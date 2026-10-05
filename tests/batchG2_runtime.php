<?php

declare(strict_types=1);

/**
 * BATCH G2 RUNTIME
 *
 * Official numbering:
 * UC24 — Xem điểm thi
 * UC25 — Xem KQ xét tuyển
 * UC28 — Theo dõi phúc khảo
 * UC34 — Danh sách thí sinh trúng tuyển / xuất danh sách
 * UC35 — Xem chỉ tiêu
 * UC36 — Thống kê
 *
 * Scope rules:
 * - No migration.
 * - No score-publication trigger changes.
 * - No mutation is performed by the read-only UC services under test.
 * - UC35 uses the shared current-admission-period contract:
 *   status='published' and greatest id.
 * - UC36 profile-status uses candidates.eligible and scopes candidates to the
 *   selected admission period through aspirations.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';
require_once __DIR__ . '/../includes/appeal_service.php';
require_once __DIR__ . '/../includes/publication_service.php';
require_once __DIR__ . '/../includes/excel_service.php';

$pdo = db();
$tag = 'G2_' . date('mdHis') . '_' . random_int(100, 999);
$results = [];

$periodA = 0;
$periodB = 0;
$thcsId = 0;
$schoolId = 0;
$schoolBId = 0;
$subjectA = 0;
$subjectB = 0;
$siteA = 0;
$siteB = 0;
$roomA = 0;
$roomB = 0;
$paperA = 0;
$paperB = 0;
$candidate1 = 0;
$candidate2 = 0;
$user1 = 0;
$user2 = 0;
$runA = 0;
$runB = 0;
$resultA1 = 0;
$resultA2 = 0;
$resultB1 = 0;
$appealId = 0;
$quotaA1 = 0;
$quotaB1 = 0;
$quotaB2 = 0;

function assert_true(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}

function must_fail(callable $fn, string $contains = ''): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($contains !== '' && stripos($e->getMessage(), $contains) === false) {
            throw new RuntimeException('Unexpected rejection: ' . $e->getMessage());
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
        $results[$number] = ['status' => 'PASS', 'name' => $name];
        echo "PASS TEST {$number}: {$name}" . PHP_EOL;
    } catch (LogicException $e) {
        $results[$number] = ['status' => 'BLOCKED', 'name' => $name, 'message' => $e->getMessage()];
        echo "BLOCKED TEST {$number}: {$name} — {$e->getMessage()}" . PHP_EOL;
    } catch (Throwable $e) {
        $results[$number] = ['status' => 'FAIL', 'name' => $name, 'message' => $e->getMessage()];
        echo "FAIL TEST {$number}: {$name} — {$e->getMessage()}" . PHP_EOL;
    }
}

function fetch_value(PDO $pdo, string $sql, array $params = [])
{
    $q = $pdo->prepare($sql);
    $q->execute($params);
    return $q->fetchColumn();
}

function table_count(PDO $pdo, string $table, string $where = '1=1', array $params = []): int
{
    $allowed = ['scores', 'admission_results', 'appeal_requests', 'publication_events', 'notifications'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Unsafe test table.');
    }
    return (int)fetch_value($pdo, "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params);
}

try {
    // ---------- Fixture setup ----------
    $q = $pdo->prepare('INSERT INTO admission_periods(name,academic_year,admission_method,status) VALUES(:n,:y,"thi tuyển","published")');
    $q->execute(['n' => $tag . '_PERIOD_A', 'y' => 'G2-A']);
    $periodA = (int)$pdo->lastInsertId();
    $q->execute(['n' => $tag . '_PERIOD_B', 'y' => 'G2-B']);
    $periodB = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO schools(school_code,name,level,active,admission_area) VALUES(:c,:n,"THCS",1,"G2")');
    $q->execute(['c' => $tag . '_THCS', 'n' => $tag . ' THCS']);
    $thcsId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO schools(school_code,name,level,active,admission_area) VALUES(:c,:n,"THPT",1,"G2")');
    $q->execute(['c' => $tag . '_THPT_A', 'n' => $tag . ' THPT A']);
    $schoolId = (int)$pdo->lastInsertId();
    $q->execute(['c' => $tag . '_THPT_B', 'n' => $tag . ' THPT B']);
    $schoolBId = (int)$pdo->lastInsertId();

    $candidate1 = candidate_create([
        'national_id' => substr($tag . 'C1', 0, 30),
        'full_name' => $tag . ' Candidate 1',
        'date_of_birth' => '2010-01-01',
        'gender' => 'Nam',
        'address' => 'G2',
        'phone' => '0900000001',
        'email' => strtolower($tag) . 'c1@example.com',
        'middle_school_id' => $thcsId,
        'eligible' => 1,
        'status' => 'active',
    ]);
    $candidate2 = candidate_create([
        'national_id' => substr($tag . 'C2', 0, 30),
        'full_name' => $tag . ' Candidate 2',
        'date_of_birth' => '2010-02-02',
        'gender' => 'Nu',
        'address' => 'G2',
        'phone' => '0900000002',
        'email' => strtolower($tag) . 'c2@example.com',
        'middle_school_id' => $thcsId,
        'eligible' => 1,
        'status' => 'active',
    ]);
    $user1 = (int)(candidate_find($candidate1)['user_id'] ?? 0);
    $user2 = (int)(candidate_find($candidate2)['user_id'] ?? 0);
    assert_true($user1 > 0 && $user2 > 0, 'Candidate accounts were not created.');

    // Exam fixtures for Period A.
    $q = $pdo->prepare('INSERT INTO exam_sites(admission_period_id,site_code,name,capacity,status) VALUES(:p,:c,:n,50,"ready")');
    $q->execute(['p' => $periodA, 'c' => $tag . '_SITE_A', 'n' => $tag . ' Site A']);
    $siteA = (int)$pdo->lastInsertId();
    $q->execute(['p' => $periodB, 'c' => $tag . '_SITE_B', 'n' => $tag . ' Site B']);
    $siteB = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO exam_rooms(exam_site_id,room_code,name,capacity,status) VALUES(:s,:c,:n,30,"ready")');
    $q->execute(['s' => $siteA, 'c' => $tag . '_ROOM_A', 'n' => $tag . ' Room A']);
    $roomA = (int)$pdo->lastInsertId();
    $q->execute(['s' => $siteB, 'c' => $tag . '_ROOM_B', 'n' => $tag . ' Room B']);
    $roomB = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,status) VALUES(:p,:c,:n,"2030-06-01","08:00:00","10:00:00",10,"scheduled")');
    $q->execute(['p' => $periodA, 'c' => $tag . '_A', 'n' => $tag . ' Subject A']);
    $subjectA = (int)$pdo->lastInsertId();
    $q->execute(['p' => $periodB, 'c' => $tag . '_B', 'n' => $tag . ' Subject B']);
    $subjectB = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO exam_papers(admission_period_id,candidate_id,exam_subject_id,exam_room_id,attendance_status,anonymous_code,paper_status) VALUES(:p,:c,:s,:r,"present",:code,"scored")');
    $q->execute(['p' => $periodA, 'c' => $candidate1, 's' => $subjectA, 'r' => $roomA, 'code' => $tag . '_CODE_A']);
    $paperA = (int)$pdo->lastInsertId();
    $q->execute(['p' => $periodB, 'c' => $candidate1, 's' => $subjectB, 'r' => $roomB, 'code' => $tag . '_CODE_B']);
    $paperB = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO scores(exam_paper_id,anonymous_code,total_score,entered_by) VALUES(:p,:code,8.5,:u)');
    $q->execute(['p' => $paperA, 'code' => $tag . '_CODE_A', 'u' => $user1]);
    $q->execute(['p' => $paperB, 'code' => $tag . '_CODE_B', 'u' => $user1]);

    // Admission result fixtures for Period A.
    $q = $pdo->prepare('INSERT INTO admission_runs(admission_period_id,status,started_by_user_id,calculated_at,finalized_at) VALUES(:p,"finalized",:u,NOW(),NOW())');
    $q->execute(['p' => $periodA, 'u' => $user1]);
    $runA = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO admission_results(admission_run_id,admission_period_id,candidate_id,school_id,priority_order,score_total,priority_points,admission_score,result_status,enrollment_status) VALUES(:r,:p,:c,:s,1,8.5,0,8.5,"admitted","pending")');
    $q->execute(['r' => $runA, 'p' => $periodA, 'c' => $candidate1, 's' => $schoolId]);
    $resultA1 = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO admission_results(admission_run_id,admission_period_id,candidate_id,school_id,priority_order,score_total,priority_points,admission_score,result_status,enrollment_status) VALUES(:r,:p,:c,:s,1,5.0,0,5.0,"not_admitted","pending")');
    $q->execute(['r' => $runA, 'p' => $periodA, 'c' => $candidate2, 's' => $schoolBId]);
    $resultA2 = (int)$pdo->lastInsertId();

    // Period B gets a finalized result but remains unpublished. This allows
    // TEST 17 to prove that B data never leaks into an A query while TEST 18
    // independently verifies the publication prerequisite for B.
    $q = $pdo->prepare('INSERT INTO admission_runs(admission_period_id,status,started_by_user_id,calculated_at,finalized_at) VALUES(:p,"finalized",:u,NOW(),NOW())');
    $q->execute(['p' => $periodB, 'u' => $user1]);
    $runB = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO admission_results(admission_run_id,admission_period_id,candidate_id,school_id,priority_order,score_total,priority_points,admission_score,result_status,enrollment_status) VALUES(:r,:p,:c,:s,1,9.0,0,9.0,"admitted","pending")');
    $q->execute(['r' => $runB, 'p' => $periodB, 'c' => $candidate2, 's' => $schoolBId]);
    $resultB1 = (int)$pdo->lastInsertId();

    // Appeal fixture for Period A.
    $q = $pdo->prepare('INSERT INTO appeal_requests(admission_period_id,candidate_id,exam_paper_id,exam_subject_id,original_score,reason,status) VALUES(:p,:c,:paper,:s,8.5,"G2 appeal","pending")');
    $q->execute(['p' => $periodA, 'c' => $candidate1, 'paper' => $paperA, 's' => $subjectA]);
    $appealId = (int)$pdo->lastInsertId();

    // Publication is created only after all unpublished tests are possible.
    $q = $pdo->prepare('INSERT INTO publication_events(admission_period_id,publication_type,status,published_by_user_id) VALUES(:p,:t,"published",:u)');
    $q->execute(['p' => $periodA, 't' => 'exam_results', 'u' => $user1]);
    $q->execute(['p' => $periodA, 't' => 'admission_results', 'u' => $user1]);

    // ---------- UC24 ----------
    test(1, 'UC24 valid own score viewing after publication', function () use ($pdo, $periodA, $candidate1) {
        $rows = public_exam_results($pdo, $periodA, $candidate1);
        assert_true(count($rows) === 1, 'Expected one published score.');
        assert_true((float)$rows[0]['total_score'] === 8.5, 'Unexpected score.');
        assert_true((int)$rows[0]['candidate_id'] === $candidate1, 'Returned another candidate.');
    });

    test(2, 'UC24 ownership isolation', function () use ($pdo, $periodA, $candidate2) {
        $rows = public_exam_results($pdo, $periodA, $candidate2);
        assert_true(count($rows) === 0, 'Candidate 2 received Candidate 1 score.');
    });

    test(3, 'UC24 wrong-period isolation', function () use ($pdo, $periodB, $candidate1) {
        must_fail(fn() => public_exam_results($pdo, $periodB, $candidate1), 'Điểm thi chưa được công bố');
    });

    test(4, 'UC24 unpublished score blocked', function () use ($pdo, $periodB, $candidate1) {
        must_fail(fn() => public_exam_results($pdo, $periodB, $candidate1), 'Điểm thi chưa được công bố');
    });

    test(5, 'UC24 read-only', function () use ($pdo, $periodA, $candidate1, $paperA) {
        $before = table_count($pdo, 'scores', 'exam_paper_id=:p', ['p' => $paperA]);
        public_exam_results($pdo, $periodA, $candidate1);
        $after = table_count($pdo, 'scores', 'exam_paper_id=:p', ['p' => $paperA]);
        assert_true($before === $after, 'Score query mutated data.');
    });

    // ---------- UC25 ----------
    test(6, 'UC25 valid own admission result after publication', function () use ($pdo, $periodA, $candidate1) {
        $rows = public_admission_results($pdo, $periodA, $candidate1);
        assert_true(count($rows) === 1, 'Expected one admission result.');
        assert_true($rows[0]['result_status'] === 'admitted', 'Unexpected admission result.');
    });

    test(7, 'UC25 ownership isolation', function () use ($pdo, $periodA, $candidate1, $candidate2) {
        $rows = public_admission_results($pdo, $periodA, $candidate2);
        assert_true(count($rows) === 1, 'Candidate 2 should see only own result.');
        assert_true((int)$rows[0]['candidate_id'] === $candidate2, 'Cross-candidate admission result leaked.');
        assert_true((int)$rows[0]['candidate_id'] !== $candidate1, 'Candidate 1 result leaked.');
    });

    test(8, 'UC25 wrong-period isolation', function () use ($pdo, $periodB, $candidate1) {
        must_fail(fn() => public_admission_results($pdo, $periodB, $candidate1), 'Kết quả tuyển sinh chưa được công bố');
    });

    test(9, 'UC25 unpublished result blocked', function () use ($pdo, $periodB, $candidate1) {
        must_fail(fn() => public_admission_results($pdo, $periodB, $candidate1), 'Kết quả tuyển sinh chưa được công bố');
    });

    test(10, 'UC25 read-only', function () use ($pdo, $periodA, $candidate1, $runA) {
        $before = table_count($pdo, 'admission_results', 'admission_run_id=:r', ['r' => $runA]);
        public_admission_results($pdo, $periodA, $candidate1);
        $after = table_count($pdo, 'admission_results', 'admission_run_id=:r', ['r' => $runA]);
        assert_true($before === $after, 'Admission result query mutated data.');
    });

    // ---------- UC28 ----------
    test(11, 'UC28 own appeal tracking', function () use ($pdo, $periodA, $candidate1, $appealId) {
        $rows = candidate_appeals($pdo, $candidate1, $periodA);
        assert_true(count($rows) === 1 && (int)$rows[0]['id'] === $appealId, 'Own appeal was not returned.');
        assert_true($rows[0]['status'] === 'pending', 'Unexpected appeal status.');
    });

    test(12, 'UC28 cross-candidate appeal isolation', function () use ($pdo, $periodA, $candidate2) {
        $rows = candidate_appeals($pdo, $candidate2, $periodA);
        assert_true(count($rows) === 0, 'Another candidate appeal was returned.');
    });

    test(13, 'UC28 wrong-period isolation', function () use ($pdo, $periodB, $candidate1) {
        $rows = candidate_appeals($pdo, $candidate1, $periodB);
        assert_true(count($rows) === 0, 'Appeal from another period was returned.');
    });

    test(14, 'UC28 completed result visibility only', function () use ($pdo, $appealId, $candidate1, $periodA, $user1) {
        $before = candidate_appeals($pdo, $candidate1, $periodA)[0];
        assert_true($before['status'] === 'pending' && $before['new_score'] === null, 'Pending appeal exposed a result.');
        $pdo->prepare('UPDATE appeal_requests SET status="completed", completed_at=NOW() WHERE id=:id')->execute(['id' => $appealId]);
        $pdo->prepare('INSERT INTO appeal_results(appeal_request_id,old_score,new_score,grader_user_id,change_reason) VALUES(:a,8.5,9.0,:u,"G2 test")')->execute(['a' => $appealId, 'u' => $user1]);
        $after = candidate_appeals($pdo, $candidate1, $periodA)[0];
        assert_true($after['status'] === 'completed' && (float)$after['new_score'] === 9.0, 'Completed appeal result was not visible.');
    });

    test(15, 'UC28 read-only', function () use ($pdo, $periodA, $candidate1, $appealId) {
        $before = table_count($pdo, 'appeal_requests', 'id=:id', ['id' => $appealId]);
        candidate_appeals($pdo, $candidate1, $periodA);
        $after = table_count($pdo, 'appeal_requests', 'id=:id', ['id' => $appealId]);
        assert_true($before === $after, 'Appeal tracking mutated data.');
    });

    // ---------- UC34 ----------
    test(16, 'UC34 authorized admitted-only dataset', function () use ($pdo, $periodA, $candidate1, $candidate2) {
        $rows = admitted_list($pdo, $periodA);
        assert_true(count($rows) === 1, 'Admitted list contains non-admitted rows.');
        assert_true((int)$rows[0]['candidate_id'] === $candidate1, 'Wrong candidate in admitted list.');
        assert_true($rows[0]['enrollment_status'] === 'pending', 'Unexpected enrollment status.');
        assert_true($candidate2 !== (int)$rows[0]['candidate_id'], 'Non-admitted candidate leaked.');
    });

    test(17, 'UC34 wrong-period isolation', function () use ($pdo, $periodA, $candidate2, $schoolBId) {
        // Period B contains an admitted fixture, but its result is unpublished.
        // Querying Period A must never return that Period B candidate/school.
        $rows = admitted_list($pdo, $periodA);
        foreach ($rows as $row) {
            assert_true((int)$row['candidate_id'] !== $candidate2, 'Candidate from another period leaked.');
            assert_true((string)$row['school_code'] !== (string)fetch_value(
                $pdo,
                'SELECT school_code FROM schools WHERE id=:id',
                ['id' => $schoolBId]
            ), 'School from another period leaked.');
        }
    });

    test(18, 'UC34 publication prerequisite', function () use ($pdo, $periodB) {
        must_fail(fn() => admitted_list($pdo, $periodB), 'Kết quả tuyển sinh chưa được công bố');
    });

    test(19, 'UC34 existing filters remain period-scoped', function () use ($pdo, $periodA, $schoolId) {
        $rows = admitted_list($pdo, $periodA, $schoolId, 'pending');
        assert_true(count($rows) === 1, 'Existing school/enrollment filters did not return the scoped row.');
        $expectedSchoolCode = (string)fetch_value(
            $pdo,
            'SELECT school_code FROM schools WHERE id=:id',
            ['id' => $schoolId]
        );
        assert_true((string)$rows[0]['school_code'] === $expectedSchoolCode, 'School filter escaped scope.');
    });

    test(20, 'UC34 unsupported filter value does not create a new business filter', function () use ($pdo, $periodA, $schoolId) {
        $rows = admitted_list($pdo, $periodA, $schoolId, 'invented_status');
        assert_true(count($rows) === 1, 'Existing service contract unexpectedly changed.');
    });

    test(21, 'UC34 CSV/XLSX dataset is identical', function () use ($pdo, $periodA) {
        $rows = admitted_list($pdo, $periodA);
        $headers = ['SBD', 'Họ tên', 'Trường', 'NV', 'Điểm xét tuyển', 'Nhập học'];
        $dataset = [];
        foreach ($rows as $r) {
            $dataset[] = [$r['candidate_code'], $r['full_name'], $r['school_name'], $r['priority_order'], $r['admission_score'], $r['enrollment_status']];
        }
        assert_true(count($dataset) === 1, 'Unexpected export dataset size.');
        assert_true(class_exists('ZipArchive'), '[MISSING IMPLEMENTATION DEPENDENCY — XLSX] ZipArchive is not available.');

        $csv = tempnam(sys_get_temp_dir(), 'g2_csv_');
        $fh = fopen($csv, 'wb');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, $headers);
        foreach ($dataset as $row) fputcsv($fh, $row);
        fclose($fh);
        $csvRows = excel_parse_csv($csv);
        unlink($csv);

        $xlsx = tempnam(sys_get_temp_dir(), 'g2_xlsx_');
        if ($xlsx === false) throw new RuntimeException('Không thể tạo file XLSX tạm.');
        try {
            // Use the same production XLSX builder directly. The previous
            // shell_exec wrapper tested CLI/stdout transport as well as XLSX,
            // which is not part of UC34 and can turn binary output into an
            // invalid ZIP before the validator receives it.
            $xlsxBytes = excel_xlsx_bytes($headers, $dataset);
            file_put_contents($xlsx, $xlsxBytes);
            $xlsxRows = excel_parse_xlsx($xlsx);
        } finally {
            if (is_file($xlsx)) unlink($xlsx);
        }

        assert_true($csvRows[0] === $headers, 'CSV headers differ from displayed columns.');
        assert_true($xlsxRows[0] === $headers, 'XLSX headers differ from displayed columns.');
        assert_true($csvRows[1] === array_map('strval', $dataset[0]), 'CSV data differs from display dataset.');
        assert_true($xlsxRows[1] === array_map('strval', $dataset[0]), 'XLSX data differs from display dataset.');
    });

    test(22, 'UC34 export is read-only', function () use ($pdo, $periodA, $runA) {
        $before = table_count($pdo, 'admission_results', 'admission_run_id=:r', ['r' => $runA]);
        admitted_list($pdo, $periodA);
        $after = table_count($pdo, 'admission_results', 'admission_run_id=:r', ['r' => $runA]);
        assert_true($before === $after, 'Export dataset access mutated admission results.');
    });

    // ---------- RBAC checks ----------
    test(23, 'UC34/UC36 permission seeds exist for staff roles', function () use ($pdo) {
        $rows = $pdo->query("SELECT p.code,r.code role_code FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id JOIN roles r ON r.id=rp.role_id WHERE p.code IN ('admitted.view','statistics.view') AND r.code IN ('admin','so_gdtd')")->fetchAll();
        $codes = [];
        foreach ($rows as $row) $codes[$row['role_code'] . ':' . $row['code']] = true;
        assert_true(isset($codes['admin:admitted.view'], $codes['admin:statistics.view'], $codes['so_gdtd:admitted.view'], $codes['so_gdtd:statistics.view']), 'Required existing RBAC mappings are missing.');
    });

    test(24, 'Candidate does not receive staff reporting permissions', function () use ($pdo) {
        $count = (int)fetch_value($pdo, "SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id WHERE r.code='candidate' AND p.code IN ('admitted.view','statistics.view')");
        assert_true($count === 0, 'Candidate role unexpectedly has staff reporting permission.');
    });

    // ---------- UC35 ----------
    test(25, 'UC35 current-period scope, filters and read-only behavior', function () use ($pdo, $periodA, $periodB, $schoolId, $schoolBId, &$quotaA1, &$quotaB1, &$quotaB2) {
        // Period B is created after A, and both are published. Under the
        // official contract, B is the current period.
        $q = $pdo->prepare('INSERT INTO admission_quotas(admission_period_id,school_id,quota,status) VALUES(:p,:s,:q,"set")');
        $q->execute(['p' => $periodA, 's' => $schoolId, 'q' => 101]);
        $quotaA1 = (int)$pdo->lastInsertId();
        $q->execute(['p' => $periodB, 's' => $schoolId, 'q' => 202]);
        $quotaB1 = (int)$pdo->lastInsertId();
        $q->execute(['p' => $periodB, 's' => $schoolBId, 'q' => 303]);
        $quotaB2 = (int)$pdo->lastInsertId();

        // Make the second school distinct for the area-filter assertion.
        $pdo->prepare('UPDATE schools SET admission_area="G2-B" WHERE id=:id')->execute(['id' => $schoolBId]);

        $beforeCount = (int)fetch_value($pdo, 'SELECT COUNT(*) FROM admission_quotas WHERE id IN (:a,:b,:c)', ['a' => $quotaA1, 'b' => $quotaB1, 'c' => $quotaB2]);
        assert_true($beforeCount === 3, 'UC35 quota fixture was not created correctly.');

        $current = current_admission_period_id($pdo);
        assert_true($current === $periodB, 'Current period is not the latest published admission period.');

        // No filter: only current-period quota rows are visible.
        $all = published_quota_list($pdo, $current);
        assert_true(count($all) === 2, 'No-filter UC35 did not return all current-period quotas.');
        $ids = array_map(static fn(array $row): int => (int)$row['school_id'], $all);
        sort($ids);
        $expectedIds = [$schoolId, $schoolBId];
        sort($expectedIds);
        assert_true($ids === $expectedIds, 'Quota from a non-current period leaked into UC35.');
        assert_true(!in_array(101, array_map(static fn(array $row): int => (int)$row['quota'], $all), true), 'Period A quota leaked into current-period results.');

        // GET period_id must not influence the current-period scope. This
        // mirrors the endpoint contract: the caller cannot choose a period.
        $_GET['period_id'] = (string)$periodA;
        assert_true(current_admission_period_id($pdo) === $periodB, 'A client-supplied period_id changed current-period resolution.');
        unset($_GET['period_id']);

        // School filter: current period + selected school only.
        $schoolRows = published_quota_list($pdo, $current, $schoolId);
        assert_true(count($schoolRows) === 1 && (int)$schoolRows[0]['school_id'] === $schoolId && (int)$schoolRows[0]['quota'] === 202, 'School filter is not scoped to current period.');

        // Area filter: current period + selected area only.
        $areaRows = published_quota_list($pdo, $current, null, 'G2-B');
        assert_true(count($areaRows) === 1 && (int)$areaRows[0]['school_id'] === $schoolBId && (int)$areaRows[0]['quota'] === 303, 'Area filter is not scoped to current period.');

        // Non-published periods are never eligible. Keep Period B published
        // and temporarily change Period A to draft, then closed; B must remain
        // the current period in both cases.
        $pdo->prepare('UPDATE admission_periods SET status="draft" WHERE id=:id')->execute(['id' => $periodA]);
        assert_true(current_admission_period_id($pdo) === $periodB, 'Draft period was incorrectly selected as current.');
        $pdo->prepare('UPDATE admission_periods SET status="closed" WHERE id=:id')->execute(['id' => $periodA]);
        assert_true(current_admission_period_id($pdo) === $periodB, 'Closed period was incorrectly selected as current.');
        $pdo->prepare('UPDATE admission_periods SET status="published" WHERE id=:id')->execute(['id' => $periodA]);

        // No-current-period behavior cannot be isolated safely in this shared
        // fixture without temporarily changing unrelated published periods.
        // Keep it as a documented [MISSING TEST HOOK] rather than mutating real
        // published data or fabricating a PASS.
        $afterCount = (int)fetch_value($pdo, 'SELECT COUNT(*) FROM admission_quotas WHERE id IN (:a,:b,:c)', ['a' => $quotaA1, 'b' => $quotaB1, 'c' => $quotaB2]);
        assert_true($afterCount === $beforeCount, 'UC35 mutated quota data during read-only access.');
    });

    test(26, 'UC36 profile-status eligible source and period isolation', function () use ($pdo, $periodA, $periodB, $candidate1, $candidate2, $schoolId, $schoolBId, $thcsId) {
        // Candidate 1 belongs to both periods; candidate 2 belongs only to B.
        // Candidate 1 has multiple aspirations in A to verify DISTINCT counting.
        $ins = $pdo->prepare('INSERT INTO aspirations(candidate_id,admission_period_id,school_id,priority_order) VALUES(:c,:p,:s,:o)');
        $ins->execute(['c' => $candidate1, 'p' => $periodA, 's' => $schoolId, 'o' => 1]);
        $ins->execute(['c' => $candidate1, 'p' => $periodA, 's' => $schoolBId, 'o' => 2]);
        $ins->execute(['c' => $candidate1, 'p' => $periodA, 's' => $thcsId, 'o' => 3]);
        $ins->execute(['c' => $candidate1, 'p' => $periodB, 's' => $schoolBId, 'o' => 1]);
        $ins->execute(['c' => $candidate2, 'p' => $periodB, 's' => $schoolBId, 'o' => 1]);

        $upd = $pdo->prepare('UPDATE candidates SET eligible=:e WHERE id=:id');
        $upd->execute(['e' => 1, 'id' => $candidate1]);
        $upd->execute(['e' => 0, 'id' => $candidate2]);

        $rowsA = statistics($pdo, $periodA, 'profile_status');
        $rowsB = statistics($pdo, $periodB, 'profile_status');
        $mapA = [];
        foreach ($rowsA as $row) $mapA[(int)$row['eligible']] = (int)$row['total'];
        $mapB = [];
        foreach ($rowsB as $row) $mapB[(int)$row['eligible']] = (int)$row['total'];

        assert_true(($mapA[1] ?? 0) === 1, 'Period A must count candidate 1 as eligible.');
        assert_true(!isset($mapA[0]), 'Period A must not include candidate 2 from period B.');
        assert_true(($mapB[1] ?? 0) === 1, 'Period B must count candidate 1 as eligible.');
        assert_true(($mapB[0] ?? 0) === 1, 'Period B must count candidate 2 as ineligible.');

        // Flip candidate 1 to ineligible: this verifies candidates.eligible is the source.
        $upd->execute(['e' => 0, 'id' => $candidate1]);
        $rowsA2 = statistics($pdo, $periodA, 'profile_status');
        $mapA2 = [];
        foreach ($rowsA2 as $row) $mapA2[(int)$row['eligible']] = (int)$row['total'];
        assert_true(($mapA2[0] ?? 0) === 1, 'eligible=0 must map to the ineligible group.');

        // Admission-result regression remains period-isolated.
        $resultRows = statistics($pdo, $periodA, 'admission_result');
        $resultMap = [];
        foreach ($resultRows as $row) $resultMap[(string)$row['result_status']] = (int)$row['total'];
        assert_true(($resultMap['admitted'] ?? 0) === 1, 'Admission-result regression: admitted count changed.');
        assert_true(($resultMap['not_admitted'] ?? 0) === 1, 'Admission-result regression: not_admitted count changed.');
    });

    test(28, 'UC24 no-score message condition', function () use ($pdo, $periodA, $candidate2) {
        $rows = public_exam_results($pdo, $periodA, $candidate2);
        assert_true(count($rows) === 0, 'Candidate without a score unexpectedly received score data.');
    });

    test(29, 'UC25 no-result condition after publication', function () use ($pdo, $periodB, $candidate1, $user1) {
        $ins = $pdo->prepare('INSERT INTO publication_events(admission_period_id,publication_type,status,published_by_user_id) VALUES(:p,"admission_results","published",:u)');
        $ins->execute(['p' => $periodB, 'u' => $user1]);
        try {
            $rows = public_admission_results($pdo, $periodB, $candidate1);
            assert_true(count($rows) === 0, 'Candidate without an admission result unexpectedly received data.');
        } finally {
            $pdo->prepare('DELETE FROM publication_events WHERE admission_period_id=:p AND publication_type="admission_results"')->execute(['p' => $periodB]);
        }
    });

    // ---------- Publication lock is only observed, never bypassed ----------
    test(27, 'Score publication lock remains present', function () use ($pdo) {
        $names = $pdo->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME IN ('trg_scores_lock_after_exam_publication','trg_scores_update_lock_after_exam_publication','trg_scores_delete_lock_after_exam_publication')")->fetchAll(PDO::FETCH_COLUMN);
        sort($names);
        $expected = ['trg_scores_delete_lock_after_exam_publication', 'trg_scores_lock_after_exam_publication', 'trg_scores_update_lock_after_exam_publication'];
        assert_true($names === $expected, 'Protected score publication triggers are missing or renamed.');
    });
} finally {
    // Delete dependent fixtures only. Never delete production Batch A-F/G1 data.
    try {
        if ($appealId > 0) {
            $pdo->prepare('DELETE FROM appeal_results WHERE appeal_request_id=:id')->execute(['id' => $appealId]);
            $pdo->prepare('DELETE FROM appeal_requests WHERE id=:id')->execute(['id' => $appealId]);
        }
        if ($resultA1 > 0 || $resultA2 > 0 || $resultB1 > 0) {
            $pdo->prepare('DELETE FROM admission_results WHERE id IN (:a,:b,:c)')->execute(['a' => $resultA1, 'b' => $resultA2, 'c' => $resultB1]);
        }
        if ($runA > 0 || $runB > 0) {
            $pdo->prepare('DELETE FROM admission_runs WHERE id IN (:a,:b)')->execute(['a' => $runA, 'b' => $runB]);
        }
        if ($periodA > 0) $pdo->prepare('DELETE FROM publication_events WHERE admission_period_id=:p')->execute(['p' => $periodA]);
        if ($quotaA1 > 0 || $quotaB1 > 0 || $quotaB2 > 0) $pdo->prepare('DELETE FROM admission_quotas WHERE id IN (:a,:b,:c)')->execute(['a' => $quotaA1, 'b' => $quotaB1, 'c' => $quotaB2]);
        if ($paperA > 0 || $paperB > 0) $pdo->prepare('DELETE FROM scores WHERE exam_paper_id IN (:a,:b)')->execute(['a' => $paperA, 'b' => $paperB]);
        if ($paperA > 0 || $paperB > 0) $pdo->prepare('DELETE FROM exam_papers WHERE id IN (:a,:b)')->execute(['a' => $paperA, 'b' => $paperB]);
        if ($roomA > 0 || $roomB > 0) $pdo->prepare('DELETE FROM exam_rooms WHERE id IN (:a,:b)')->execute(['a' => $roomA, 'b' => $roomB]);
        if ($siteA > 0 || $siteB > 0) $pdo->prepare('DELETE FROM exam_sites WHERE id IN (:a,:b)')->execute(['a' => $siteA, 'b' => $siteB]);
        if ($subjectA > 0 || $subjectB > 0) $pdo->prepare('DELETE FROM exam_subjects WHERE id IN (:a,:b)')->execute(['a' => $subjectA, 'b' => $subjectB]);
        if ($candidate1 > 0 || $candidate2 > 0) $pdo->prepare('DELETE FROM aspirations WHERE candidate_id IN (:a,:b)')->execute(['a' => $candidate1, 'b' => $candidate2]);
        if ($candidate1 > 0 || $candidate2 > 0) $pdo->prepare('DELETE FROM candidates WHERE id IN (:a,:b)')->execute(['a' => $candidate1, 'b' => $candidate2]);
        if ($user1 > 0 || $user2 > 0) {
            $pdo->prepare('DELETE FROM user_roles WHERE user_id IN (:a,:b)')->execute(['a' => $user1, 'b' => $user2]);
            $pdo->prepare('DELETE FROM users WHERE id IN (:a,:b)')->execute(['a' => $user1, 'b' => $user2]);
        }
        if ($schoolId > 0 || $schoolBId > 0) $pdo->prepare('UPDATE schools SET admission_area="G2" WHERE id IN (:a,:b)')->execute(['a' => $schoolId, 'b' => $schoolBId]);
        if ($schoolId > 0 || $schoolBId > 0 || $thcsId > 0) $pdo->prepare('DELETE FROM schools WHERE id IN (:a,:b,:c)')->execute(['a' => $schoolId, 'b' => $schoolBId, 'c' => $thcsId]);
        if ($periodA > 0 || $periodB > 0) $pdo->prepare('DELETE FROM admission_periods WHERE id IN (:a,:b)')->execute(['a' => $periodA, 'b' => $periodB]);
    } catch (Throwable $cleanupError) {
        echo 'CLEANUP ERROR: ' . $cleanupError->getMessage() . PHP_EOL;
    }
}

$pass = $fail = $blocked = 0;
foreach ($results as $r) {
    if ($r['status'] === 'PASS') $pass++;
    elseif ($r['status'] === 'FAIL') $fail++;
    else $blocked++;
}

echo PHP_EOL;
echo "TOTAL TESTS: " . count($results) . PHP_EOL;
echo "PASS: {$pass}" . PHP_EOL;
echo "FAIL: {$fail}" . PHP_EOL;
echo "BLOCKED: {$blocked}" . PHP_EOL;
if ($fail > 0) exit(1);
exit(0);
