<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function candidate_validate(array $input): array
{
    $data = [
        'national_id' => trim((string) ($input['national_id'] ?? '')),
        'full_name' => trim((string) ($input['full_name'] ?? '')),
        'date_of_birth' => trim((string) ($input['date_of_birth'] ?? '')),
        'gender' => trim((string) ($input['gender'] ?? '')),
        'address' => trim((string) ($input['address'] ?? '')),
        'phone' => trim((string) ($input['phone'] ?? '')),
        'email' => trim((string) ($input['email'] ?? '')),
        'middle_school_id' => (int) ($input['middle_school_id'] ?? 0),
        'eligible' => isset($input['eligible']) ? (int) $input['eligible'] : 1,
        'status' => in_array(($input['status'] ?? 'active'), ['active', 'inactive'], true) ? ($input['status'] ?? 'active') : 'active'
    ];
    $errors = [];
    if ($data['national_id'] === '')
        $errors['national_id'] = 'Số CMND/CCCD không được để trống.';
    if ($data['full_name'] === '')
        $errors['full_name'] = 'Họ tên không được để trống.';
    if ($data['date_of_birth'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth']))
        $errors['date_of_birth'] = 'Ngày sinh không hợp lệ.';
    if ($data['phone'] !== '' && !preg_match('/^[0-9+().\-\s]{7,30}$/', $data['phone']))
        $errors['phone'] = 'Số điện thoại không hợp lệ.';
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Email không hợp lệ.';
    return [$data, $errors];
}
function candidate_list(array $filters = []): array
{
    $pdo = db();
    $sql = 'SELECT c.*, s.name AS middle_school_name, s.school_code AS middle_school_code
            FROM candidates c
            LEFT JOIN schools s ON s.id = c.middle_school_id
            WHERE 1=1';
    $params = [];

    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        $sql .= ' AND (c.national_id LIKE :q_nat OR c.full_name LIKE :q_name OR c.email LIKE :q_email OR c.phone LIKE :q_phone)';
        $params['q_nat'] = '%' . $q . '%';
        $params['q_name'] = '%' . $q . '%';
        $params['q_email'] = '%' . $q . '%';
        $params['q_phone'] = '%' . $q . '%';
    }

    $middleSchoolId = isset($filters['middle_school_id']) ? (int)$filters['middle_school_id'] : 0;
    if ($middleSchoolId > 0) {
        $sql .= ' AND c.middle_school_id = :middle_school_id';
        $params['middle_school_id'] = $middleSchoolId;
    }

    $status = trim((string)($filters['status'] ?? ''));
    if ($status === 'active' || $status === 'inactive') {
        $sql .= ' AND c.status = :status';
        $params['status'] = $status;
    }

    $eligible = trim((string)($filters['eligible'] ?? ''));
    if ($eligible === '0' || $eligible === '1') {
        $sql .= ' AND c.eligible = :eligible';
        $params['eligible'] = (int)$eligible;
    }

    $sql .= ' ORDER BY c.status DESC, c.full_name ASC, c.id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function middle_schools_active_list(): array
{
    $stmt = db()->query('SELECT id, school_code, name FROM schools WHERE level = "THCS" AND active = 1 ORDER BY name ASC');
    return $stmt->fetchAll();
}
function candidate_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT c.*,s.name AS middle_school_name FROM candidates c LEFT JOIN schools s ON s.id=c.middle_school_id WHERE c.id=:id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $r = $stmt->fetch();
    return $r ?: null;
}
function candidate_find_by_user(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM candidates WHERE user_id=:user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $r = $stmt->fetch();
    return $r ?: null;
}
function candidate_duplicate(string $nationalId, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM candidates WHERE national_id=:national_id';
    if ($excludeId !== null)
        $sql .= ' AND id<>:exclude_id';
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->bindValue(':national_id', $nationalId);
    if ($excludeId !== null)
        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
    $stmt->execute();
    return (bool) $stmt->fetchColumn();
}
function candidate_create(array $data): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $check = $pdo->prepare('SELECT id FROM candidates WHERE national_id = :national_id LIMIT 1 FOR UPDATE');
        $check->execute(['national_id' => $data['national_id']]);
        if ($check->fetchColumn()) {
            throw new RuntimeException('Thí sinh/Học sinh đã tồn tại trong hệ thống.');
        }

        $userCheck = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1 FOR UPDATE');
        $userCheck->execute(['username' => $data['national_id']]);
        if ($userCheck->fetchColumn()) {
            throw new RuntimeException('Tên tài khoản thí sinh đã tồn tại trong hệ thống.');
        }

        if ($data['middle_school_id'] > 0) {
            $schoolCheck = $pdo->prepare(
                'SELECT id
                FROM schools
                WHERE id=:id
                AND level=:level
                AND active=:active
                LIMIT 1
                FOR UPDATE'
            );

            $schoolCheck->execute([
                'id' => $data['middle_school_id'],
                'level' => 'THCS',
                'active' => 1,
            ]);

            if (!$schoolCheck->fetchColumn()) {
                throw new InvalidArgumentException(
                    'Trường được chọn phải tồn tại, là trường THCS và đang hoạt động.'
                );
            }
        }

        $stmt = $pdo->prepare('INSERT INTO candidates(national_id,full_name,date_of_birth,gender,address,phone,email,middle_school_id,eligible,status) VALUES(:national_id,:full_name,:date_of_birth,:gender,:address,:phone,:email,:middle_school_id,:eligible,:status)');
        $stmt->execute([
            'national_id' => $data['national_id'],
            'full_name' => $data['full_name'],
            'date_of_birth' => $data['date_of_birth'] !== '' ? $data['date_of_birth'] : null,
            'gender' => $data['gender'] !== '' ? $data['gender'] : null,
            'address' => $data['address'] !== '' ? $data['address'] : null,
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'email' => $data['email'] !== '' ? $data['email'] : null,
            'middle_school_id' => $data['middle_school_id'] > 0 ? $data['middle_school_id'] : null,
            'eligible' => $data['eligible'],
            'status' => $data['status']
        ]);
        $candidateId = (int) $pdo->lastInsertId();

        $user = $pdo->prepare('INSERT INTO users(username,password_hash,full_name,role,email,phone,active) VALUES(:username,:password_hash,:full_name,"candidate",:email,:phone,1)');
        $user->execute([
            'username' => $data['national_id'],
            'password_hash' => password_hash('1111', PASSWORD_DEFAULT),
            'full_name' => $data['full_name'],
            'email' => $data['email'] !== '' ? $data['email'] : null,
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
        ]);
        $userId = (int) $pdo->lastInsertId();

        $role = $pdo->prepare('SELECT id FROM roles WHERE code = "candidate" AND active = 1 LIMIT 1 FOR UPDATE');
        $role->execute();
        $roleId = $role->fetchColumn();
        if (!$roleId)
            throw new RuntimeException('Không tìm thấy role candidate.');

        $mapping = $pdo->prepare('INSERT INTO user_roles(user_id,role_id) VALUES(:user_id,:role_id)');
        $mapping->execute(['user_id' => $userId, 'role_id' => (int) $roleId]);

        $link = $pdo->prepare('UPDATE candidates SET user_id = :user_id WHERE id = :id');
        $link->execute(['user_id' => $userId, 'id' => $candidateId]);

        $pdo->commit();
        return $candidateId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        throw $e;
    }
}

