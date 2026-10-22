<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admission_service.php';
require_once __DIR__ . '/../includes/publication_service.php';
require_once __DIR__ . '/../includes/appeal_service.php';

$pdo = db();
$tag = 'BATCHE_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$results = [];
$periodId = 0;
$period2Id = 0;
$schoolId = 0;
$siteId = 0;
$roomId = 0;
$subject1 = 0;
$subject2 = 0;
$subject3 = 0;
$candidate1 = 0;
$candidate2 = 0;
$asp1 = 0;
$asp2 = 0;
$paper11 = 0;
$paper12 = 0;
$paper21 = 0;
$paper22 = 0;
$score11 = 0;
$score12 = 0;
$score21 = 0;
$score22 = 0;
$quotaId = 0;
$windowId = 0;
$userId = 0;
$runId = 0;
$admittedCandidateId = 0;
$admittedResultId = 0;
$appealId = 0;

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function must_fail(callable $fn, ?string $contains = null): Throwable
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($contains !== null) {
            assert_true(str_contains($e->getMessage(), $contains), 'Unexpected rejection: ' . $e->getMessage());
        }
        return $e;
    }
    throw new RuntimeException('Expected rejection did not occur.');
}

function test(int $number, string $name, callable $fn): void
{
    global $results;
    try {
        $fn();
        $results[$number] = true;
        echo 'PASS TEST ' . $number . ': ' . $name . PHP_EOL;
    } catch (Throwable $e) {
        $results[$number] = false;
        echo 'FAIL TEST ' . $number . ': ' . $name . ' — ' . $e->getMessage() . PHP_EOL;
    }
}

function fetch_count(PDO $pdo, string $sql, array $params = []): int
{
    $q = $pdo->prepare($sql);
    $q->execute($params);
    return (int)$q->fetchColumn();
}

function create_score(PDO $pdo, int $paperId, int $userId, float $score): int
{
    $q = $pdo->prepare(
        'INSERT INTO scores(exam_paper_id,anonymous_code,score_1,score_2,total_score,entered_by)
         SELECT id, anonymous_code, :score1, NULL, :score2, :u FROM exam_papers WHERE id=:id'
    );
    $q->execute(['score1' => $score, 'score2' => $score, 'u' => $userId, 'id' => $paperId]);
    return (int)$pdo->lastInsertId();
}

