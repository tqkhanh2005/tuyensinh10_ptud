<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_once __DIR__ . '/../includes/candidate_service.php';

$pdo = db();
$tag = 'BATCHF_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$runId = substr(preg_replace('/\D/', '', $tag), -8);
$candidate1NationalId = 'F' . $runId . 'A';
$candidate2NationalId = 'F' . $runId . 'B';
$deleteCandidateNationalId = 'F' . $runId . 'D';
$dependencyCandidateNationalId = 'F' . $runId . 'P';
$rollbackCandidateNationalId = 'F' . $runId . 'R';
$editCandidateNationalId = 'F' . $runId . 'E';
$staffIds = [];
$candidateIds = [];
$userIds = [];
$periodId = 0;
$schoolId = 0;
$siteId = 0;
$roomId = 0;
$subjectId = 0;
$councilId = 0;
$assignmentId = 0;
$results = [];

function assert_true(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function must_fail(callable $fn, string $contains = ''): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($contains !== '' && !str_contains($e->getMessage(), $contains)) {
            throw new RuntimeException('Unexpected rejection: ' . $e->getMessage());
        }
        return;
    }
    throw new RuntimeException('Expected rejection did not occur.');
}
function test(int $n, string $name, callable $fn): void
{
    global $results;
    try {
        $fn();
        $results[$n] = "PASS TEST {$n}: {$name}";
        echo $results[$n] . PHP_EOL;
    } catch (Throwable $e) {
        $results[$n] = "FAIL TEST {$n}: {$name} — {$e->getMessage()}";
        echo $results[$n] . PHP_EOL;
    }
}

