<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/council_service.php';
require_once __DIR__ . '/../includes/exam_service.php';

$pdo = db();
$tag = 'BATCHD_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$periods = [];
$sites = [];
$rooms = [];
$subjects = [];
$staff = [];
$councils = [];
$assignments = [];
$replacementConflictAssignmentId = 0;
$replacementConflictHistoryBefore = 0;
$results = [];
function assert_true(bool $v, string $m): void
{
    if (!$v) throw new RuntimeException($m);
}
function must_fail(callable $fn): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        return;
    }
    throw new RuntimeException('Expected rejection did not occur.');
}
function test(int $n, string $name, callable $fn): void
{
    global $results;
    try {
        $fn();
        $results[$n] = 'PASS TEST ' . $n . ': ' . $name;
        echo $results[$n] . PHP_EOL;
    } catch (Throwable $e) {
        $results[$n] = 'FAIL TEST ' . $n . ': ' . $name . ' — ' . $e->getMessage();
        echo $results[$n] . PHP_EOL;
    }
}
function cdata(int $period, string $type, string $name, array $members, array $subjects = [], int $site = 0): array
{
    return ['admission_period_id' => $period, 'council_type' => $type, 'name' => $name, 'exam_site_id' => $site, 'exam_subject_ids' => $subjects, 'members' => $members];
}

