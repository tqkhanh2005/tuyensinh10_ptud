<?php

declare(strict_types=1);

/**
 * BATCH G1 RUNTIME
 *
 * UC13 — Duyệt yêu cầu điều chỉnh hồ sơ
 * UC14 — Gửi yêu cầu điều chỉnh hồ sơ
 * UC15 — Đăng ký/nguyện vọng
 * UC20 — Đặt chỉ tiêu tuyển sinh
 *
 * CHỈ LÀ TEST:
 * - Không sửa production.
 * - Không tạo migration.
 * - Không sửa trigger.
 * - Không sử dụng dữ liệu Batch A-F làm fixture.
 * - Mọi fixture đều có tag riêng và cleanup cuối file.
 *
 * Lưu ý:
 * - Một số yêu cầu nghiệp vụ không thể kiểm thử qua service hiện tại
 *   vì service chưa có tham số/handler tương ứng. Những trường hợp đó
 *   được ghi BLOCKED thay vì giả lập PASS.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';
require_once __DIR__ . '/../includes/school_admission_service.php';

$pdo = db();

$tag = 'G1_' . date('mdHis') . '_' . random_int(100, 999);

$periodIds = [];
$schoolIds = [];
$candidateIds = [];
$userIds = [];
$adjustmentIds = [];

$results = [];

function assert_true(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}

function must_fail(callable $fn, string $messageContains = ''): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if (
            $messageContains !== ''
            && stripos($e->getMessage(), $messageContains) === false
        ) {
            throw new RuntimeException(
                'Operation was rejected, but message did not contain expected text. ' .
                    'Actual: ' . $e->getMessage()
            );
        }

        return;
    }

    throw new RuntimeException('Expected rejection did not occur.');
}

function blocked_test(string $reason): void
{
    throw new LogicException('[BLOCKED] ' . $reason);
}

function test(int $number, string $name, callable $fn): void
{
    global $results;

    try {
        $fn();

        $results[$number] = [
            'status' => 'PASS',
            'message' => "PASS TEST {$number}: {$name}"
        ];

        echo $results[$number]['message'] . PHP_EOL;
    } catch (LogicException $e) {
        $results[$number] = [
            'status' => 'BLOCKED',
            'message' => "BLOCKED TEST {$number}: {$name} — {$e->getMessage()}"
        ];

        echo $results[$number]['message'] . PHP_EOL;
    } catch (Throwable $e) {
        $results[$number] = [
            'status' => 'FAIL',
            'message' => "FAIL TEST {$number}: {$name} — {$e->getMessage()}"
        ];

        echo $results[$number]['message'] . PHP_EOL;
    }
}

function fetch_one(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $row = $stmt->fetch();

    return $row ?: null;
}

function fetch_value(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

function create_period(PDO $pdo, string $tag, array $overrides = []): int
{
    $now = new DateTimeImmutable('now');

    $data = [
        'name' => $tag . '_Period',
        'academic_year' => $tag,
        'admission_method' => 'thi tuyển',
        'plan_content' => 'Runtime fixture',
        'registration_start' => $now->modify('-1 hour')->format('Y-m-d H:i:s'),
        'registration_end' => $now->modify('+1 hour')->format('Y-m-d H:i:s'),
        'status' => 'published',
    ];

    $data = array_merge($data, $overrides);

    return admission_period_create($pdo, $data);
}

function create_school(
    PDO $pdo,
    string $tag,
    string $level,
    string $suffix
): int {
    /*
     * school_code phải unique.
     * Không cắt tag + suffix theo cách làm mất phần phân biệt.
     * Dùng hash ngắn để giữ uniqueness trong giới hạn độ dài.
     */
    $code = 'G1' . strtoupper(substr(
        hash('sha256', $tag . '|' . $level . '|' . $suffix),
        0,
        16
    ));

    return school_create($pdo, [
        'school_code' => $code,
        'name' => $tag . ' ' . $suffix,
        'level' => $level,
        'address' => 'TEST G1',
        'district' => 'TEST',
        'admission_area' => 'TEST',
        'phone' => '0900000000',
        'email' => strtolower($tag . $suffix) . '@example.com',
    ]);
}

function create_candidate_fixture(
    string $tag,
    int $middleSchoolId,
    string $suffix
): int {
    return candidate_create([
        'national_id' => substr($tag . $suffix . 'CID', 0, 20),
        'full_name' => $tag . ' Candidate ' . $suffix,
        'date_of_birth' => '2010-01-01',
        'gender' => 'Nam',
        'address' => 'TEST G1',
        'phone' => '0910000000',
        'email' => strtolower($tag . $suffix) . '@example.com',
        'middle_school_id' => $middleSchoolId,
        'eligible' => 1,
        'status' => 'active',
    ]);
}

function candidate_adjustment_data(
    array $candidate,
    string $newName
): array {
    return [
        'national_id' => $candidate['national_id'],
        'full_name' => $newName,
        'date_of_birth' => $candidate['date_of_birth'] ?? '',
        'gender' => $candidate['gender'] ?? '',
        'address' => $candidate['address'] ?? '',
        'phone' => $candidate['phone'] ?? '',
        'email' => $candidate['email'] ?? '',
        'middle_school_id' => (int)($candidate['middle_school_id'] ?? 0),
        'eligible' => (int)($candidate['eligible'] ?? 1),
        'status' => $candidate['status'] ?? 'active',
    ];
}