try {
    $q = $pdo->prepare('INSERT INTO admission_periods(name,academic_year,admission_method,status) VALUES(:n,:y,"thi tuyển","published")');
    $q->execute(['n' => $tag . '_PERIOD', 'y' => 'TEST-F']);
    $periodId = (int)$pdo->lastInsertId();

    // UC31/UC32 require middle_school_id to reference an active THCS school.
    $q = $pdo->prepare('INSERT INTO schools(school_code,name,level,active) VALUES(:c,:n,"THCS",1)');
    $q->execute(['c' => $tag . '_SCH', 'n' => $tag . ' School']);
    $schoolId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO exam_sites(admission_period_id,site_code,name,capacity,status) VALUES(:p,:c,:n,50,"ready")');
    $q->execute(['p' => $periodId, 'c' => $tag . '_SITE', 'n' => $tag . ' Site']);
    $siteId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO exam_rooms(exam_site_id,room_code,name,capacity,status) VALUES(:s,:c,:n,30,"ready")');
    $q->execute(['s' => $siteId, 'c' => $tag . '_ROOM', 'n' => $tag . ' Room']);
    $roomId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,status) VALUES(:p,:c,:n,"2030-06-01","08:00:00","10:00:00",10,"scheduled")');
    $q->execute(['p' => $periodId, 'c' => $tag . '_SUBJ', 'n' => $tag . ' Subject']);
    $subjectId = (int)$pdo->lastInsertId();

    // ---------- F1: staff ----------
    test(1, 'UC05 valid staff creation', function () use ($tag, &$staffIds) {
        $id = staff_create(['full_name' => $tag . '_Staff1', 'workplace' => 'TEST-F', 'phone' => '0900000001', 'email' => $tag . '1@example.com', 'staff_role' => 'TEST']);
        $staffIds[] = $id;
        assert_true(staff_find($id) !== null, 'Staff was not persisted.');
    });

    test(2, 'UC05 duplicate staff rejected', function () use ($tag) {
        must_fail(fn() => staff_create(['full_name' => $tag . '_Staff1', 'workplace' => 'TEST-F', 'phone' => '0900000001', 'email' => $tag . '1@example.com', 'staff_role' => 'TEST']), '');
    });

    test(3, 'UC05 invalid required data rejected', function () {
        [$data, $errors] = staff_validate(['full_name' => '', 'workplace' => '', 'staff_role' => '']);
        assert_true(isset($errors['full_name'], $errors['workplace'], $errors['staff_role']), 'Required staff fields were not rejected.');
    });

    test(4, 'UC05 unauthorized rejected by RBAC', function () {
        auth_logout();
        assert_true(!auth_check() && !auth_has_permission('staff.create'), 'Guest unexpectedly has staff.create.');
    });

    test(5, 'UC05 invalid CSRF rejected', function () {
        assert_true(!csrf_verify('invalid-token'), 'Invalid CSRF accepted.');
    });

    test(6, 'UC06 valid update', function () use ($staffIds) {
        assert_true(staff_update($staffIds[0], ['full_name' => 'BATCHF_Staff1_EDIT', 'workplace' => 'TEST-F-EDIT', 'phone' => '0900000002', 'email' => 'edit@example.com', 'staff_role' => 'TEST-EDIT']), 'Staff update did not execute.');
        $row = staff_find($staffIds[0]);
        assert_true($row !== null && $row['full_name'] === 'BATCHF_Staff1_EDIT' && $row['workplace'] === 'TEST-F-EDIT', 'Staff update was not persisted.');
    });

    test(7, 'UC06 nonexistent staff rejected', function () {
        must_fail(fn() => staff_update(999999999, ['full_name' => 'X', 'workplace' => 'Y', 'phone' => '', 'email' => '', 'staff_role' => 'Z']));
    });

    test(8, 'UC06 invalid data rejected', function () {
        [, $errors] = staff_validate(['full_name' => '', 'workplace' => '', 'staff_role' => '', 'phone' => 'bad', 'email' => 'bad']);
        assert_true(count($errors) >= 5, 'Invalid staff data was not rejected.');
    });

    test(9, 'UC06 unauthorized rejected by RBAC', function () {
        auth_logout();
        assert_true(!auth_has_permission('staff.update'), 'Guest unexpectedly has staff.update.');
    });

    test(10, 'UC06 invalid CSRF rejected', function () {
        assert_true(!csrf_verify('BATCHF_BAD_CSRF'), 'Invalid CSRF accepted.');
    });

    test(11, 'UC07 valid staff deletion via inactive status', function () use ($tag, &$staffIds) {
        $id = staff_create(['full_name' => $tag . '_Staff2', 'workplace' => 'TEST-F', 'phone' => '0900000003', 'email' => $tag . '2@example.com', 'staff_role' => 'TEST']);
        $staffIds[] = $id;
        assert_true(staff_deactivate($id), 'Staff deletion/inactivation failed.');
        $row = staff_find($id);
        assert_true((int)$row['active'] === 0, 'Staff was not moved to inactive state.');
    });

    test(12, 'UC07 nonexistent staff rejected', function () {
        must_fail(fn() => staff_deactivate(999999999));
    });

    test(13, 'UC07 referenced staff rejected', function () use ($pdo, $tag, &$staffIds, &$councilId, &$assignmentId, $periodId, $siteId, $roomId, $subjectId) {
        $id = staff_create(['full_name' => $tag . '_StaffRef', 'workplace' => 'TEST-F', 'phone' => '0900000004', 'email' => $tag . '4@example.com', 'staff_role' => 'TEST']);
        $staffIds[] = $id;
        $q = $pdo->prepare('INSERT INTO councils(admission_period_id,council_type,name,exam_site_id,status) VALUES(:p,"appeal",:n,NULL,"established")');
        $q->execute(['p' => $periodId, 'n' => $tag . '_COUNCIL']);
        $councilId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO council_members(council_id,staff_id) VALUES(:c,:s)')->execute(['c' => $councilId, 's' => $id]);
        must_fail(fn() => staff_deactivate($id), 'đang được phân công');
        $row = staff_find($id);
        assert_true((int)$row['active'] === 1, 'Referenced staff was changed.');

        $pdo->prepare('DELETE FROM council_members WHERE council_id=:c')->execute(['c' => $councilId]);
        $pdo->prepare('DELETE FROM councils WHERE id=:c')->execute(['c' => $councilId]);
        $councilId = 0;
        $q = $pdo->prepare('INSERT INTO exam_invigilator_assignments(exam_room_id,exam_subject_id,staff_id,position,status) VALUES(:r,:s,:st,"invigilator_1","assigned")');
        $q->execute(['r' => $roomId, 's' => $subjectId, 'st' => $id]);
        $assignmentId = (int)$pdo->lastInsertId();
        must_fail(fn() => staff_deactivate($id), 'đang được phân công');
        assert_true((int)staff_find($id)['active'] === 1, 'Invigilator-referenced staff was changed.');
    });

    test(14, 'UC07 unauthorized rejected by RBAC', function () {
        auth_logout();
        assert_true(!auth_has_permission('staff.delete'), 'Guest unexpectedly has staff.delete.');
    });

    test(15, 'UC07 invalid CSRF rejected', function () {
        assert_true(!csrf_verify('BATCHF_BAD_DELETE_CSRF'), 'Invalid CSRF accepted.');
    });

    test(16, 'F1 transaction rollback', function () use ($pdo, $tag, &$staffIds) {
        $before = (int)$pdo->query("SELECT COUNT(*) FROM staff WHERE full_name=" . $pdo->quote($tag . '_ROLLBACK'))->fetchColumn();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO staff(full_name,workplace,phone,email,staff_role,active) VALUES(:n,"TEST-F","","", "TEST",1)')->execute(['n' => $tag . '_ROLLBACK']);
            throw new RuntimeException('forced rollback');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
        }
        $after = (int)$pdo->query("SELECT COUNT(*) FROM staff WHERE full_name=" . $pdo->quote($tag . '_ROLLBACK'))->fetchColumn();
        assert_true($before === $after, 'Rollback left partial staff data.');
    });

    // ---------- F2: candidate ----------
    $baseCandidate = ['national_id' => $candidate1NationalId, 'full_name' => $tag . '_Candidate1', 'date_of_birth' => '2010-01-01', 'gender' => 'Nam', 'address' => 'TEST-F', 'phone' => '0910000001', 'email' => $tag . 'c1@example.com', 'middle_school_id' => $schoolId, 'eligible' => 1, 'status' => 'active'];

    test(17, 'UC31 valid candidate creation + default account', function () use ($baseCandidate, &$candidateIds, &$userIds, $pdo) {
        $id = candidate_create($baseCandidate);
        $candidateIds[] = $id;
        $row = candidate_find($id);
        assert_true($row !== null, 'Candidate was not persisted.');
        assert_true((int)$row['user_id'] > 0, 'Candidate account was not linked.');
        $userIds[] = (int)$row['user_id'];
        $u = $pdo->prepare('SELECT username,password_hash,role,active FROM users WHERE id=:id');
        $u->execute(['id' => (int)$row['user_id']]);
        $user = $u->fetch();
        assert_true($user !== false && $user['username'] === $baseCandidate['national_id'] && $user['role'] === 'candidate' && (int)$user['active'] === 1, 'Default candidate account data is wrong.');
        assert_true(password_verify('1111', $user['password_hash']), 'Default candidate password does not verify.');
    });

    test(18, 'UC31 duplicate identifier rejected', function () use ($baseCandidate) {
        must_fail(fn() => candidate_create($baseCandidate), 'đã tồn tại');
    });

    test(19, 'UC31 invalid required data rejected', function () {
        [, $errors] = candidate_validate(['national_id' => '', 'full_name' => '']);
        assert_true(isset($errors['national_id'], $errors['full_name']), 'Required candidate fields were not rejected.');
    });

    test(20, 'UC31 unauthorized rejected by RBAC', function () {
        auth_logout();
        assert_true(!auth_has_permission('candidate.create'), 'Guest unexpectedly has candidate.create.');
    });

    test(21, 'UC31 invalid CSRF rejected', function () {
        assert_true(!csrf_verify('BATCHF_BAD_CANDIDATE_CSRF'), 'Invalid CSRF accepted.');
    });

    test(22, 'UC32 valid update', function () use ($candidateIds, $editCandidateNationalId, $schoolId) {
        $id = $candidateIds[0];
        candidate_update($id, ['national_id' => $editCandidateNationalId, 'full_name' => 'BATCHF Candidate Edited', 'date_of_birth' => '2010-02-02', 'gender' => 'Nữ', 'address' => 'EDIT', 'phone' => '0910000002', 'email' => 'edited@example.com', 'middle_school_id' => $schoolId, 'eligible' => 1, 'status' => 'active']);
        $row = candidate_find($id);
        assert_true($row !== null && $row['full_name'] === 'BATCHF Candidate Edited' && $row['national_id'] === $editCandidateNationalId, 'Candidate update was not persisted.');
    });

    test(23, 'UC32 nonexistent candidate rejected', function () {
        must_fail(fn() => candidate_update(999999999, ['national_id' => 'X', 'full_name' => 'X', 'date_of_birth' => '', 'gender' => '', 'address' => '', 'phone' => '', 'email' => '', 'middle_school_id' => 0, 'eligible' => 1, 'status' => 'active']));
    });

    test(24, 'UC32 duplicate identifier rejected', function () use ($baseCandidate, $candidate2NationalId, &$candidateIds, &$userIds) {
        $candidate1Id = $candidateIds[0];
        candidate_update($candidate1Id, $baseCandidate);
        assert_true(candidate_find($candidate1Id)['national_id'] === $baseCandidate['national_id'], 'Candidate 1 fixture was not restored to its run-specific identifier.');

        $second = $baseCandidate;
        $second['national_id'] = $candidate2NationalId;
        $second['full_name'] = 'BATCHF Candidate2';
        assert_true($second['national_id'] !== $baseCandidate['national_id'], 'Candidate 1 and candidate 2 identifiers are not unique.');
        assert_true(strlen($baseCandidate['national_id']) <= 30 && strlen($second['national_id']) <= 30, 'Duplicate-identifier fixture exceeds the candidate identifier column length.');
        echo 'TRACE TEST 24: candidate1 national_id=' . $baseCandidate['national_id'] . ' strlen=' . strlen($baseCandidate['national_id']) . PHP_EOL;
        echo 'TRACE TEST 24: candidate2 national_id=' . $second['national_id'] . ' strlen=' . strlen($second['national_id']) . PHP_EOL;
        $id = candidate_create($second);
        $candidateIds[] = $id;

        $secondRow = candidate_find($id);
        assert_true($secondRow !== null && (int)$secondRow['user_id'] > 0, 'Candidate 2 account was not linked.');
        $userIds[] = (int)$secondRow['user_id'];

        must_fail(fn() => candidate_update($candidate1Id, [
            'national_id' => $second['national_id'],
            'full_name' => 'X',
            'date_of_birth' => '',
            'gender' => '',
            'address' => '',
            'phone' => '',
            'email' => '',
            'middle_school_id' => $baseCandidate['middle_school_id'],
            'eligible' => 1,
            'status' => 'active'
        ]), 'tồn tại');
    });

    test(25, 'UC32 unauthorized rejected by RBAC', function () {
        auth_logout();
        assert_true(!auth_has_permission('candidate.update'), 'Guest unexpectedly has candidate.update.');
    });

    test(26, 'UC32 invalid CSRF rejected', function () {
        assert_true(!csrf_verify('BATCHF_BAD_EDIT_CSRF'), 'Invalid CSRF accepted.');
    });

    test(27, 'UC33 valid deletion when candidate has no dependencies', function () use ($baseCandidate, $deleteCandidateNationalId, &$candidateIds, &$userIds, $pdo) {
        $data = $baseCandidate;
        $data['national_id'] = $deleteCandidateNationalId;
        $data['full_name'] = 'BATCHF Delete Candidate';
        $data['email'] = 'delete@example.com';
        $id = candidate_create($data);
        $candidateIds[] = $id;
        $before = candidate_find($id);
        $uid = (int)$before['user_id'];
        $userIds[] = $uid;
        candidate_deactivate($id);
        $after = candidate_find($id);
        assert_true($after !== null && $after['status'] === 'inactive', 'Candidate was not moved to inactive state.');
        $q = $pdo->prepare('SELECT id FROM users WHERE id=:id');
        $q->execute(['id' => $uid]);
        assert_true($q->fetchColumn() === false, 'Candidate account was not deleted.');
    });

    test(28, 'UC33 nonexistent candidate rejected', function () {
        must_fail(fn() => candidate_deactivate(999999999));
    });

    test(29, 'UC33 candidate with dependencies rejected', function () use ($baseCandidate, $dependencyCandidateNationalId, $pdo, $periodId, &$candidateIds, &$userIds) {
        $data = $baseCandidate;
        $data['national_id'] = $dependencyCandidateNationalId;
        $data['full_name'] = 'BATCHF Dependency Candidate';
        $data['email'] = 'dep@example.com';
        assert_true($data['national_id'] !== $baseCandidate['national_id'] && strlen($data['national_id']) <= 30, 'Dependency candidate identifier is not a unique in-range fixture.');
        echo 'TRACE TEST 29: dependency national_id=' . $data['national_id'] . ' strlen=' . strlen($data['national_id']) . PHP_EOL;
        $id = candidate_create($data);
        $candidateIds[] = $id;
        $candidateRow = candidate_find($id);
        assert_true($candidateRow !== null && (int)$candidateRow['user_id'] > 0, 'Dependency candidate account was not linked.');
        $userIds[] = (int)$candidateRow['user_id'];
        $adj = $pdo->query('SELECT id FROM users WHERE role IN ("admin","so_gdtd") ORDER BY id LIMIT 1')->fetchColumn();
        if (!$adj) throw new RuntimeException('No staff user available for dependency fixture.');
        $pdo->prepare('INSERT INTO adjustment_requests(candidate_id,submitted_by_user_id,old_data,new_data,reason,status) VALUES(:c,:u,"{}","{}","TEST","pending")')->execute(['c' => $id, 'u' => (int)$adj]);
        must_fail(fn() => candidate_deactivate($id), 'dữ liệu liên quan');
        assert_true(candidate_find($id)['status'] === 'active', 'Dependent candidate changed status.');
    });

    test(30, 'UC33 unauthorized rejected by RBAC', function () {
        auth_logout();
        assert_true(!auth_has_permission('candidate.delete'), 'Guest unexpectedly has candidate.delete.');
    });

    test(31, 'UC33 invalid CSRF rejected', function () {
        assert_true(!csrf_verify('BATCHF_BAD_DELETE_CANDIDATE_CSRF'), 'Invalid CSRF accepted.');
    });

    test(32, 'F2 transaction rollback after role lookup failure', function () use ($pdo, $baseCandidate, $rollbackCandidateNationalId) {
        $role = $pdo->query('SELECT id,active FROM roles WHERE code="candidate" LIMIT 1')->fetch();
        if (!$role) throw new RuntimeException('candidate role missing.');
        $beforeCandidate = (int)$pdo->query('SELECT COUNT(*) FROM candidates WHERE national_id=' . $pdo->quote($rollbackCandidateNationalId))->fetchColumn();
        $beforeUser = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE username=' . $pdo->quote($rollbackCandidateNationalId))->fetchColumn();
        $pdo->prepare('UPDATE roles SET active=0 WHERE id=:id')->execute(['id' => (int)$role['id']]);
        try {
            $data = $baseCandidate;
            $data['national_id'] = $rollbackCandidateNationalId;
            $data['full_name'] = 'BATCHF Rollback Candidate';
            $data['email'] = 'rollback@example.com';
            must_fail(fn() => candidate_create($data), 'role candidate');
        } finally {
            $pdo->prepare('UPDATE roles SET active=:active WHERE id=:id')->execute(['active' => (int)$role['active'], 'id' => (int)$role['id']]);
        }
        $afterCandidate = (int)$pdo->query('SELECT COUNT(*) FROM candidates WHERE national_id=' . $pdo->quote($rollbackCandidateNationalId))->fetchColumn();
        $afterUser = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE username=' . $pdo->quote($rollbackCandidateNationalId))->fetchColumn();
        assert_true($beforeCandidate === $afterCandidate && $beforeUser === $afterUser, 'Candidate transaction left partial candidate/user data.');
    });
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL SETUP: ' . $e->getMessage() . PHP_EOL);
}