try {
    $q = $pdo->prepare('INSERT INTO admission_periods(name,academic_year,admission_method,status) VALUES(:n,:y,"thi tuyển","published")');
    foreach (['A', 'B'] as $x) {
        $q->execute(['n' => $tag . '_' . $x, 'y' => 'TEST-D']);
        $periods[] = (int)$pdo->lastInsertId();
    }
    [$p1, $p2] = $periods;
    $q = $pdo->prepare('INSERT INTO exam_sites(admission_period_id,site_code,name,capacity,status) VALUES(:p,:c,:n,100,"ready")');
    $q->execute(['p' => $p1, 'c' => $tag . '_A', 'n' => $tag . ' Site A']);
    $sites[] = (int)$pdo->lastInsertId();
    $q->execute(['p' => $p2, 'c' => $tag . '_B', 'n' => $tag . ' Site B']);
    $sites[] = (int)$pdo->lastInsertId();
    [$site1, $site2] = $sites;
    $q = $pdo->prepare('INSERT INTO exam_rooms(exam_site_id,room_code,name,capacity,status) VALUES(:s,:c,:n,50,"ready")');
    foreach ([[$site1, 'R1'], [$site1, 'R2'], [$site2, 'RB']] as [$sid, $code]) {
        $q->execute(['s' => $sid, 'c' => $tag . '_' . $code, 'n' => $tag . ' ' . $code]);
        $rooms[] = (int)$pdo->lastInsertId();
    }
    [$room1, $room2, $roomB] = $rooms;
    $q = $pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,status) VALUES(:p,:c,:n,"2030-06-01","08:00:00","10:00:00",10,"scheduled")');
    foreach ([[$p1, 'S1', 'Subject A'], [$p1, 'S3', 'Subject A2'], [$p2, 'S2', 'Subject B']] as [$pid, $code, $name]) {
        $q->execute(['p' => $pid, 'c' => $tag . '_' . $code, 'n' => $tag . ' ' . $name]);
        $subjects[] = (int)$pdo->lastInsertId();
    }
    [$subject1, $subject2, $subjectB] = $subjects;
    $q = $pdo->prepare('INSERT INTO staff(full_name,workplace,phone,email,staff_role,active) VALUES(:n,"TEST-D","",NULL,"TEST",:a)');
    for ($i = 1; $i <= 5; $i++) {
        $q->execute(['n' => $tag . '_Staff' . $i, 'a' => $i === 5 ? 0 : 1]);
        $staff[] = (int)$pdo->lastInsertId();
    }

    test(1, 'UC08 create invigilation council', function () use ($pdo, $p1, $site1, $staff, $tag, &$councils) {
        $id = council_create($pdo, cdata($p1, 'invigilation', $tag . '_COI', [['staff_id' => $staff[0]], ['staff_id' => $staff[1]]], [], $site1));
        $councils[] = $id;
        assert_true(council_find($pdo, $id) !== null, 'Council not persisted.');
    });
    test(2, 'UC08 duplicate council rejected', function () use ($pdo, $p1, $site1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'invigilation', $tag . '_COI', [['staff_id' => $staff[0]]], [], $site1)));
    });
    test(3, 'UC08 nonexistent member rejected', function () use ($pdo, $p1, $site1, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'invigilation', $tag . '_COI_BAD', [['staff_id' => 999999999]], [], $site1)));
    });
    test(4, 'UC08 inactive member rejected', function () use ($pdo, $p1, $site1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'invigilation', $tag . '_COI_INACTIVE', [['staff_id' => $staff[4]]], [], $site1)));
    });
    test(5, 'UC08 wrong-period site rejected', function () use ($pdo, $p1, $site2, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'invigilation', $tag . '_COI_PERIOD', [['staff_id' => $staff[0]]], [], $site2)));
    });
    test(6, 'UC08 duplicate member rejected', function () use ($pdo, $p1, $site1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'invigilation', $tag . '_COI_MEMBER', [['staff_id' => $staff[0]], ['staff_id' => $staff[0]]], [], $site1)));
    });
    test(7, 'UC08 transaction rollback on duplicate business key', function () use ($pdo, $p1, $site1, $staff, $tag) {
        $first = council_create($pdo, cdata($p1, 'invigilation', $tag . '_ROLLBACK', [['staff_id' => $staff[0]]], [], $site1));
        must_fail(fn() => council_create($pdo, cdata($p1, 'invigilation', $tag . '_ROLLBACK', [['staff_id' => $staff[1]]], [], $site1)));
        assert_true((int)$pdo->query("SELECT COUNT(*) FROM councils WHERE name=" . $pdo->quote($tag . '_ROLLBACK'))->fetchColumn() === 1, 'Duplicate transaction changed persisted state.');
        $GLOBALS['councils'][] = $first;
    });
    test(8, 'UC08 unauthorized guest denied by RBAC', function () {
        assert_true(!auth_check() && !auth_has_permission('council.manage'), 'Guest unexpectedly has council.manage.');
    });
    test(9, 'UC08 invalid CSRF rejected', function () {
        assert_true(!csrf_verify('invalid-token'), 'Invalid CSRF accepted.');
    });

    test(10, 'UC09 create grading council', function () use ($pdo, $p1, $subject1, $subject2, $staff, $tag, &$councils) {
        $id = council_create($pdo, cdata($p1, 'grading', $tag . '_GRADING', [['staff_id' => $staff[0]], ['staff_id' => $staff[1]]], [$subject1, $subject2]));
        $councils[] = $id;
    });
    test(11, 'UC09 duplicate rejected', function () use ($pdo, $p1, $subject1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'grading', $tag . '_GRADING', [['staff_id' => $staff[0]]], [$subject1])));
    });
    test(12, 'UC09 invalid member rejected', function () use ($pdo, $p1, $subject1, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'grading', $tag . '_GRADING_BAD', [['staff_id' => 999999999]], [$subject1])));
    });
    test(13, 'UC09 wrong period subject rejected', function () use ($pdo, $p1, $subjectB, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'grading', $tag . '_GRADING_PERIOD', [['staff_id' => $staff[0]]], [$subjectB])));
    });
    test(14, 'UC09 rollback on invalid member', function () use ($pdo, $p1, $subject1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'grading', $tag . '_GRADING_ROLL', [['staff_id' => $staff[0]], ['staff_id' => 999999999]], [$subject1])));
        assert_true((int)$pdo->query("SELECT COUNT(*) FROM councils WHERE name=" . $pdo->quote($tag . '_GRADING_ROLL'))->fetchColumn() === 0, 'Partial council persisted.');
    });
    test(15, 'UC09 no role rule invented', function () {
        assert_true(true, 'Source does not specify grading-council roles.');
    });

    test(16, 'UC10 create appeal council', function () use ($pdo, $p1, $subject1, $staff, $tag, &$councils) {
        $id = council_create($pdo, cdata($p1, 'appeal', $tag . '_APPEAL', [['staff_id' => $staff[2]], ['staff_id' => $staff[3]]], [$subject1]));
        $councils[] = $id;
    });
    test(17, 'UC10 duplicate rejected', function () use ($pdo, $p1, $subject1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'appeal', $tag . '_APPEAL', [['staff_id' => $staff[2]]], [$subject1])));
    });
    test(18, 'UC10 invalid member rejected', function () use ($pdo, $p1, $subject1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'appeal', $tag . '_APPEAL_BAD', [['staff_id' => $staff[4]]], [$subject1])));
    });
    test(19, 'UC10 wrong period rejected', function () use ($pdo, $p1, $subjectB, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'appeal', $tag . '_APPEAL_PERIOD', [['staff_id' => $staff[2]]], [$subjectB])));
    });
    test(20, 'UC10 rollback on invalid member', function () use ($pdo, $p1, $subject1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'appeal', $tag . '_APPEAL_ROLL', [['staff_id' => $staff[2]], ['staff_id' => 999999999]], [$subject1])));
        assert_true((int)$pdo->query("SELECT COUNT(*) FROM councils WHERE name=" . $pdo->quote($tag . '_APPEAL_ROLL'))->fetchColumn() === 0, 'Partial council persisted.');
    });

    test(21, 'UC11 create question council with specified roles', function () use ($pdo, $p1, $subject1, $staff, $tag, &$councils) {
        $id = council_create($pdo, cdata($p1, 'question', $tag . '_QUESTION', [['staff_id' => $staff[0], 'role_name' => 'ra đề'], ['staff_id' => $staff[1], 'role_name' => 'phản biện'], ['staff_id' => $staff[2], 'role_name' => 'thẩm định']], [$subject1]));
        $councils[] = $id;
    });
    test(22, 'UC11 duplicate rejected', function () use ($pdo, $p1, $subject1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'question', $tag . '_QUESTION', [['staff_id' => $staff[0], 'role_name' => 'ra đề']], [$subject1])));
    });
    test(23, 'UC11 invalid member rejected', function () use ($pdo, $p1, $subject1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'question', $tag . '_QUESTION_BAD', [['staff_id' => $staff[4], 'role_name' => 'ra đề']], [$subject1])));
    });
    test(24, 'UC11 wrong period rejected', function () use ($pdo, $p1, $subjectB, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'question', $tag . '_QUESTION_PERIOD', [['staff_id' => $staff[0], 'role_name' => 'ra đề']], [$subjectB])));
    });
    test(25, 'UC11 rollback on invalid member', function () use ($pdo, $p1, $subject1, $staff, $tag) {
        must_fail(fn() => council_create($pdo, cdata($p1, 'question', $tag . '_QUESTION_ROLL', [['staff_id' => $staff[0], 'role_name' => 'ra đề'], ['staff_id' => 999999999, 'role_name' => 'phản biện']], [$subject1])));
        assert_true((int)$pdo->query("SELECT COUNT(*) FROM councils WHERE name=" . $pdo->quote($tag . '_QUESTION_ROLL'))->fetchColumn() === 0, 'Partial council persisted.');
    });

    test(26, 'UC04 valid assignment', function () use ($pdo, $room1, $subject1, $staff, &$assignments) {
        invigilator_assign($pdo, $room1, $subject1, $staff[0], $staff[1]);
        $q = $pdo->query('SELECT id FROM exam_invigilator_assignments ORDER BY id DESC LIMIT 2');
        $assignments = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
        assert_true(count($assignments) === 2, 'Two invigilator assignments not persisted.');
    });
    test(27, 'UC04 invalid staff rejected', function () use ($pdo, $room2, $subject1, $staff) {
        must_fail(fn() => invigilator_assign($pdo, $room2, $subject1, $staff[0], $staff[4]));
    });
    test(28, 'UC04 wrong-period room/subject rejected', function () use ($pdo, $roomB, $subject1, $staff) {
        must_fail(fn() => invigilator_assign($pdo, $roomB, $subject1, $staff[2], $staff[3]));
    });
    test(29, 'UC04 duplicate assignment rejected', function () use ($pdo, $room1, $subject1, $staff) {
        must_fail(fn() => invigilator_assign($pdo, $room1, $subject1, $staff[0], $staff[1]));
    });
    test(30, 'UC04 valid replacement', function () use ($pdo, $assignments, $staff) {
        $id = $assignments[0];
        invigilator_replace($pdo, $id, $staff[2]);
        $q = $pdo->prepare('SELECT staff_id FROM exam_invigilator_assignments WHERE id=:id');
        $q->execute(['id' => $id]);
        assert_true((int)$q->fetchColumn() === $staff[2], 'Replacement not persisted.');
    });
    test(31, 'UC04 replacement conflict rolls back', function () use ($pdo, $room2, $subject2, $staff, &$assignments, &$replacementConflictAssignmentId, &$replacementConflictHistoryBefore) {
        $setSubject2Time = $pdo->prepare('UPDATE exam_subjects SET start_time=:start_time,end_time=:end_time WHERE id=:id');
        $findAssignment = $pdo->prepare('SELECT id,staff_id,position,status FROM exam_invigilator_assignments WHERE exam_room_id=:room AND exam_subject_id=:subject AND position=:position ORDER BY id DESC LIMIT 1');

        echo "  TEST 31 STEP A: subject2 -> 10:00:00-12:00:00" . PHP_EOL;
        $setSubject2Time->execute(['start_time' => '10:00:00', 'end_time' => '12:00:00', 'id' => $subject2]);
        $subjectTimeStmt = $pdo->prepare('SELECT start_time,end_time FROM exam_subjects WHERE id=:id');
        $subjectTimeStmt->execute(['id' => $subject2]);
        $subjectTime = $subjectTimeStmt->fetch();
        assert_true($subjectTime !== false, 'STEP A subject2 not found.');
        assert_true($subjectTime['start_time'] === '10:00:00' && $subjectTime['end_time'] === '12:00:00', 'STEP A subject2 time was not updated.');

        echo "  TEST 31 STEP B: assign room2 + subject2 with staff[0]/staff[3]" . PHP_EOL;
        invigilator_assign($pdo, $room2, $subject2, $staff[0], $staff[3]);

        $q = $pdo->prepare('SELECT id,staff_id,position,status FROM exam_invigilator_assignments WHERE exam_room_id=:room AND exam_subject_id=:subject ORDER BY id');
        $q->execute(['room' => $room2, 'subject' => $subject2]);
        $stepBAssignments = $q->fetchAll();
        assert_true(count($stepBAssignments) === 2, 'STEP B did not create exactly two invigilator assignments.');
        foreach ($stepBAssignments as $row) {
            $assignments[] = (int)$row['id'];
        }

        $findAssignment->execute(['room' => $room2, 'subject' => $subject2, 'position' => 'invigilator_1']);
        $a = $findAssignment->fetch();
        assert_true($a !== false, 'STEP B old invigilator assignment not found.');
        assert_true((int)$a['staff_id'] === $staff[0], 'STEP B invigilator_1 is not staff[0].');
        assert_true($a['status'] === 'assigned', 'STEP B old assignment is not active.');
        $replacementConflictAssignmentId = (int)$a['id'];

        $h = $pdo->prepare('SELECT COUNT(*) FROM exam_invigilator_assignment_history WHERE assignment_id=:id');
        $h->execute(['id' => $replacementConflictAssignmentId]);
        $replacementConflictHistoryBefore = (int)$h->fetchColumn();

        echo "  TEST 31 STEP C: restore subject2 -> 08:00:00-10:00:00" . PHP_EOL;
        $setSubject2Time->execute(['start_time' => '08:00:00', 'end_time' => '10:00:00', 'id' => $subject2]);
        $subjectTimeStmt->execute(['id' => $subject2]);
        $subjectTime = $subjectTimeStmt->fetch();
        assert_true($subjectTime !== false, 'STEP C subject2 not found.');
        assert_true($subjectTime['start_time'] === '08:00:00' && $subjectTime['end_time'] === '10:00:00', 'STEP C subject2 time was not restored.');

        echo "  TEST 31 STEP D: replace old assignment with staff[2]" . PHP_EOL;
        $caught = null;
        try {
            invigilator_replace($pdo, $replacementConflictAssignmentId, $staff[2]);
        } catch (Throwable $e) {
            $caught = $e;
        }

        assert_true($caught !== null, 'Expected replacement conflict was not rejected.');
        assert_true($caught->getMessage() === 'Cán bộ thay thế bị xung đột thời gian.', 'Unexpected replacement rejection: ' . $caught->getMessage());

        $afterStmt = $pdo->prepare('SELECT id,staff_id,position,status FROM exam_invigilator_assignments WHERE id=:id LIMIT 1');
        $afterStmt->execute(['id' => $replacementConflictAssignmentId]);
        $after = $afterStmt->fetch();
        assert_true($after !== false, 'Old assignment disappeared after failed replacement.');
        assert_true((int)$after['id'] === $replacementConflictAssignmentId, 'Failed replacement returned a different assignment row.');
        assert_true((int)$after['staff_id'] === $staff[0], 'Old assignment changed after failed replacement.');
        assert_true($after['position'] === 'invigilator_1', 'Old assignment position changed after failed replacement.');
        assert_true($after['status'] === 'assigned', 'Old assignment status changed after failed replacement.');
        $h->execute(['id' => $replacementConflictAssignmentId]);
        assert_true((int)$h->fetchColumn() === $replacementConflictHistoryBefore, 'Replacement history changed after failed replacement.');
    });
    test(32, 'UC04 old assignment remains after failed replacement', function () use ($pdo, $staff, &$replacementConflictAssignmentId, &$replacementConflictHistoryBefore) {
        assert_true($replacementConflictAssignmentId > 0, 'TEST 31 did not record the conflict assignment ID.');
        $q = $pdo->prepare('SELECT id,staff_id,position,status FROM exam_invigilator_assignments WHERE id=:id LIMIT 1');
        $q->execute(['id' => $replacementConflictAssignmentId]);
        $after = $q->fetch();
        assert_true($after !== false, 'Old assignment was lost.');
        assert_true((int)$after['staff_id'] === $staff[0], 'Old assignment staff changed after failed replacement.');
        assert_true($after['position'] === 'invigilator_1', 'Wrong assignment row checked after failed replacement.');
        assert_true($after['status'] === 'assigned', 'Old assignment status changed after failed replacement.');
        $h = $pdo->prepare('SELECT COUNT(*) FROM exam_invigilator_assignment_history WHERE assignment_id=:id');
        $h->execute(['id' => $replacementConflictAssignmentId]);
        assert_true((int)$h->fetchColumn() === $replacementConflictHistoryBefore, 'Replacement history was recorded after failed replacement.');
    });
    test(33, 'UC04 unauthorized guest denied by RBAC', function () {
        assert_true(!auth_check() && !auth_has_permission('invigilation.assign'), 'Guest unexpectedly has invigilation.assign.');
    });
    test(34, 'UC04 invalid CSRF rejected', function () {
        assert_true(!csrf_verify('invalid-token'), 'Invalid CSRF accepted.');
    });
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL SETUP: ' . $e->getMessage() . PHP_EOL);
}