function candidate_update(int $id, array $data): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $find = $pdo->prepare('SELECT id, middle_school_id FROM candidates WHERE id = :id FOR UPDATE');
        $find->execute(['id' => $id]);
        $candidate = $find->fetch();

        if (!$candidate) throw new RuntimeException('Không tìm thấy thí sinh.');

        $currentMiddleSchoolId = $candidate['middle_school_id'] !== null
            ? (int)$candidate['middle_school_id']
            : 0;

        $newMiddleSchoolId = (int)($data['middle_school_id'] ?? 0);

        if ($currentMiddleSchoolId > 0 && $newMiddleSchoolId <= 0) {
            throw new InvalidArgumentException(
                'Không thể bỏ trường THCS đã có trong hồ sơ.'
            );
        }

        if ($newMiddleSchoolId > 0 && $newMiddleSchoolId !== $currentMiddleSchoolId) {
            $schoolCheck = $pdo->prepare(
                'SELECT id
                FROM schools
                WHERE id=:id
                AND level=:level
                AND active=:active
                LIMIT 1
                FOR UPDATE'
            );

            $schoolCheck->execute([
                'id' => $newMiddleSchoolId,
                'level' => 'THCS',
                'active' => 1,
            ]);

            if (!$schoolCheck->fetchColumn()) {
                throw new InvalidArgumentException(
                    'Trường được chọn mới phải tồn tại, là trường THCS và đang hoạt động.'
                );
            }
        }

        $dup = $pdo->prepare(
            'SELECT id FROM candidates WHERE national_id = :national_id AND id <> :id LIMIT 1 FOR UPDATE'
        );
        $dup->execute([
            'national_id' => $data['national_id'],
            'id' => $id,
        ]);
        if ($dup->fetchColumn()) {
            throw new RuntimeException('Thí sinh/Học sinh đã tồn tại trong hệ thống.');
        }

        $stmt = $pdo->prepare('UPDATE candidates SET national_id=:national_id,full_name=:full_name,date_of_birth=:date_of_birth,gender=:gender,address=:address,phone=:phone,email=:email,middle_school_id=:middle_school_id,eligible=:eligible,status=:status WHERE id=:id');
        foreach (['date_of_birth', 'gender', 'address', 'phone', 'email'] as $k)
            if (($data[$k] ?? '') === '')
                $data[$k] = null;
        $data['middle_school_id'] = $data['middle_school_id'] > 0 ? $data['middle_school_id'] : null;
        $data['id'] = $id;
        $stmt->execute($data);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        throw $e;
    }
}