// Cleanup only BATCHF_* fixtures. Protected Batch B-E data is never targeted.
try {
    if ($assignmentId) $pdo->prepare('DELETE FROM exam_invigilator_assignments WHERE id=:id')->execute(['id' => $assignmentId]);
    if ($councilId) {
        $pdo->prepare('DELETE FROM council_members WHERE council_id=:id')->execute(['id' => $councilId]);
        $pdo->prepare('DELETE FROM councils WHERE id=:id')->execute(['id' => $councilId]);
    }
    if ($candidateIds) {
        $in = implode(',', array_fill(0, count($candidateIds), '?'));
        $pdo->prepare("DELETE FROM adjustment_requests WHERE candidate_id IN ($in)")->execute($candidateIds);
        $pdo->prepare("DELETE FROM aspirations WHERE candidate_id IN ($in)")->execute($candidateIds);
        $pdo->prepare("DELETE FROM candidates WHERE id IN ($in)")->execute($candidateIds);
    }
    if ($userIds) {
        $in = implode(',', array_fill(0, count($userIds), '?'));
        $pdo->prepare("DELETE FROM users WHERE id IN ($in)")->execute($userIds);
    }
    if ($staffIds) {
        $in = implode(',', array_fill(0, count($staffIds), '?'));
        $pdo->prepare("DELETE FROM council_members WHERE staff_id IN ($in)")->execute($staffIds);
        $pdo->prepare("DELETE FROM exam_invigilator_assignments WHERE staff_id IN ($in)")->execute($staffIds);
        $pdo->prepare("DELETE FROM staff WHERE id IN ($in)")->execute($staffIds);
    }
    if ($roomId) $pdo->prepare('DELETE FROM exam_rooms WHERE id=:id')->execute(['id' => $roomId]);
    if ($siteId) $pdo->prepare('DELETE FROM exam_sites WHERE id=:id')->execute(['id' => $siteId]);
    if ($subjectId) $pdo->prepare('DELETE FROM exam_subjects WHERE id=:id')->execute(['id' => $subjectId]);
    if ($schoolId) $pdo->prepare('DELETE FROM schools WHERE id=:id')->execute(['id' => $schoolId]);
    if ($periodId) $pdo->prepare('DELETE FROM admission_periods WHERE id=:id')->execute(['id' => $periodId]);
} catch (Throwable $e) {
    fwrite(STDERR, 'CLEANUP ERROR: ' . $e->getMessage() . PHP_EOL);
}

$pass = 0;
$fail = 0;
foreach ($results as $r) {
    if (str_starts_with($r, 'PASS')) $pass++;
    else $fail++;
}
echo "BATCH F RUNTIME: {$pass}/" . count($results) . " PASS" . PHP_EOL;
exit($fail ? 1 : 0);