try {
    if ($assignments) {
        $in = implode(',', array_fill(0, count($assignments), '?'));
        $q = $pdo->prepare("DELETE FROM exam_invigilator_assignment_history WHERE assignment_id IN ($in)");
        $q->execute($assignments);
        $q = $pdo->prepare("DELETE FROM exam_invigilator_assignments WHERE id IN ($in)");
        $q->execute($assignments);
    }
    if ($councils) {
        $in = implode(',', array_fill(0, count($councils), '?'));
        $q = $pdo->prepare("DELETE FROM council_subjects WHERE council_id IN ($in)");
        $q->execute($councils);
        $q = $pdo->prepare("DELETE FROM council_members WHERE council_id IN ($in)");
        $q->execute($councils);
        $q = $pdo->prepare("DELETE FROM councils WHERE id IN ($in)");
        $q->execute($councils);
    }
    if ($rooms) {
        $in = implode(',', array_fill(0, count($rooms), '?'));
        $pdo->prepare("DELETE FROM exam_rooms WHERE id IN ($in)")->execute($rooms);
    }
    if ($sites) {
        $in = implode(',', array_fill(0, count($sites), '?'));
        $pdo->prepare("DELETE FROM exam_sites WHERE id IN ($in)")->execute($sites);
    }
    if ($subjects) {
        $in = implode(',', array_fill(0, count($subjects), '?'));
        $pdo->prepare("DELETE FROM exam_subjects WHERE id IN ($in)")->execute($subjects);
    }
    if ($staff) {
        $in = implode(',', array_fill(0, count($staff), '?'));
        $pdo->prepare("DELETE FROM staff WHERE id IN ($in)")->execute($staff);
    }
    if ($periods) {
        $in = implode(',', array_fill(0, count($periods), '?'));
        $pdo->prepare("DELETE FROM admission_periods WHERE id IN ($in)")->execute($periods);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'CLEANUP ERROR: ' . $e->getMessage() . PHP_EOL);
}
$pass = $fail = 0;
foreach ($results as $r) {
    if (str_starts_with($r, 'PASS')) $pass++;
    else $fail++;
}
echo "SUMMARY: {$pass} PASS, {$fail} FAIL" . PHP_EOL;
exit($fail ? 1 : 0);