function candidate_has_dependencies(int $id): bool
{
    $pdo = db();
    $checks = [
        'SELECT 1 FROM aspirations WHERE candidate_id = :id LIMIT 1',
        'SELECT 1 FROM exam_allocations WHERE candidate_id = :id LIMIT 1',
        'SELECT 1 FROM room_assignments WHERE candidate_id = :id LIMIT 1',
        'SELECT 1 FROM exam_papers WHERE candidate_id = :id LIMIT 1',
        'SELECT 1 FROM scores sc INNER JOIN exam_papers p ON p.id = sc.exam_paper_id WHERE p.candidate_id = :id LIMIT 1',
        'SELECT 1 FROM admission_results WHERE candidate_id = :id LIMIT 1',
        'SELECT 1 FROM appeal_requests WHERE candidate_id = :id LIMIT 1',
        'SELECT 1 FROM enrollment_confirmations WHERE candidate_id = :id LIMIT 1',
        'SELECT 1 FROM adjustment_requests WHERE candidate_id = :id LIMIT 1',
        'SELECT 1 FROM candidate_priority_points WHERE candidate_id = :id LIMIT 1',
    ];
    foreach ($checks as $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        if ($stmt->fetchColumn())
            return true;
    }
    return false;
}

function candidate_deactivate(int $id): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $find = $pdo->prepare('SELECT id,user_id,status FROM candidates WHERE id = :id FOR UPDATE');
        $find->execute(['id' => $id]);
        $candidate = $find->fetch();
        if (!$candidate)
            throw new RuntimeException('Không tìm thấy thí sinh.');
        if ($candidate['status'] !== 'active')
            throw new RuntimeException('Thí sinh/Học sinh đã không hoạt động.');

        if (candidate_has_dependencies($id)) {
            throw new RuntimeException('Không thể xóa thí sinh/học sinh do đang có dữ liệu liên quan.');
        }

        $update = $pdo->prepare('UPDATE candidates SET status="inactive" WHERE id=:id AND status="active"');
        $update->execute(['id' => $id]);
        if ($update->rowCount() !== 1)
            throw new RuntimeException('Không thể xóa dữ liệu.');

        if (!empty($candidate['user_id'])) {
            $deleteUser = $pdo->prepare('DELETE FROM users WHERE id = :user_id');
            $deleteUser->execute(['user_id' => (int) $candidate['user_id']]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        throw $e;
    }
}

function candidate_set_user(int $candidateId, int $userId): void
{
    $stmt = db()->prepare('UPDATE candidates SET user_id=:user_id WHERE id=:id');
    $stmt->execute(['user_id' => $userId, 'id' => $candidateId]);
}