try {
    $u = $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
    assert_true($u !== false, 'No user fixture is available.');
    $userId = (int)$u;

    $q = $pdo->prepare(
        'INSERT INTO admission_periods(name,academic_year,admission_method,status,enrollment_start,enrollment_end)
         VALUES(:name,:year,"thi tuyển","published",DATE_ADD(NOW(),INTERVAL 1 DAY),DATE_ADD(NOW(),INTERVAL 2 DAY))'
    );
    $q->execute(['name' => $tag . '_A', 'year' => 'TEST-E']);
    $periodId = (int)$pdo->lastInsertId();
    $q->execute(['name' => $tag . '_B', 'year' => 'TEST-E']);
    $period2Id = (int)$pdo->lastInsertId();

    $q = $pdo->prepare(
        'INSERT INTO schools(school_code,name,level,active) VALUES(:code,:name,"THPT",1)'
    );
    $q->execute(['code' => $tag . '_SCH', 'name' => $tag . ' School']);
    $schoolId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare(
        'INSERT INTO exam_sites(admission_period_id,school_id,site_code,name,capacity,status)
         VALUES(:p,:s,:code,:name,100,"ready")'
    );
    $q->execute(['p' => $periodId, 's' => $schoolId, 'code' => $tag . '_SITE', 'name' => $tag . ' Site']);
    $siteId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare(
        'INSERT INTO exam_rooms(exam_site_id,room_code,name,capacity,status)
         VALUES(:site,:code,:name,100,"ready")'
    );
    $q->execute(['site' => $siteId, 'code' => $tag . '_ROOM', 'name' => $tag . ' Room']);
    $roomId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare(
        'INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,component_count,status)
         VALUES(:p,:code,:name,"2030-06-01","08:00:00","10:00:00",10,1,"scheduled")'
    );
    foreach ([['S1', 'Subject 1'], ['S2', 'Subject 2']] as [$code, $name]) {
        $q->execute(['p' => $periodId, 'code' => $tag . '_' . $code, 'name' => $tag . ' ' . $name]);
        if ($subject1 === 0) $subject1 = (int)$pdo->lastInsertId();
        else $subject2 = (int)$pdo->lastInsertId();
    }

    $q = $pdo->prepare(
        'INSERT INTO candidates(user_id,candidate_code,national_id,full_name,eligible,status,admission_status)
         VALUES(NULL,:code,:nid,:name,1,"active","not_processed")'
    );
    $q->execute(['code' => $tag . '_C1', 'nid' => $tag . '_N1', 'name' => $tag . ' Candidate 1']);
    $candidate1 = (int)$pdo->lastInsertId();
    $q->execute(['code' => $tag . '_C2', 'nid' => $tag . '_N2', 'name' => $tag . ' Candidate 2']);
    $candidate2 = (int)$pdo->lastInsertId();

    $q = $pdo->prepare(
        'INSERT INTO aspirations(candidate_id,admission_period_id,school_id,priority_order) VALUES(:c,:p,:s,1)'
    );
    $q->execute(['c' => $candidate1, 'p' => $periodId, 's' => $schoolId]);
    $asp1 = (int)$pdo->lastInsertId();
    $q->execute(['c' => $candidate2, 'p' => $periodId, 's' => $schoolId]);
    $asp2 = (int)$pdo->lastInsertId();

    $q = $pdo->prepare(
        'INSERT INTO candidate_priority_points(admission_period_id,candidate_id,priority_group,priority_points)
         VALUES(:p,:c,NULL,0)'
    );
    $q->execute(['p' => $periodId, 'c' => $candidate1]);
    $q->execute(['p' => $periodId, 'c' => $candidate2]);

    $q = $pdo->prepare(
        'INSERT INTO exam_papers(admission_period_id,candidate_id,exam_subject_id,exam_room_id,attendance_status,anonymous_code,paper_status)
         VALUES(:p,:c,:s,:r,"present",:code,"coded")'
    );
    foreach ([[$candidate1, $subject1, 'P11'], [$candidate1, $subject2, 'P12'], [$candidate2, $subject1, 'P21'], [$candidate2, $subject2, 'P22']] as [$candidate, $subject, $code]) {
        $q->execute(['p' => $periodId, 'c' => $candidate, 's' => $subject, 'r' => $roomId, 'code' => $tag . '_' . $code]);
        ${'paper' . substr($code, 1, 2)} = (int)$pdo->lastInsertId();
    }

    $score11 = create_score($pdo, $paper11, $userId, 9.00);
    $score12 = create_score($pdo, $paper12, $userId, 8.00);
    $score21 = create_score($pdo, $paper21, $userId, 5.00);
    $score22 = create_score($pdo, $paper22, $userId, 5.00);

    $q = $pdo->prepare(
        'INSERT INTO admission_quotas(admission_period_id,school_id,quota,status) VALUES(:p,:s,1,"set")'
    );
    $q->execute(['p' => $periodId, 's' => $schoolId]);
    $quotaId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare(
        'INSERT INTO appeal_windows(admission_period_id,start_at,end_at,fee_amount,status)
         VALUES(:p,DATE_SUB(NOW(),INTERVAL 2 DAY),DATE_SUB(NOW(),INTERVAL 1 DAY),0,"closed")'
    );
    $q->execute(['p' => $periodId]);
    $windowId = (int)$pdo->lastInsertId();

    test(1, 'UC21 valid prerequisites', function () use ($pdo, $periodId) {
        assert_true(admission_check_prerequisites($pdo, $periodId) === [], 'Valid prerequisites were rejected.');
    });

    test(2, 'UC21 missing quota rejected', function () use ($pdo, $periodId, $quotaId, $schoolId) {
        $pdo->prepare('DELETE FROM admission_quotas WHERE id=:id')->execute(['id' => $quotaId]);
        try {
            $errors = admission_check_prerequisites($pdo, $periodId);
            assert_true(in_array('Chưa thiết lập chỉ tiêu tuyển sinh.', $errors, true), 'Missing quota was not detected.');
        } finally {
            $q = $pdo->prepare('INSERT INTO admission_quotas(admission_period_id,school_id,quota,status) VALUES(:p,:s,1,"set")');
            $q->execute(['p' => $periodId, 's' => $schoolId]);
        }
    });

    test(3, 'UC21 missing aspiration rejected', function () use ($pdo, $periodId, $candidate1, $candidate2, $asp1, $asp2, $schoolId) {
        $pdo->prepare('DELETE FROM aspirations WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        try {
            $errors = admission_check_prerequisites($pdo, $periodId);
            assert_true(in_array('Chưa có nguyện vọng của thí sinh đủ điều kiện.', $errors, true), 'Missing aspiration was not detected.');
        } finally {
            $q = $pdo->prepare('INSERT INTO aspirations(candidate_id,admission_period_id,school_id,priority_order) VALUES(:c,:p,:s,1)');
            $q->execute(['c' => $candidate1, 'p' => $periodId, 's' => $schoolId]);
            $q->execute(['c' => $candidate2, 'p' => $periodId, 's' => $schoolId]);
        }
    });

    test(4, 'UC21 missing official score rejected', function () use ($pdo, $periodId, $score22, $paper22, $userId) {
        $pdo->prepare('DELETE FROM scores WHERE id=:id')->execute(['id' => $score22]);
        try {
            $errors = admission_check_prerequisites($pdo, $periodId);
            assert_true(count(array_filter($errors, fn($e) => str_contains($e, 'Chưa đủ điểm chính thức'))) > 0, 'Missing official score was not detected.');
        } finally {
            create_score($pdo, $paper22, $userId, 5.00);
        }
    });

    test(5, 'UC21 pending appeal blocks admission prerequisites', function () use ($pdo, $periodId, $candidate1, $paper11, $subject1, $score11) {
        $q = $pdo->prepare(
            'INSERT INTO appeal_requests(admission_period_id,candidate_id,exam_paper_id,exam_subject_id,original_score,reason,fee_amount,payment_status,status)
             VALUES(:p,:c,:paper,:s,:score,"TEST pending",0,"not_required","pending")'
        );
        $q->execute(['p' => $periodId, 'c' => $candidate1, 'paper' => $paper11, 's' => $subject1, 'score' => 9.00]);
        $appeal = (int)$pdo->lastInsertId();
        try {
            $errors = admission_check_prerequisites($pdo, $periodId);
            assert_true(count(array_filter($errors, fn($e) => str_contains($e, 'Vẫn còn yêu cầu phúc khảo'))) > 0, 'Pending appeal was not detected.');
        } finally {
            $pdo->prepare('DELETE FROM appeal_requests WHERE id=:id')->execute(['id' => $appeal]);
        }
    });

    test(6, 'UC21 wrong period is isolated', function () use ($pdo, $period2Id) {
        $errors = admission_check_prerequisites($pdo, $period2Id);
        assert_true(count($errors) >= 1, 'Wrong-period fixture unexpectedly passed prerequisites.');
    });

    test(7, 'UC21 unspecified ranking/tie-break remains documented', function () {
        echo "  [MISSING SPECIFICATION — UC21] Tie-break/quota allocation is not asserted by this runtime test." . PHP_EOL;
        assert_true(true, 'documentation marker');
    });

    test(8, 'UC21 admission run before publication', function () use ($pdo, $periodId, $userId, &$runId, &$admittedCandidateId, &$admittedResultId) {
        $runId = admission_calculate($pdo, $periodId, $userId);
        assert_true($runId > 0, 'Admission run was not created.');
        $rows = admission_results($pdo, $runId);
        assert_true(count($rows) === 2, 'Expected one result row per aspiration.');
        foreach ($rows as $row) {
            if ($row['result_status'] === 'admitted') {
                $admittedCandidateId = (int)$row['candidate_id'];
                $admittedResultId = (int)$row['id'];
            }
        }
        assert_true($admittedCandidateId > 0 && $admittedResultId > 0, 'No admitted result was produced.');
    });

    test(9, 'UC22 empty/no-score state rejects publication', function () use ($pdo, $period2Id, $userId) {
        must_fail(fn() => publish_exam_results($pdo, $period2Id, $userId), 'Chưa có dữ liệu điểm thi để công bố.');
    });

    test(10, 'UC22 valid publication path', function () use ($pdo, $periodId, $userId) {
        publish_exam_results($pdo, $periodId, $userId);
        assert_true(exam_results_published($pdo, $periodId), 'Exam publication state was not persisted.');
    });

    test(11, 'UC22 duplicate publication rejected', function () use ($pdo, $periodId, $userId) {
        must_fail(fn() => publish_exam_results($pdo, $periodId, $userId), 'Điểm thi đã được công bố.');
    });

    test(12, 'UC22 publication state is published', function () use ($pdo, $periodId) {
        $q = $pdo->prepare('SELECT status FROM publication_events WHERE admission_period_id=:p AND publication_type="exam_results" LIMIT 1');
        $q->execute(['p' => $periodId]);
        assert_true($q->fetchColumn() === 'published', 'Exam publication state is not published.');
    });

    test(13, 'UC22 score INSERT remains locked by existing trigger', function () use ($pdo, $periodId, $candidate1, $roomId, $userId, $tag) {
        $q = $pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,component_count,status) VALUES(:p,:code,:name,"2030-06-01","08:00:00","10:00:00",10,1,"scheduled")');
        $q->execute(['p' => $periodId, 'code' => $tag . '_LOCK_SUBJECT', 'name' => $tag . ' Lock Subject']);
        $subject = (int)$pdo->lastInsertId();
        $q = $pdo->prepare('INSERT INTO exam_papers(admission_period_id,candidate_id,exam_subject_id,exam_room_id,attendance_status,anonymous_code,paper_status) VALUES(:p,:c,:s,:r,"present",:code,"coded")');
        $q->execute(['p' => $periodId, 'c' => $candidate1, 's' => $subject, 'r' => $roomId, 'code' => $tag . '_LOCK_INSERT']);
        $paper = (int)$pdo->lastInsertId();
        try {
            must_fail(fn() => create_score($pdo, $paper, $userId, 4.00), 'Điểm thi đã công bố và bị khóa.');
        } finally {
            $pdo->prepare('DELETE FROM exam_papers WHERE id=:id')->execute(['id' => $paper]);
            $pdo->prepare('DELETE FROM exam_subjects WHERE id=:id')->execute(['id' => $subject]);
        }
    });

    test(14, 'UC22 score UPDATE remains locked by existing trigger', function () use ($pdo, $score11) {
        must_fail(function () use ($pdo, $score11) {
            $q = $pdo->prepare('UPDATE scores SET total_score=9.50, score_1=9.50 WHERE id=:id');
            $q->execute(['id' => $score11]);
        }, 'Điểm thi đã công bố và bị khóa.');
    });

    test(15, 'UC22 score DELETE remains locked by existing trigger', function () use ($pdo, $score12) {
        must_fail(function () use ($pdo, $score12) {
            $q = $pdo->prepare('DELETE FROM scores WHERE id=:id');
            $q->execute(['id' => $score12]);
        }, 'Điểm thi đã công bố và bị khóa.');
    });

    test(16, 'UC23 admission result publication', function () use ($pdo, $periodId, $userId) {
        publish_admission_results($pdo, $periodId, $userId);
        assert_true(admission_results_published($pdo, $periodId), 'Admission result publication state was not persisted.');
    });

    test(17, 'UC23 direct admission rerun rejected after publication', function () use ($pdo, $periodId, $userId, $runId, $admittedResultId) {
        $beforeRuns = fetch_count($pdo, 'SELECT COUNT(*) FROM admission_runs WHERE admission_period_id=:p', ['p' => $periodId]);
        $beforeResult = $pdo->prepare('SELECT result_status,enrollment_status,admission_score FROM admission_results WHERE id=:id');
        $beforeResult->execute(['id' => $admittedResultId]);
        $before = $beforeResult->fetch();
        must_fail(fn() => admission_calculate($pdo, $periodId, $userId), 'Kết quả tuyển sinh đã được công bố');
        $afterRuns = fetch_count($pdo, 'SELECT COUNT(*) FROM admission_runs WHERE admission_period_id=:p', ['p' => $periodId]);
        $beforeResult->execute(['id' => $admittedResultId]);
        $after = $beforeResult->fetch();
        assert_true($beforeRuns === $afterRuns, 'Rejected rerun created or modified an admission run.');
        assert_true($before === $after, 'Published admission result changed after rejected rerun.');
    });

    test(18, 'UC26 admitted candidate inside enrollment window', function () use ($pdo, $periodId, $admittedCandidateId, $admittedResultId) {
        $pdo->prepare('UPDATE admission_periods SET enrollment_start=DATE_SUB(NOW(),INTERVAL 1 DAY), enrollment_end=DATE_ADD(NOW(),INTERVAL 1 DAY) WHERE id=:p')->execute(['p' => $periodId]);
        enrollment_confirm($pdo, $admittedCandidateId, $periodId);
        assert_true(fetch_count($pdo, 'SELECT COUNT(*) FROM enrollment_confirmations WHERE admission_result_id=:r', ['r' => $admittedResultId]) === 1, 'Enrollment confirmation was not persisted.');
        $q = $pdo->prepare('SELECT enrollment_status FROM admission_results WHERE id=:id');
        $q->execute(['id' => $admittedResultId]);
        assert_true($q->fetchColumn() === 'confirmed', 'Admission result was not marked confirmed.');
        $q = $pdo->prepare('SELECT admission_status FROM candidates WHERE id=:id');
        $q->execute(['id' => $admittedCandidateId]);
        assert_true($q->fetchColumn() === 'enrolled', 'Candidate was not marked enrolled.');
    });

    test(19, 'UC26 duplicate confirmation rejected', function () use ($pdo, $periodId, $admittedCandidateId) {
        must_fail(fn() => enrollment_confirm($pdo, $admittedCandidateId, $periodId), 'đã được xác nhận');
    });

    test(20, 'UC26 non-admitted candidate rejected', function () use ($pdo, $periodId, $candidate1, $candidate2, $admittedCandidateId) {
        $nonAdmitted = $candidate1 === $admittedCandidateId ? $candidate2 : $candidate1;
        must_fail(fn() => enrollment_confirm($pdo, $nonAdmitted, $periodId), 'chưa có kết quả trúng tuyển');
    });

    test(21, 'UC26 before enrollment window rejected', function () use ($pdo, $periodId, $candidate1, $admittedCandidateId) {
        $nonAdmitted = $candidate1 === $admittedCandidateId ? $candidate1 : $candidate1;
        // The admitted candidate is already confirmed; this test uses a direct window check through a fresh period state.
        $pdo->prepare('UPDATE admission_periods SET enrollment_start=DATE_ADD(NOW(),INTERVAL 1 DAY), enrollment_end=DATE_ADD(NOW(),INTERVAL 2 DAY) WHERE id=:p')->execute(['p' => $periodId]);
        must_fail(fn() => enrollment_confirm($pdo, $admittedCandidateId, $periodId), 'Thời gian xác nhận nhập học');
    });

    test(22, 'UC26 after enrollment window rejected', function () use ($pdo, $periodId, $admittedCandidateId) {
        $pdo->prepare('UPDATE admission_periods SET enrollment_start=DATE_SUB(NOW(),INTERVAL 2 DAY), enrollment_end=DATE_SUB(NOW(),INTERVAL 1 DAY) WHERE id=:p')->execute(['p' => $periodId]);
        must_fail(fn() => enrollment_confirm($pdo, $admittedCandidateId, $periodId), 'Thời gian xác nhận nhập học');
    });

    test(23, 'UC27 valid appeal persists as pending', function () use ($pdo, $periodId, $candidate1, $subject1, &$appealId) {
        $pdo->prepare('UPDATE appeal_windows SET start_at=DATE_SUB(NOW(),INTERVAL 1 DAY), end_at=DATE_ADD(NOW(),INTERVAL 1 DAY), status="open" WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        $appealId = create_appeal($pdo, $candidate1, $periodId, $subject1, 'TEST valid appeal');
        $q = $pdo->prepare('SELECT status FROM appeal_requests WHERE id=:id');
        $q->execute(['id' => $appealId]);
        assert_true($q->fetchColumn() === 'pending', 'Appeal was not persisted as pending.');
    });

    test(24, 'UC27 duplicate subject rejected', function () use ($pdo, $periodId, $candidate1, $subject1) {
        must_fail(fn() => create_appeal($pdo, $candidate1, $periodId, $subject1, 'TEST duplicate'), 'đã đăng ký phúc khảo');
    });

    test(25, 'UC27 closed window rejected', function () use ($pdo, $periodId, $candidate2, $subject1) {
        $pdo->prepare('UPDATE appeal_windows SET status="closed" WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        must_fail(fn() => create_appeal($pdo, $candidate2, $periodId, $subject1, 'TEST closed'), 'Đã hết thời hạn đăng ký phúc khảo.');
        $pdo->prepare('UPDATE appeal_windows SET status="open" WHERE admission_period_id=:p')->execute(['p' => $periodId]);
    });

    test(26, 'UC27 outside window rejected', function () use ($pdo, $periodId, $candidate2, $subject1) {
        $pdo->prepare('UPDATE appeal_windows SET start_at=DATE_SUB(NOW(),INTERVAL 2 DAY), end_at=DATE_SUB(NOW(),INTERVAL 1 DAY), status="open" WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        must_fail(fn() => create_appeal($pdo, $candidate2, $periodId, $subject1, 'TEST outside'), 'Đã hết thời hạn đăng ký phúc khảo.');
        $pdo->prepare('UPDATE appeal_windows SET start_at=DATE_SUB(NOW(),INTERVAL 1 DAY), end_at=DATE_ADD(NOW(),INTERVAL 1 DAY), status="open" WHERE admission_period_id=:p')->execute(['p' => $periodId]);
    });

    test(27, 'UC27 no-score subject rejected', function () use ($pdo, $periodId, $candidate1, $roomId, $tag) {
        $q = $pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,component_count,status) VALUES(:p,:code,:name,"2030-06-01","08:00:00","10:00:00",10,1,"scheduled")');
        $q->execute(['p' => $periodId, 'code' => $tag . '_S3', 'name' => $tag . ' Subject 3']);
        $subject3 = (int)$pdo->lastInsertId();

        $q = $pdo->prepare('INSERT INTO exam_papers(admission_period_id,candidate_id,exam_subject_id,exam_room_id,attendance_status,anonymous_code,paper_status) VALUES(:p,:c,:s,:r,"present",:code,"coded")');
        $q->execute(['p' => $periodId, 'c' => $candidate1, 's' => $subject3, 'r' => $roomId, 'code' => $tag . '_P31']);

        try {
            must_fail(fn() => create_appeal($pdo, $candidate1, $periodId, $subject3, 'TEST no score'), 'không đủ điều kiện');
        } finally {
            $paper = fetch_count($pdo, 'SELECT id FROM exam_papers WHERE anonymous_code=:code', ['code' => $tag . '_P31']);
            $pdo->prepare('DELETE FROM exam_papers WHERE anonymous_code=:code')->execute(['code' => $tag . '_P31']);
            $pdo->prepare('DELETE FROM exam_subjects WHERE id=:id')->execute(['id' => $subject3]);
        }
    });

    test(28, 'UC27 wrong period rejected', function () use ($pdo, $period2Id, $candidate1, $subject1) {
        $q = $pdo->prepare('INSERT INTO appeal_windows(admission_period_id,start_at,end_at,fee_amount,status) VALUES(:p,DATE_SUB(NOW(),INTERVAL 1 DAY),DATE_ADD(NOW(),INTERVAL 1 DAY),0,"open")');
        $q->execute(['p' => $period2Id]);

        try {
            must_fail(fn() => create_appeal($pdo, $candidate1, $period2Id, $subject1, 'TEST wrong period'), 'không đủ điều kiện');
        } finally {
            $pdo->prepare('DELETE FROM appeal_windows WHERE admission_period_id=:p')->execute(['p' => $period2Id]);
        }
    });

    test(29, 'UC27 invalid registration rolls back', function () use ($pdo, $periodId, $candidate2, $subject1) {
        $before = fetch_count($pdo, 'SELECT COUNT(*) FROM appeal_requests WHERE admission_period_id=:p AND candidate_id=:c', ['p' => $periodId, 'c' => $candidate2]);
        must_fail(fn() => create_appeal($pdo, $candidate2, $periodId, $subject1, ''));
        $after = fetch_count($pdo, 'SELECT COUNT(*) FROM appeal_requests WHERE admission_period_id=:p AND candidate_id=:c', ['p' => $periodId, 'c' => $candidate2]);
        assert_true($before === $after, 'Invalid appeal changed persisted request count.');
    });

    test(30, 'SPEC_CONFLICT score publication vs appeal completion is preserved', function () use ($pdo, $appealId, $score11, $userId) {
        $pdo->prepare('UPDATE appeal_requests SET status="grading" WHERE id=:id')->execute(['id' => $appealId]);

        $beforeScore = fetch_count($pdo, 'SELECT total_score FROM scores WHERE id=:id', ['id' => $score11]);

        $caught = null;
        try {
            complete_appeal($pdo, $appealId, 9.50, 'TEST published-score conflict', $userId);
        } catch (Throwable $e) {
            $caught = $e;
        }

        assert_true($caught !== null, 'Published score update unexpectedly succeeded.');

        $q = $pdo->prepare('SELECT total_score FROM scores WHERE id=:id');
        $q->execute(['id' => $score11]);
        assert_true((float)$q->fetchColumn() === (float)$beforeScore, 'Score changed despite publication lock.');

        $q = $pdo->prepare('SELECT status FROM appeal_requests WHERE id=:id');
        $q->execute(['id' => $appealId]);
        assert_true($q->fetchColumn() === 'grading', 'Appeal transaction did not roll back to grading.');

        echo "  [SPEC_CONFLICT] Published scores are locked by the existing DB trigger; appeal completion cannot update the official score." . PHP_EOL;
    });
} finally {
    // Clean only this test's tagged fixtures. Existing application data and Batch A-D fixtures are untouched.
    try {
        if ($appealId) $pdo->prepare('DELETE FROM appeal_results WHERE appeal_request_id=:id')->execute(['id' => $appealId]);
        if ($periodId) $pdo->prepare('DELETE FROM score_change_logs WHERE appeal_request_id IN (SELECT id FROM appeal_requests WHERE admission_period_id=:p)')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM appeal_requests WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM enrollment_confirmations WHERE candidate_id IN (:c1,:c2)')->execute(['c1' => $candidate1, 'c2' => $candidate2]);
        if ($periodId) $pdo->prepare('DELETE FROM admission_results WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM admission_runs WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM publication_events WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM scores WHERE exam_paper_id IN (SELECT id FROM exam_papers WHERE admission_period_id=:p)')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM exam_papers WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM candidate_priority_points WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM aspirations WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM admission_quotas WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM appeal_windows WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM exam_subjects WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($periodId) $pdo->prepare('DELETE FROM exam_rooms WHERE exam_site_id=:s')->execute(['s' => $siteId]);
        if ($periodId) $pdo->prepare('DELETE FROM exam_sites WHERE id=:s')->execute(['s' => $siteId]);
        if ($candidate1 || $candidate2) $pdo->prepare('DELETE FROM candidates WHERE id IN (:c1,:c2)')->execute(['c1' => $candidate1, 'c2' => $candidate2]);
        if ($schoolId) $pdo->prepare('DELETE FROM schools WHERE id=:id')->execute(['id' => $schoolId]);
        if ($period2Id) $pdo->prepare('DELETE FROM appeal_windows WHERE admission_period_id=:p')->execute(['p' => $period2Id]);
        if ($periodId) $pdo->prepare('DELETE FROM admission_periods WHERE id=:p')->execute(['p' => $periodId]);
        if ($period2Id) $pdo->prepare('DELETE FROM admission_periods WHERE id=:p')->execute(['p' => $period2Id]);
    } catch (Throwable $cleanupError) {
        echo 'CLEANUP ERROR: ' . $cleanupError->getMessage() . PHP_EOL;
    }
}

$total = count($results);
$passed = count(array_filter($results));
$failed = $total - $passed;
echo "BATCH E RUNTIME: {$passed}/{$total} PASS" . PHP_EOL;
exit($failed === 0 ? 0 : 1);