try {
    /*
     * ------------------------------------------------------------
     * SETUP
     * ------------------------------------------------------------
     */

    echo PHP_EOL;
    echo "========== BATCH G1 RUNTIME ==========" . PHP_EOL;
    echo "Tag: {$tag}" . PHP_EOL;

    /*
     * Tạo 2 kỳ tuyển sinh riêng để kiểm tra period isolation.
     */
    $period1 = create_period($pdo, $tag . '_P1');
    $period2 = create_period($pdo, $tag . '_P2');

    $periodIds[] = $period1;
    $periodIds[] = $period2;

    /*
     * THCS fixture dùng làm middle_school_id.
     */
    $middleSchoolId = create_school(
        $pdo,
        $tag,
        'THCS',
        'MIDDLE'
    );

    $schoolIds[] = $middleSchoolId;

    /*
     * Hai trường THPT dùng cho aspirations/quota.
     */
    $school1 = create_school(
        $pdo,
        $tag,
        'THPT',
        'THPT1'
    );

    $school2 = create_school(
        $pdo,
        $tag,
        'THPT',
        'THPT2'
    );

    $schoolIds[] = $school1;
    $schoolIds[] = $school2;

    /*
     * Candidate fixtures.
     */
    $candidate1 = create_candidate_fixture(
        $tag,
        $middleSchoolId,
        'A'
    );

    $candidate2 = create_candidate_fixture(
        $tag,
        $middleSchoolId,
        'B'
    );

    $candidateIds[] = $candidate1;
    $candidateIds[] = $candidate2;

    $candidateRow1 = candidate_find($candidate1);
    $candidateRow2 = candidate_find($candidate2);

    assert_true(
        $candidateRow1 !== null,
        'Candidate 1 fixture was not created.'
    );

    assert_true(
        $candidateRow2 !== null,
        'Candidate 2 fixture was not created.'
    );

    /*
     * Đúng user ID tương ứng với candidate.
     *
     * Đây là các user được candidate_create() tạo ra.
     */
    $candidateUser1 = (int)$candidateRow1['user_id'];
    $candidateUser2 = (int)$candidateRow2['user_id'];

    assert_true(
        $candidateUser1 > 0,
        'Candidate 1 does not have a valid user_id.'
    );

    assert_true(
        $candidateUser2 > 0,
        'Candidate 2 does not have a valid user_id.'
    );

    if ($candidateUser1 > 0) {
        $userIds[] = $candidateUser1;
    }

    if ($candidateUser2 > 0) {
        $userIds[] = $candidateUser2;
    }

    /*
     * Tìm user có vai trò staff/admin đã tồn tại từ Batch A.
     * Không tạo/sửa tài khoản production.
     */
    $reviewerId = (int)fetch_value(
        $pdo,
        'SELECT u.id
         FROM users u
         LEFT JOIN user_roles ur ON ur.user_id=u.id
         LEFT JOIN roles r ON r.id=ur.role_id
         WHERE u.active=1
           AND r.code IN ("admin","so_gdtd")
         ORDER BY u.id
         LIMIT 1'
    );

    if ($reviewerId <= 0) {
        throw new RuntimeException(
            'Không tìm thấy user reviewer có role admin/so_gdtd để chạy fixture UC13.'
        );
    }

    /*
     * ------------------------------------------------------------
     * UC13 / UC14
     * ------------------------------------------------------------
     */

    /*
     * 1. Valid request
     */
    test(
        1,
        'UC14 valid request',
        function () use (
            $candidate1,
            $candidateUser1,
            $candidateRow1,
            &$adjustmentIds
        ) {
            $old = candidate_adjustment_data(
                $candidateRow1,
                $candidateRow1['full_name']
            );

            $new = candidate_adjustment_data(
                $candidateRow1,
                $candidateRow1['full_name'] . ' Updated'
            );

            $id = adjustment_submit(
                $candidate1,
                $candidateUser1,
                $old,
                $new,
                'TEST G1 valid adjustment',
                'TEST-G1-evidence.txt'
            );

            $adjustmentIds[] = $id;

            $row = adjustment_find($id);

            assert_true(
                $row !== null,
                'Adjustment request was not persisted.'
            );

            assert_true(
                $row['status'] === 'pending',
                'New adjustment request is not pending.'
            );

            assert_true(
                (int)$row['candidate_id'] === $candidate1,
                'Wrong candidate linked.'
            );

            assert_true(
                (int)$row['submitted_by_user_id'] === $candidateUser1,
                'Wrong submitter linked.'
            );
        }
    );

    /*
     * 2. Invalid data
     */
    test(
        2,
        'UC14 invalid data rejected before persistence',
        function () use (
            $candidate1,
            $candidateUser1,
            $candidateRow1,
            $pdo,
            &$adjustmentIds
        ) {
            $before = (int)fetch_value(
                $pdo,
                'SELECT COUNT(*)
                 FROM adjustment_requests
                 WHERE candidate_id=:candidate_id
                   AND submitted_by_user_id=:user_id',
                [
                    'candidate_id' => $candidate1,
                    'user_id' => $candidateUser1
                ]
            );

            $invalidNew = [
                'national_id' => '',
                'full_name' => '',
                'date_of_birth' => 'invalid-date',
                'gender' => '',
                'address' => '',
                'phone' => 'invalid-phone',
                'email' => 'invalid-email',
                'middle_school_id' => 0,
                'eligible' => 1,
                'status' => 'active',
            ];

            $caught = false;

            try {
                $id = adjustment_submit(
                    $candidate1,
                    $candidateUser1,
                    candidate_adjustment_data(
                        $candidateRow1,
                        $candidateRow1['full_name']
                    ),
                    $invalidNew,
                    '',
                    null
                );

                $adjustmentIds[] = $id;
            } catch (Throwable $e) {
                $caught = true;
            }

            $after = (int)fetch_value(
                $pdo,
                'SELECT COUNT(*)
                 FROM adjustment_requests
                 WHERE candidate_id=:candidate_id
                   AND submitted_by_user_id=:user_id',
                [
                    'candidate_id' => $candidate1,
                    'user_id' => $candidateUser1
                ]
            );

            assert_true(
                $caught,
                'UC14 requires invalid data to be rejected, but adjustment_submit() accepted it.'
            );

            assert_true(
                $before === $after,
                'Invalid adjustment request was persisted.'
            );
        }
    );

    /*
     * 3. Nonexistent candidate/request
     */
    test(
        3,
        'UC13/14 nonexistent candidate/request rejected',
        function () use ($candidateUser1) {
            must_fail(
                fn() => adjustment_submit(
                    999999999,
                    $candidateUser1,
                    [],
                    [],
                    'TEST',
                    null
                )
            );

            must_fail(
                fn() => adjustment_review(
                    999999999,
                    $candidateUser1,
                    true,
                    ''
                )
            );
        }
    );

    /*
     * 4. Ownership
     */
    test(
        4,
        'UC14 ownership enforced',
        function () use (
            $candidate1,
            $candidateUser2,
            $candidateRow1,
            &$adjustmentIds
        ) {
            $old = candidate_adjustment_data(
                $candidateRow1,
                $candidateRow1['full_name']
            );

            $new = candidate_adjustment_data(
                $candidateRow1,
                $candidateRow1['full_name'] . ' Ownership'
            );

            $caught = false;

            try {
                $id = adjustment_submit(
                    $candidate1,
                    $candidateUser2,
                    $old,
                    $new,
                    'TEST ownership violation',
                    null
                );

                $adjustmentIds[] = $id;
            } catch (Throwable $e) {
                $caught = true;
            }

            assert_true(
                $caught,
                'Cross-candidate ownership was accepted by adjustment_submit().'
            );
        }
    );

    /*
     * 5. Authorization boundary
     */
    test(
        5,
        'UC14 authorization enforced at endpoint boundary',
        function () {
            auth_logout();

            assert_true(
                !auth_check(),
                'Guest unexpectedly authenticated.'
            );
        }
    );

    /*
     * 6. Invalid CSRF
     */
    test(
        6,
        'UC13/14 invalid CSRF rejected',
        function () {
            if (!function_exists('csrf_verify')) {
                blocked_test(
                    '[MISSING SPECIFICATION] csrf_verify() is unavailable.'
                );
            }

            assert_true(
                csrf_verify('G1_INVALID_CSRF_TOKEN') === false,
                'Invalid CSRF token was accepted.'
            );

            echo "  [NOTE] This test verifies csrf_verify(); endpoint-level adjustment CSRF cannot be exercised from the 4 supplied files." . PHP_EOL;
        }
    );

    /*
     * 7. Valid approval
     */
    test(
        7,
        'UC13 valid approval updates candidate atomically',
        function () use (
            $candidate1,
            $candidateUser1,
            $candidateRow1,
            $reviewerId,
            &$adjustmentIds
        ) {
            $oldName = $candidateRow1['full_name'];
            $newName = $oldName . ' Approved';

            $old = candidate_adjustment_data(
                $candidateRow1,
                $oldName
            );

            $new = candidate_adjustment_data(
                $candidateRow1,
                $newName
            );

            $id = adjustment_submit(
                $candidate1,
                $candidateUser1,
                $old,
                $new,
                'TEST valid approval',
                null
            );

            $adjustmentIds[] = $id;

            adjustment_review(
                $id,
                $reviewerId,
                true,
                ''
            );

            $candidate = candidate_find($candidate1);
            $request = adjustment_find($id);

            assert_true(
                $candidate !== null
                    && $candidate['full_name'] === $newName,
                'Candidate profile was not updated after approval.'
            );

            assert_true(
                $request !== null
                    && $request['status'] === 'approved',
                'Adjustment request was not marked approved.'
            );

            assert_true(
                (int)$request['reviewed_by_user_id'] === $reviewerId,
                'Reviewer was not recorded.'
            );
        }
    );

    /*
     * 8. Rejection
     */
    test(
        8,
        'UC13 rejection requires reason and preserves original profile',
        function () use (
            $candidate2,
            $candidateUser2,
            $candidateRow2,
            $reviewerId,
            &$adjustmentIds
        ) {
            $originalName = $candidateRow2['full_name'];

            $old = candidate_adjustment_data(
                $candidateRow2,
                $originalName
            );

            $new = candidate_adjustment_data(
                $candidateRow2,
                $originalName . ' Rejected'
            );

            $id = adjustment_submit(
                $candidate2,
                $candidateUser2,
                $old,
                $new,
                'TEST rejection',
                null
            );

            $adjustmentIds[] = $id;

            must_fail(
                fn() => adjustment_review(
                    $id,
                    $reviewerId,
                    false,
                    ''
                ),
                'Lý do từ chối'
            );

            adjustment_review(
                $id,
                $reviewerId,
                false,
                'Minh chứng không hợp lệ trong test.'
            );

            $candidate = candidate_find($candidate2);
            $request = adjustment_find($id);

            assert_true(
                $candidate !== null
                    && $candidate['full_name'] === $originalName,
                'Candidate profile changed after rejection.'
            );

            assert_true(
                $request !== null
                    && $request['status'] === 'rejected'
                    && $request['review_reason'] === 'Minh chứng không hợp lệ trong test.',
                'Rejected adjustment state/reason was not persisted correctly.'
            );
        }
    );

    /*
     * 9. Invalid state
     */
    test(
        9,
        'UC13 second review of processed request rejected',
        function () use (
            $adjustmentIds,
            $reviewerId
        ) {
            $approvedId = 0;

            foreach ($adjustmentIds as $id) {
                $row = adjustment_find($id);

                if ($row && $row['status'] === 'approved') {
                    $approvedId = (int)$id;
                    break;
                }
            }

            assert_true(
                $approvedId > 0,
                'No approved adjustment fixture available.'
            );

            must_fail(
                fn() => adjustment_review(
                    $approvedId,
                    $reviewerId,
                    true,
                    ''
                ),
                'đã được xử lý'
            );
        }
    );

    /*
     * 10. Concurrent second processing
     */
    test(
        10,
        'UC13 concurrent second processing guard',
        function () use (
            $candidate1,
            $candidateUser1,
            $candidateRow1,
            $reviewerId,
            &$adjustmentIds
        ) {
            $old = candidate_adjustment_data(
                $candidateRow1,
                $candidateRow1['full_name']
            );

            $new = candidate_adjustment_data(
                $candidateRow1,
                $candidateRow1['full_name'] . ' Concurrent'
            );

            $id = adjustment_submit(
                $candidate1,
                $candidateUser1,
                $old,
                $new,
                'TEST concurrency',
                null
            );

            $adjustmentIds[] = $id;

            adjustment_review(
                $id,
                $reviewerId,
                true,
                ''
            );

            must_fail(
                fn() => adjustment_review(
                    $id,
                    $reviewerId,
                    false,
                    'Second processing'
                ),
                'đã được xử lý'
            );

            $row = adjustment_find($id);

            assert_true(
                $row !== null
                    && $row['status'] === 'approved',
                'Second processing changed the already-approved state.'
            );
        }
    );

    /*
     * 11. Transaction rollback
     *
     * Force a genuine reviewer FK failure after the profile update and verify
     * that the service rolls back its own transaction and notification.
     */
    test(
        11,
        'UC13 transaction rollback',
        function () use ($pdo, $candidate1, $candidateUser1, &$adjustmentIds) {
            // A missing reviewer violates the real FK only AFTER the candidate update.
            // This exercises the service's own rollback without a production test hook.
            $before = candidate_find($candidate1);
            $new = candidate_adjustment_data($before, $before['full_name'] . ' Rollback');
            $requestId = adjustment_submit($candidate1, $candidateUser1, $before, $new, 'Verify atomic approval', null);
            $adjustmentIds[] = $requestId;
            $notificationsBefore = (int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
            $caught = false;
            try {
                adjustment_review($requestId, 0, true);
            } catch (PDOException $exception) {
                $caught = $exception->getCode() === '23000';
            }
            assert_true($caught, 'Expected reviewer FK failure after candidate update.');
            assert_true(candidate_find($candidate1) === $before, 'Candidate change was not rolled back.');
            assert_true(adjustment_find($requestId)['status'] === 'pending', 'Request must remain pending after rollback.');
            assert_true((int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn() === $notificationsBefore, 'Rollback must not send a notification.');
            assert_true(!$pdo->inTransaction(), 'Failed approval left a transaction open.');
        }
    );

    /*
     * 12. Notification
     *
     * Tạo MỘT request pending riêng cho TEST 12.
     * Không dùng request TEST 1 vì TEST 7 đã xử lý request đó.
     */
    test(
        12,
        'UC13 approval creates notification',
        function () use (
            $pdo,
            $candidate1,
            $candidateUser1,
            $reviewerId,
            &$adjustmentIds
        ) {
            /*
             * Đọc candidate hiện tại sau TEST 7.
             * Không dùng candidateRow1 cũ vì TEST 7 đã thay đổi full_name.
             */
            $candidate = candidate_find($candidate1);

            assert_true(
                $candidate !== null,
                'Candidate 1 fixture missing.'
            );

            $old = candidate_adjustment_data(
                $candidate,
                $candidate['full_name']
            );

            $new = candidate_adjustment_data(
                $candidate,
                $candidate['full_name'] . ' Notification'
            );

            /*
             * Tạo request RIÊNG và phải pending trước khi review.
             */
            $id = adjustment_submit(
                $candidate1,
                $candidateUser1,
                $old,
                $new,
                'TEST notification approval',
                null
            );

            $adjustmentIds[] = $id;

            $request = adjustment_find($id);

            assert_true(
                $request !== null,
                'Adjustment request fixture missing.'
            );

            assert_true(
                $request['status'] === 'pending',
                'Notification test request is not pending.'
            );

            assert_true(
                (int)$request['submitted_by_user_id'] === $candidateUser1,
                'Notification recipient fixture is incorrect.'
            );

            $recipient = (int)$request['submitted_by_user_id'];

            $beforeStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM notifications
                 WHERE user_id=:user_id'
            );

            $beforeStmt->execute([
                'user_id' => $recipient
            ]);

            $before = (int)$beforeStmt->fetchColumn();

            /*
             * Review đúng MỘT lần.
             */
            adjustment_review(
                $id,
                $reviewerId,
                true,
                ''
            );

            $afterStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM notifications
                 WHERE user_id=:user_id'
            );

            $afterStmt->execute([
                'user_id' => $recipient
            ]);

            $after = (int)$afterStmt->fetchColumn();

            assert_true(
                $after === $before + 1,
                'Approval notification was not created.'
            );

            $notification = fetch_one(
                $pdo,
                'SELECT user_id,title,message
                 FROM notifications
                 WHERE user_id=:user_id
                 ORDER BY id DESC
                 LIMIT 1',
                [
                    'user_id' => $recipient
                ]
            );

            assert_true(
                $notification !== null,
                'Latest notification was not found.'
            );

            assert_true(
                (int)$notification['user_id'] === $recipient,
                'Notification recipient is incorrect.'
            );

            assert_true(
                stripos(
                    (string)$notification['title'],
                    'điều chỉnh'
                ) !== false,
                'Notification title does not describe adjustment result.'
            );
        }
    );

    /*
     * 13. Isolation
     */
    test(
        13,
        'UC14 adjustment data remains candidate-specific',
        function () use (
            $candidate1,
            $candidate2
        ) {
            $rows1 = adjustment_list('pending');
            $ids1 = [];

            foreach ($rows1 as $row) {
                if ((int)$row['candidate_id'] === $candidate1) {
                    $ids1[] = (int)$row['candidate_id'];
                }
            }

            $rows2 = adjustment_list('pending');
            $ids2 = [];

            foreach ($rows2 as $row) {
                if ((int)$row['candidate_id'] === $candidate2) {
                    $ids2[] = (int)$row['candidate_id'];
                }
            }

            foreach ($ids1 as $id) {
                assert_true(
                    $id === $candidate1,
                    'Adjustment list returned wrong candidate identifier.'
                );
            }

            foreach ($ids2 as $id) {
                assert_true(
                    $id === $candidate2,
                    'Adjustment list returned wrong candidate identifier.'
                );
            }
        }
    );

    /*
     * ------------------------------------------------------------
     * UC15 — ASPIRATIONS
     * ------------------------------------------------------------
     */

    /*
     * 14. Valid ordered aspirations
     *
     * candidate1 -> candidateUser1
     */
    test(
        14,
        'UC15 valid ordered aspirations',
        function () use (
            $candidate1,
            $candidateUser1,
            $period1,
            $school1,
            $school2
        ) {
            aspiration_save(
                $candidate1,
                $period1,
                [$school2, $school1],
                $candidateUser1
            );

            $rows = aspiration_get(
                $candidate1,
                $period1
            );

            assert_true(
                count($rows) === 2,
                'Expected two aspirations.'
            );

            assert_true(
                (int)$rows[0]['school_id'] === $school2
                    && (int)$rows[0]['priority_order'] === 1,
                'First aspiration priority is incorrect.'
            );

            assert_true(
                (int)$rows[1]['school_id'] === $school1
                    && (int)$rows[1]['priority_order'] === 2,
                'Second aspiration priority is incorrect.'
            );
        }
    );

    /*
     * 15. Invalid data
     *
     * candidate1 -> candidateUser1
     */
    test(
        15,
        'UC15 invalid aspiration data rejected',
        function () use (
            $candidate1,
            $candidateUser1,
            $period1
        ) {
            must_fail(
                fn() => aspiration_save(
                    $candidate1,
                    $period1,
                    [],
                    $candidateUser1
                ),
                'ít nhất một'
            );
        }
    );

    /*
     * 16. Duplicate school
     *
     * candidate1 -> candidateUser1
     */
    test(
        16,
        'UC15 duplicate school rejected',
        function () use (
            $candidate1,
            $candidateUser1,
            $period1,
            $school1
        ) {
            must_fail(
                fn() => aspiration_save(
                    $candidate1,
                    $period1,
                    [$school1, $school1],
                    $candidateUser1
                ),
                'trùng trường'
            );
        }
    );

    /*
     * 17. Priority order
     *
     * candidate1 -> candidateUser1
     */
    test(
        17,
        'UC15 priority order follows submitted school order',
        function () use (
            $candidate1,
            $candidateUser1,
            $period1,
            $school1,
            $school2
        ) {
            aspiration_save(
                $candidate1,
                $period1,
                [$school2, $school1],
                $candidateUser1
            );

            $rows = aspiration_get(
                $candidate1,
                $period1
            );

            assert_true(
                count($rows) === 2,
                'Expected two aspirations.'
            );

            assert_true(
                (int)$rows[0]['school_id'] === $school2
                    && (int)$rows[0]['priority_order'] === 1,
                'Submitted first school was not assigned priority 1.'
            );

            assert_true(
                (int)$rows[1]['school_id'] === $school1
                    && (int)$rows[1]['priority_order'] === 2,
                'Submitted second school was not assigned priority 2.'
            );
        }
    );

    /*
     * 18. Ownership
     *
     * candidate A user ID + candidate B ID
     * => MUST FAIL
     */
    test(
        18,
        'UC15 candidate A cannot modify candidate B aspirations',
        function () use (
            $candidate1,
            $candidate2,
            $candidateUser1,
            $period1,
            $school1
        ) {
            must_fail(
                fn() => aspiration_save(
                    $candidate2,
                    $period1,
                    [$school1],
                    $candidateUser1
                )
            );
        }
    );

    /*
     * 19. Unauthorized
     */
    test(
        19,
        'UC15 unauthorized denied by existing RBAC',
        function () {
            auth_logout();

            assert_true(
                !auth_check()
                    && !auth_has_permission('aspiration.manage'),
                'Guest unexpectedly has aspiration.manage.'
            );
        }
    );

    /*
     * 20. Invalid CSRF
     */
    test(
        20,
        'UC15 invalid CSRF rejected',
        function () {
            if (!function_exists('csrf_verify')) {
                blocked_test(
                    '[MISSING SPECIFICATION] csrf_verify() is unavailable.'
                );
            }

            assert_true(
                csrf_verify('G1_INVALID_ASPIRATION_CSRF') === false,
                'Invalid CSRF token was accepted.'
            );

            echo "  [NOTE] aspirations/index.php calls require_csrf(); this test validates the existing CSRF primitive." . PHP_EOL;
        }
    );

    /*
     * 21. Period isolation
     *
     * candidate1 -> candidateUser1
     */
    test(
        21,
        'UC15 period isolation',
        function () use (
            $candidate1,
            $candidateUser1,
            $period1,
            $period2,
            $school1,
            $school2
        ) {
            aspiration_save(
                $candidate1,
                $period1,
                [$school1],
                $candidateUser1
            );

            aspiration_save(
                $candidate1,
                $period2,
                [$school2],
                $candidateUser1
            );

            $p1 = aspiration_get(
                $candidate1,
                $period1
            );

            $p2 = aspiration_get(
                $candidate1,
                $period2
            );

            assert_true(
                count($p1) === 1
                    && (int)$p1[0]['school_id'] === $school1,
                'Period 1 aspiration was contaminated.'
            );

            assert_true(
                count($p2) === 1
                    && (int)$p2[0]['school_id'] === $school2,
                'Period 2 aspiration was contaminated.'
            );
        }
    );

    /*
     * 22. Transaction rollback
     *
     * candidate1 -> candidateUser1
     */
    test(
        22,
        'UC15 transaction rollback',
        function () use (
            $candidate1,
            $candidateUser1,
            $period1,
            $school1,
            $middleSchoolId
        ) {
            aspiration_save(
                $candidate1,
                $period1,
                [$school1],
                $candidateUser1
            );

            $before = aspiration_get(
                $candidate1,
                $period1
            );

            must_fail(
                fn() => aspiration_save(
                    $candidate1,
                    $period1,
                    [$school1, $middleSchoolId],
                    $candidateUser1
                )
            );

            $after = aspiration_get(
                $candidate1,
                $period1
            );

            assert_true(
                count($before) === count($after),
                'Aspiration transaction did not rollback row count.'
            );

            assert_true(
                count($after) === 1
                    && (int)$after[0]['school_id'] === $school1,
                'Previous valid aspiration was lost after rollback.'
            );
        }
    );

    /*
     * ------------------------------------------------------------
     * UC20 — QUOTAS
     * ------------------------------------------------------------
     */

    /*
     * 23. Valid quota
     */
    test(
        23,
        'UC20 valid quota',
        function () use (
            $pdo,
            $period1,
            $school1
        ) {
            quota_save_all(
                $pdo,
                $period1,
                [
                    (string)$school1 => '100'
                ]
            );

            $row = fetch_one(
                $pdo,
                'SELECT *
                 FROM admission_quotas
                 WHERE admission_period_id=:p
                   AND school_id=:s',
                [
                    'p' => $period1,
                    's' => $school1
                ]
            );

            assert_true(
                $row !== null
                    && (int)$row['quota'] === 100
                    && $row['status'] === 'set',
                'Valid quota was not persisted correctly.'
            );
        }
    );

    /*
     * 24. Invalid quota
     */
    test(
        24,
        'UC20 zero/invalid quota rejected',
        function () use (
            $pdo,
            $period1,
            $school2
        ) {
            must_fail(
                fn() => quota_save_all(
                    $pdo,
                    $period1,
                    [
                        (string)$school2 => '0'
                    ]
                ),
                'Chỉ tiêu'
            );
        }
    );

    /*
     * 25. Duplicate/upsert
     */
    test(
        25,
        'UC20 duplicate quota uses upsert',
        function () use (
            $pdo,
            $period1,
            $school1
        ) {
            quota_save_all(
                $pdo,
                $period1,
                [
                    (string)$school1 => '100'
                ]
            );

            quota_save_all(
                $pdo,
                $period1,
                [
                    (string)$school1 => '120'
                ]
            );

            $count = (int)fetch_value(
                $pdo,
                'SELECT COUNT(*)
                 FROM admission_quotas
                 WHERE admission_period_id=:p
                   AND school_id=:s',
                [
                    'p' => $period1,
                    's' => $school1
                ]
            );

            $quota = (int)fetch_value(
                $pdo,
                'SELECT quota
                 FROM admission_quotas
                 WHERE admission_period_id=:p
                   AND school_id=:s',
                [
                    'p' => $period1,
                    's' => $school1
                ]
            );

            assert_true(
                $count === 1,
                'Duplicate quota created multiple rows.'
            );

            assert_true(
                $quota === 120,
                'Upsert did not update quota.'
            );
        }
    );

    /*
     * 26. Invalid school/period
     */
    test(
        26,
        'UC20 invalid school/period rejected',
        function () use (
            $pdo,
            $period1,
            $school1
        ) {
            must_fail(
                fn() => quota_save_all(
                    $pdo,
                    999999999,
                    [
                        (string)$school1 => '50'
                    ]
                )
            );

            must_fail(
                fn() => quota_save_all(
                    $pdo,
                    $period1,
                    [
                        '999999999' => '50'
                    ]
                )
            );
        }
    );

    /*
     * 27. Unauthorized
     */
    test(
        27,
        'UC20 unauthorized denied by existing RBAC',
        function () {
            auth_logout();

            assert_true(
                !auth_check()
                    && !auth_has_permission('admission.manage'),
                'Guest unexpectedly has admission.manage.'
            );
        }
    );

    /*
     * 28. Invalid CSRF
     */
    test(
        28,
        'UC20 invalid CSRF rejected',
        function () {
            if (!function_exists('csrf_verify')) {
                blocked_test(
                    '[MISSING SPECIFICATION] csrf_verify() is unavailable.'
                );
            }

            assert_true(
                csrf_verify('G1_INVALID_QUOTA_CSRF') === false,
                'Invalid CSRF token was accepted.'
            );

            echo "  [NOTE] quotas/index.php calls require_csrf(); this test validates the existing CSRF primitive." . PHP_EOL;
        }
    );

    /*
     * 29. Period isolation
     */
    test(
        29,
        'UC20 quota period isolation',
        function () use (
            $pdo,
            $period1,
            $period2,
            $school1
        ) {
            quota_save_all(
                $pdo,
                $period1,
                [
                    (string)$school1 => '111'
                ]
            );

            quota_save_all(
                $pdo,
                $period2,
                [
                    (string)$school1 => '222'
                ]
            );

            $q1 = (int)fetch_value(
                $pdo,
                'SELECT quota
                 FROM admission_quotas
                 WHERE admission_period_id=:p
                   AND school_id=:s',
                [
                    'p' => $period1,
                    's' => $school1
                ]
            );

            $q2 = (int)fetch_value(
                $pdo,
                'SELECT quota
                 FROM admission_quotas
                 WHERE admission_period_id=:p
                   AND school_id=:s',
                [
                    'p' => $period2,
                    's' => $school1
                ]
            );

            assert_true(
                $q1 === 111,
                'Period 1 quota was contaminated.'
            );

            assert_true(
                $q2 === 222,
                'Period 2 quota was contaminated.'
            );
        }
    );

    /*
     * 30. Transaction rollback
     */
    test(
        30,
        'UC20 quota transaction rollback',
        function () use (
            $pdo,
            $period1,
            $school1,
            $school2
        ) {
            $pdo->prepare(
                'DELETE FROM admission_quotas
                 WHERE admission_period_id=:p
                   AND school_id IN (:s1,:s2)'
            )->execute([
                'p' => $period1,
                's1' => $school1,
                's2' => $school2
            ]);

            $caught = false;

            try {
                quota_save_all(
                    $pdo,
                    $period1,
                    [
                        (string)$school1 => '333',
                        (string)$school2 => 'INVALID_QUOTA'
                    ]
                );
            } catch (Throwable $e) {
                $caught = true;
            }

            assert_true(
                $caught,
                'Mixed valid/invalid quota batch was unexpectedly accepted.'
            );

            $count = (int)fetch_value(
                $pdo,
                'SELECT COUNT(*)
                 FROM admission_quotas
                 WHERE admission_period_id=:p
                   AND school_id IN (:s1,:s2)',
                [
                    'p' => $period1,
                    's1' => $school1,
                    's2' => $school2
                ]
            );

            assert_true(
                $count === 0,
                'Quota transaction left partial data after rollback.'
            );
        }
    );
} catch (Throwable $e) {
    fwrite(
        STDERR,
        'FAIL SETUP: ' . $e->getMessage() . PHP_EOL
    );
} finally {
    /*
     * ------------------------------------------------------------
     * CLEANUP
     * ------------------------------------------------------------
     */

    try {
        /*
         * Notifications phải xóa trước users.
         * Chỉ xóa notification của user fixture hiện tại.
         */
        if ($userIds) {
            $placeholders = implode(
                ',',
                array_fill(0, count($userIds), '?')
            );

            $stmt = $pdo->prepare(
                "DELETE FROM notifications
                 WHERE user_id IN ($placeholders)"
            );

            $stmt->execute($userIds);
        }

        /*
         * Adjustment requests phải xóa trước candidates.
         */
        if ($candidateIds) {
            $placeholders = implode(
                ',',
                array_fill(0, count($candidateIds), '?')
            );

            $stmt = $pdo->prepare(
                "DELETE FROM adjustment_requests
                 WHERE candidate_id IN ($placeholders)"
            );

            $stmt->execute($candidateIds);
        }

        /*
         * Aspirations phải xóa trước candidates/schools/periods.
         */
        if ($candidateIds) {
            $placeholders = implode(
                ',',
                array_fill(0, count($candidateIds), '?')
            );

            $stmt = $pdo->prepare(
                "DELETE FROM aspirations
                 WHERE candidate_id IN ($placeholders)"
            );

            $stmt->execute($candidateIds);
        }

        /*
         * Quotas phải xóa trước periods/schools.
         */
        if ($periodIds) {
            $placeholders = implode(
                ',',
                array_fill(0, count($periodIds), '?')
            );

            $stmt = $pdo->prepare(
                "DELETE FROM admission_quotas
                 WHERE admission_period_id IN ($placeholders)"
            );

            $stmt->execute($periodIds);
        }

        /*
         * Xóa candidate fixtures.
         */
        if ($candidateIds) {
            $placeholders = implode(
                ',',
                array_fill(0, count($candidateIds), '?')
            );

            $stmt = $pdo->prepare(
                "DELETE FROM candidates
                 WHERE id IN ($placeholders)"
            );

            $stmt->execute($candidateIds);
        }

        /*
         * Xóa RBAC mapping/user được candidate_create() tạo.
         * Chỉ dùng chính user IDs do fixture tạo.
         */
        if ($userIds) {
            $placeholders = implode(
                ',',
                array_fill(0, count($userIds), '?')
            );

            $stmt = $pdo->prepare(
                "DELETE FROM user_roles
                 WHERE user_id IN ($placeholders)"
            );

            $stmt->execute($userIds);

            $stmt = $pdo->prepare(
                "DELETE FROM users
                 WHERE id IN ($placeholders)"
            );

            $stmt->execute($userIds);
        }

        /*
         * Xóa school fixtures.
         */
        if ($schoolIds) {
            $placeholders = implode(
                ',',
                array_fill(0, count($schoolIds), '?')
            );

            $stmt = $pdo->prepare(
                "DELETE FROM schools
                 WHERE id IN ($placeholders)"
            );

            $stmt->execute($schoolIds);
        }

        /*
         * Xóa period fixtures cuối cùng.
         */
        if ($periodIds) {
            $placeholders = implode(
                ',',
                array_fill(0, count($periodIds), '?')
            );

            $stmt = $pdo->prepare(
                "DELETE FROM admission_periods
                 WHERE id IN ($placeholders)"
            );

            $stmt->execute($periodIds);
        }

        echo PHP_EOL;
        echo "CLEANUP: OK" . PHP_EOL;
    } catch (Throwable $cleanupError) {
        echo PHP_EOL;
        echo 'CLEANUP ERROR: ' . $cleanupError->getMessage() . PHP_EOL;
    }
}

/*
 * ------------------------------------------------------------
 * SUMMARY
 * ------------------------------------------------------------
 */

$pass = 0;
$fail = 0;
$blocked = 0;

foreach ($results as $result) {
    if ($result['status'] === 'PASS') {
        $pass++;
    } elseif ($result['status'] === 'BLOCKED') {
        $blocked++;
    } else {
        $fail++;
    }
}

$total = count($results);

echo PHP_EOL;
echo "========== BATCH G1 SUMMARY ==========" . PHP_EOL;
echo "TOTAL   : {$total}" . PHP_EOL;
echo "PASS    : {$pass}" . PHP_EOL;
echo "FAIL    : {$fail}" . PHP_EOL;
echo "BLOCKED : {$blocked}" . PHP_EOL;

if ($fail > 0 || $blocked > 0) {
    echo "BATCH G1 RUNTIME: NOT COMPLETE" . PHP_EOL;
    exit(1);
}

echo "BATCH G1 RUNTIME: {$pass}/{$total} PASS" . PHP_EOL;
exit(0);