function adjustment_submit(
    int $candidateId,
    int $userId,
    array $old,
    array $new,
    string $reason,
    ?string $evidencePath
): int {
    $pdo = db();

    /*
     * UC14 — Gửi yêu cầu điều chỉnh hồ sơ
     *
     * Authentication/RBAC được enforce ở endpoint.
     * Service kiểm tra ownership của candidate đối với user truyền vào.
     */

    $candidateStmt = $pdo->prepare(
        'SELECT id,user_id,national_id,full_name,date_of_birth,gender,
                address,phone,email,middle_school_id,eligible,status
         FROM candidates
         WHERE id=:id
         LIMIT 1'
    );

    $candidateStmt->execute([
        'id' => $candidateId,
    ]);

    $candidate = $candidateStmt->fetch();

    if (!$candidate) {
        throw new RuntimeException('Không tìm thấy thí sinh.');
    }

    if ((int) $candidate['user_id'] !== $userId) {
        throw new RuntimeException(
            'Bạn chỉ được gửi yêu cầu điều chỉnh hồ sơ của chính mình.'
        );
    }

    /*
     * TEST 2:
     * new_data phải được validate ngay khi gửi request,
     * không chờ đến bước duyệt.
     */
    [$validatedNew, $errors] = candidate_validate($new);

    if ($errors) {
        throw new InvalidArgumentException(
            'Thông tin điều chỉnh không hợp lệ.'
        );
    }

    /*
     * Không được dùng CMND/CCCD trùng với candidate khác.
     */
    if (
        candidate_duplicate(
            $validatedNew['national_id'],
            $candidateId
        )
    ) {
        throw new InvalidArgumentException(
            'Số CMND/CCCD đã tồn tại.'
        );
    }

    /*
     * Snapshot dữ liệu cũ từ DB.
     * Không tin $old từ client.
     */
    $oldData = [
        'national_id' => $candidate['national_id'],
        'full_name' => $candidate['full_name'],
        'date_of_birth' => $candidate['date_of_birth'] ?? '',
        'gender' => $candidate['gender'] ?? '',
        'address' => $candidate['address'] ?? '',
        'phone' => $candidate['phone'] ?? '',
        'email' => $candidate['email'] ?? '',
        'middle_school_id' => (int) ($candidate['middle_school_id'] ?? 0),
        'eligible' => (int) ($candidate['eligible'] ?? 1),
        'status' => $candidate['status'] ?? 'active',
    ];

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO adjustment_requests
                (
                    candidate_id,
                    submitted_by_user_id,
                    old_data,
                    new_data,
                    reason,
                    evidence_path,
                    status
                )
             VALUES
                (
                    :candidate_id,
                    :submitted_by_user_id,
                    :old_data,
                    :new_data,
                    :reason,
                    :evidence_path,
                    "pending"
                )'
        );

        $stmt->execute([
            'candidate_id' => $candidateId,
            'submitted_by_user_id' => $userId,
            'old_data' => json_encode(
                $oldData,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            'new_data' => json_encode(
                $validatedNew,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            'reason' => $reason,
            'evidence_path' => $evidencePath,
        ]);

        $id = (int) $pdo->lastInsertId();

        $pdo->commit();

        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
function adjustment_list(string $status = ''): array
{
    $sql =
        'SELECT a.*,c.full_name,c.national_id
         FROM adjustment_requests a
         JOIN candidates c ON c.id=a.candidate_id';

    $params = [];

    if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $sql .= ' WHERE a.status=:status';
        $params['status'] = $status;
    }

    $sql .= ' ORDER BY a.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
function adjustment_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT *
         FROM adjustment_requests
         WHERE id=:id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => $id,
    ]);

    $r = $stmt->fetch();

    return $r ?: null;
}


function adjustment_review(
    int $id,
    int $reviewerId,
    bool $approve,
    string $reason = ''
): void {
    $pdo = db();

    $pdo->beginTransaction();

    try {
        /*
         * UC13 — Duyệt yêu cầu điều chỉnh hồ sơ
         *
         * Lock request để chống xử lý lần 2.
         */
        $stmt = $pdo->prepare(
            'SELECT *
             FROM adjustment_requests
             WHERE id=:id
             FOR UPDATE'
        );

        $stmt->execute([
            'id' => $id,
        ]);

        $a = $stmt->fetch();

        if (!$a) {
            throw new RuntimeException(
                'Không tìm thấy yêu cầu.'
            );
        }

        if ($a['status'] !== 'pending') {
            throw new RuntimeException(
                'Yêu cầu đã được xử lý.'
            );
        }

        if (!$approve && trim($reason) === '') {
            throw new InvalidArgumentException(
                'Lý do từ chối là bắt buộc.'
            );
        }

        if ($approve) {
            $candidateLock = $pdo->prepare(
                'SELECT id, middle_school_id
                FROM candidates
                WHERE id=:id
                FOR UPDATE'
            );

            $candidateLock->execute([
                'id' => (int)$a['candidate_id'],
            ]);

            $candidate = $candidateLock->fetch();

            if (!$candidate) {
                throw new RuntimeException('Không tìm thấy thí sinh.');
            }
            $new = json_decode(
                $a['new_data'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            [$data, $errors] = candidate_validate($new);

            if ($errors) {
                throw new InvalidArgumentException(
                    'Thông tin điều chỉnh không hợp lệ.'
                );
            }

            if (
                candidate_duplicate(
                    $data['national_id'],
                    (int) $a['candidate_id']
                )
            ) {
                throw new InvalidArgumentException(
                    'Số CMND/CCCD đã tồn tại.'
                );
            }

            $currentMiddleSchoolId = $candidate['middle_school_id'] !== null
                ? (int)$candidate['middle_school_id']
                : 0;

            $newMiddleSchoolId = (int)($data['middle_school_id'] ?? 0);

            if ($currentMiddleSchoolId > 0 && $newMiddleSchoolId <= 0) {
                throw new InvalidArgumentException(
                    'Không thể bỏ trường THCS đã có trong hồ sơ.'
                );
            }

            if ($newMiddleSchoolId > 0 && $newMiddleSchoolId !== $currentMiddleSchoolId) {
                $schoolCheck = $pdo->prepare(
                    'SELECT id
                    FROM schools
                    WHERE id=:id
                    AND level=:level
                    AND active=:active
                    LIMIT 1
                    FOR UPDATE'
                );

                $schoolCheck->execute([
                    'id' => $newMiddleSchoolId,
                    'level' => 'THCS',
                    'active' => 1,
                ]);

                if (!$schoolCheck->fetchColumn()) {
                    throw new InvalidArgumentException(
                        'Trường được yêu cầu phải tồn tại, là trường THCS và đang hoạt động.'
                    );
                }
            }

            $u = $pdo->prepare(
                'UPDATE candidates
                 SET
                    national_id=:national_id,
                    full_name=:full_name,
                    date_of_birth=:date_of_birth,
                    gender=:gender,
                    address=:address,
                    phone=:phone,
                    email=:email,
                    middle_school_id=:middle_school_id
                 WHERE id=:id'
            );

            $u->execute([
                'national_id' => $data['national_id'],
                'full_name' => $data['full_name'],
                'date_of_birth' =>
                    $data['date_of_birth'] ?: null,
                'gender' =>
                    $data['gender'] ?: null,
                'address' =>
                    $data['address'] ?: null,
                'phone' =>
                    $data['phone'] ?: null,
                'email' =>
                    $data['email'] ?: null,
                'middle_school_id' =>
                    $data['middle_school_id'] > 0
                    ? $data['middle_school_id']
                    : null,
                'id' => $a['candidate_id'],
            ]);

            $status = 'approved';
        } else {
            $status = 'rejected';
        }

        $u = $pdo->prepare(
            'UPDATE adjustment_requests
             SET
                status=:status,
                reviewed_by_user_id=:reviewer,
                review_reason=:reason,
                reviewed_at=CURRENT_TIMESTAMP
             WHERE id=:id'
        );

        $u->execute([
            'status' => $status,
            'reviewer' => $reviewerId,
            'reason' =>
                trim($reason) !== ''
                ? trim($reason)
                : null,
            'id' => $id,
        ]);

        /*
         * Notification dùng đúng schema hiện có:
         * notifications(user_id,title,message)
         *
         * Notification nằm trong cùng transaction với
         * candidate + adjustment_request.
         */
        $recipient = (int) $a['submitted_by_user_id'];

        if ($recipient > 0) {
            $notify = $pdo->prepare(
                'INSERT INTO notifications
                    (user_id,title,message)
                 VALUES
                    (:user_id,:title,:message)'
            );

            if ($approve) {
                $notify->execute([
                    'user_id' => $recipient,
                    'title' =>
                        'Yêu cầu điều chỉnh hồ sơ đã được duyệt',
                    'message' =>
                        'Yêu cầu điều chỉnh hồ sơ của bạn đã được duyệt.',
                ]);
            } else {
                $notify->execute([
                    'user_id' => $recipient,
                    'title' =>
                        'Yêu cầu điều chỉnh hồ sơ bị từ chối',
                    'message' =>
                        'Yêu cầu điều chỉnh hồ sơ của bạn bị từ chối. Lý do: '
                        . trim($reason),
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
function aspiration_get(
    int $candidateId,
    int $periodId
): array {
    $stmt = db()->prepare(
        'SELECT
            a.*,
            s.school_code,
            s.name
         FROM aspirations a
         JOIN schools s
           ON s.id=a.school_id
         WHERE
            a.candidate_id=:candidate_id
            AND a.admission_period_id=:period_id
         ORDER BY a.priority_order'
    );

    $stmt->execute([
        'candidate_id' => $candidateId,
        'period_id' => $periodId,
    ]);

    return $stmt->fetchAll();
}
function aspiration_save(
    int $candidateId,
    int $periodId,
    array $schoolIds,
    int $userId
): void {
    /*
     * UC15 — Đăng ký/quản lý nguyện vọng
     *
     * Ownership:
     * candidate.user_id phải chính là authenticated user.
     */
    $schoolIds = array_values(
        array_filter(
            array_map('intval', $schoolIds),
            fn($v) => $v > 0
        )
    );

    if (!$schoolIds) {
        throw new InvalidArgumentException(
            'Phải chọn ít nhất một nguyện vọng.'
        );
    }

    if (
        count($schoolIds) !==
        count(array_unique($schoolIds))
    ) {
        throw new InvalidArgumentException(
            'Không được chọn trùng trường.'
        );
    }

    $pdo = db();

    $owner = $pdo->prepare(
        'SELECT id
         FROM candidates
         WHERE id=:candidate_id
           AND user_id=:user_id
         LIMIT 1'
    );

    $owner->execute([
        'candidate_id' => $candidateId,
        'user_id' => $userId,
    ]);

    if (!$owner->fetchColumn()) {
        throw new RuntimeException(
            'Bạn chỉ được quản lý nguyện vọng của chính mình.'
        );
    }

    $pdo->beginTransaction();

    try {
        $p = $pdo->prepare(
            'SELECT
                registration_start,
                registration_end,
                status
             FROM admission_periods
             WHERE id=:id'
        );

        $p->execute([
            'id' => $periodId,
        ]);

        $period = $p->fetch();

        if (!$period) {
            throw new RuntimeException(
                'Không tìm thấy kỳ tuyển sinh.'
            );
        }

        $now = new DateTimeImmutable('now');

        if (
            $period['registration_start'] !== null
            && $now < new DateTimeImmutable(
                $period['registration_start']
            )
        ) {
            throw new RuntimeException(
                'Chưa đến thời gian đăng ký nguyện vọng.'
            );
        }

        if (
            $period['registration_end'] !== null
            && $now > new DateTimeImmutable(
                $period['registration_end']
            )
        ) {
            throw new RuntimeException(
                'Đã hết thời gian đăng ký nguyện vọng.'
            );
        }

        if ($period['status'] === 'closed') {
            throw new RuntimeException(
                'Kỳ tuyển sinh đã đóng.'
            );
        }

        /*
         * Xóa toàn bộ NV hiện tại của candidate + period
         * trong cùng transaction.
         */
        $delete = $pdo->prepare(
            'DELETE FROM aspirations
             WHERE candidate_id=:candidate_id
               AND admission_period_id=:period_id'
        );

        $delete->execute([
            'candidate_id' => $candidateId,
            'period_id' => $periodId,
        ]);

        $schoolStmt = $pdo->prepare(
            'SELECT id
             FROM schools
             WHERE id=:id
               AND level="THPT"
               AND active=1
             LIMIT 1'
        );

        $insert = $pdo->prepare(
            'INSERT INTO aspirations
                (
                    candidate_id,
                    admission_period_id,
                    school_id,
                    priority_order
                )
             VALUES
                (
                    :candidate_id,
                    :period_id,
                    :school_id,
                    :priority_order
                )'
        );

        /*
         * Thứ tự array chính là priority:
         * phần tử đầu = NV1,
         * phần tử thứ hai = NV2, ...
         */
        foreach ($schoolIds as $index => $schoolId) {
            $schoolStmt->execute([
                'id' => $schoolId,
            ]);

            if (!$schoolStmt->fetchColumn()) {
                throw new InvalidArgumentException(
                    'Trường được chọn không hợp lệ.'
                );
            }

            $insert->execute([
                'candidate_id' => $candidateId,
                'period_id' => $periodId,
                'school_id' => $schoolId,
                'priority_order' => $index + 1,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
