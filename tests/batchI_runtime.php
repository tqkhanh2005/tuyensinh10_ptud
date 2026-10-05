<?php

declare(strict_types=1);

/**
 * Batch I runtime verification for UC03.
 * Run from project root:
 *   C:\\xampp\\php\\php.exe tests\\batchI_runtime.php
 *
 * Requires database/013_batchI_uc03.sql to have been applied.
 * Tests use tagged fixtures and clean only their own rows.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appeal_service.php';
require_once __DIR__ . '/../includes/excel_service.php';

$pdo = db();
$tag = 'BATCHI_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
$results = [];
$paper2 = 0;
$score2 = 0;
$ids = ['period' => 0, 'period2' => 0, 'school' => 0, 'subject' => 0, 'subject2' => 0, 'site' => 0, 'room' => 0, 'candidate' => 0, 'paper' => 0, 'score' => 0, 'candidateUser' => 0, 'graderUser' => 0, 'initialUser' => 0, 'managerUser' => 0, 'appeal' => 0, 'appeal2' => 0, 'appealCouncil' => 0, 'appealMember' => 0, 'initialCouncil' => 0, 'initialMember' => 0, 'initialAppealMember' => 0, 'staffGrader' => 0, 'staffInitial' => 0, 'candidate2' => 0, 'candidateUser2' => 0];

function assert_true(bool $v, string $m): void
{
    if (!$v) throw new RuntimeException($m);
}
function must_fail(callable $fn, string $contains = ''): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($contains !== '' && stripos($e->getMessage(), $contains) === false) throw new RuntimeException('Unexpected rejection: ' . $e->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection did not occur.');
}
function must_reject_validation(callable $fn, string $contains = ''): void
{
    $result = $fn();
    if (!is_array($result) || ($result['ok'] ?? true) !== false) throw new RuntimeException('Expected validation rejection did not occur.');
    $message = (string)($result['error'] ?? '');
    if ($contains !== '' && stripos($message, $contains) === false) throw new RuntimeException('Unexpected validation rejection: ' . $message);
}
function test(int $n, string $name, callable $fn): void
{
    global $results;
    try {
        $fn();
        $results[$n] = ['status' => 'PASS', 'name' => $name];
        echo "PASS TEST $n: $name" . PHP_EOL;
    } catch (LogicException $e) {
        $results[$n] = ['status' => 'BLOCKED', 'name' => $name, 'message' => $e->getMessage()];
        echo "BLOCKED TEST $n: $name — {$e->getMessage()}" . PHP_EOL;
    } catch (Throwable $e) {
        $results[$n] = ['status' => 'FAIL', 'name' => $name, 'message' => $e->getMessage()];
        echo "FAIL TEST $n: $name — {$e->getMessage()}" . PHP_EOL;
    }
}
function blocked(string $m): void
{
    throw new LogicException($m);
}
function scalar(PDO $pdo, string $sql, array $p = [])
{
    $q = $pdo->prepare($sql);
    $q->execute($p);
    return $q->fetchColumn();
}

/**
 * Execute appeals/update_result.php in an isolated PHP child so route-level
 * auth / upload handling is not bypassed by the service harness.
 *
 * @param array<string,mixed> $post
 * @param array<string,mixed> $session
 * @param array<string,mixed>|null $files One uploaded file descriptor for result_file, or null.
 * @return array{output:string,session:array,headers:array<int,string>,exit_code:int}
 */
function run_uc03_route(array $post, array $session, ?array $files = null): array
{
    $root = dirname(__DIR__);
    $child = tempnam(sys_get_temp_dir(), 'batchI_child_') . '.php';
    $resultFile = tempnam(sys_get_temp_dir(), 'batchI_result_') . '.json';
    $sessionPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'batchI_session_' . bin2hex(random_bytes(8));
    if (!mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) {
        @unlink($child);
        @unlink($resultFile);
        throw new RuntimeException('Không thể tạo session store cho Batch I child process.');
    }

    $env = [
        'BATCHI_ROOT' => $root,
        'BATCHI_CONFIG' => $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.php',
        'BATCHI_TARGET' => $root . DIRECTORY_SEPARATOR . 'appeals' . DIRECTORY_SEPARATOR . 'update_result.php',
        'BATCHI_RESULT' => $resultFile,
        'BATCHI_SESSION_ID' => bin2hex(random_bytes(16)),
        'BATCHI_SESSION_PATH' => $sessionPath,
        'BATCHI_POST' => base64_encode(serialize($post)),
        'BATCHI_SESSION' => base64_encode(serialize($session)),
        'BATCHI_FILES' => base64_encode(serialize($files)),
    ];

    $code = <<<'PHP'
<?php
declare(strict_types=1);
$root = getenv('BATCHI_ROOT');
$config = getenv('BATCHI_CONFIG');
$target = getenv('BATCHI_TARGET');
$resultFile = getenv('BATCHI_RESULT');
$sessionId = getenv('BATCHI_SESSION_ID');
$sessionPath = getenv('BATCHI_SESSION_PATH');
if ($root === false || $config === false || $target === false || $resultFile === false || $sessionId === false || $sessionPath === false) {
    fwrite(STDERR, "Missing Batch I child environment.\n");
    exit(2);
}
$post = unserialize(base64_decode((string)getenv('BATCHI_POST'), true));
$session = unserialize(base64_decode((string)getenv('BATCHI_SESSION'), true));
$files = unserialize(base64_decode((string)getenv('BATCHI_FILES'), true));
if (!is_array($post) || !is_array($session)) {
    fwrite(STDERR, "Invalid Batch I child payload.\n");
    exit(2);
}
ob_start();
$headers = [];
register_shutdown_function(static function () use ($resultFile, &$headers): void {
    file_put_contents($resultFile, json_encode([
        'session' => $_SESSION ?? [],
        'output' => ob_get_contents() ?: '',
        'headers' => headers_list(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
});
session_save_path($sessionPath);
session_id($sessionId);
require $config;
$_SESSION = $session;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $post;
$_FILES = [];
if (is_array($files) && isset($files['tmp_name'], $files['name'])) {
    $_FILES['result_file'] = [
        'name' => (string)$files['name'],
        'type' => (string)($files['type'] ?? 'application/octet-stream'),
        'tmp_name' => (string)$files['tmp_name'],
        'error' => UPLOAD_ERR_OK,
        'size' => is_file((string)$files['tmp_name']) ? filesize((string)$files['tmp_name']) : 0,
    ];
}
try {
    require $target;
} catch (Throwable $e) {
    echo $e->getMessage();
}
PHP;

    $cleanup = static function () use ($child, $resultFile, $sessionPath): void {
        @unlink($child);
        @unlink($resultFile);
        if (is_dir($sessionPath)) {
            foreach (glob($sessionPath . DIRECTORY_SEPARATOR . 'sess_*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($sessionPath);
        }
    };

    if (file_put_contents($child, $code) === false) {
        $cleanup();
        throw new RuntimeException('Không thể tạo Batch I child script.');
    }

    $processEnv = getenv();
    if (!is_array($processEnv)) {
        $processEnv = [];
    }
    foreach ($env as $key => $value) {
        $processEnv[$key] = $value;
    }

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open([PHP_BINARY, $child], $descriptors, $pipes, $root, $processEnv);
    if (!is_resource($process)) {
        $cleanup();
        throw new RuntimeException('Không thể khởi chạy Batch I child process.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $data = is_file($resultFile) ? json_decode((string)file_get_contents($resultFile), true) : null;
    $cleanup();
    if (!is_array($data)) {
        throw new RuntimeException('Child UC03 route did not return data. STDERR: ' . trim((string)$stderr) . ' STDOUT: ' . trim((string)$stdout));
    }

    return [
        'output' => (string)($data['output'] ?? ''),
        'session' => is_array($data['session'] ?? null) ? $data['session'] : [],
        'headers' => is_array($data['headers'] ?? null) ? $data['headers'] : [],
        'exit_code' => $exitCode,
    ];
}

try {
    // ---------- Fixture ----------
    $pdo->beginTransaction();
    $q = $pdo->prepare('INSERT INTO admission_periods(name,academic_year,registration_start,registration_end,status) VALUES(:n,"2035","2035-01-01 00:00:00","2035-12-31 23:59:59","published")');
    $q->execute(['n' => $tag . ' Period A']);
    $ids['period'] = (int)$pdo->lastInsertId();
    $q->execute(['n' => $tag . ' Period B']);
    $ids['period2'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO schools(school_code,name,level,active,admission_area) VALUES(:c,:n,"THCS",1,"TEST")');
    $q->execute(['c' => $tag . '_S', 'n' => $tag . ' School']);
    $ids['school'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,component_count,status) VALUES(:p,:c,:n,"2035-06-01","08:00:00","10:00:00",10,1,"scheduled")');
    $q->execute(['p' => $ids['period'], 'c' => $tag . '_M', 'n' => $tag . ' Subject']);
    $ids['subject'] = (int)$pdo->lastInsertId();
    $q->execute(['p' => $ids['period2'], 'c' => $tag . '_M2', 'n' => $tag . ' Subject B']);
    $ids['subject2'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO exam_sites(admission_period_id,school_id,name,address,status) VALUES(:p,:s,:n,"TEST","active")');
    $q->execute(['p' => $ids['period'], 's' => $ids['school'], 'n' => $tag . ' Site']);
    $ids['site'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO exam_rooms(exam_site_id,room_code,capacity,status) VALUES(:s,:c,30,"ready")');
    $q->execute(['s' => $ids['site'], 'c' => $tag . '_R']);
    $ids['room'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO users(username,password_hash,full_name,role,active) VALUES(:u,:h,:n,"candidate",1)');
    $q->execute(['u' => $tag . '_candidate', 'h' => password_hash('Test1!', PASSWORD_DEFAULT), 'n' => $tag . ' Candidate']);
    $ids['candidateUser'] = (int)$pdo->lastInsertId();
    $q->execute(['u' => $tag . '_grader', 'h' => password_hash('Test1!', PASSWORD_DEFAULT), 'n' => $tag . ' Grader']);
    $ids['graderUser'] = (int)$pdo->lastInsertId();
    $q->execute(['u' => $tag . '_initial', 'h' => password_hash('Test1!', PASSWORD_DEFAULT), 'n' => $tag . ' Initial']);
    $ids['initialUser'] = (int)$pdo->lastInsertId();
    $q->execute(['u' => $tag . '_manager', 'h' => password_hash('Test1!', PASSWORD_DEFAULT), 'n' => $tag . ' Manager']);
    $ids['managerUser'] = (int)$pdo->lastInsertId();
    $q->execute(['u' => $tag . '_candidate2', 'h' => password_hash('Test1!', PASSWORD_DEFAULT), 'n' => $tag . ' Candidate 2']);
    $ids['candidateUser2'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO staff(full_name,workplace,staff_role,active,user_id) VALUES(:n,"TEST","TEST",1,:u)');
    $q->execute(['n' => $tag . ' Grader', 'u' => $ids['graderUser']]);
    $ids['staffGrader'] = (int)$pdo->lastInsertId();
    $q->execute(['n' => $tag . ' Initial', 'u' => $ids['initialUser']]);
    $ids['staffInitial'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO councils(admission_period_id,council_type,name,status) VALUES(:p,"appeal",:n,"established")');
    $q->execute(['p' => $ids['period'], 'n' => $tag . ' Appeal Council']);
    $ids['appealCouncil'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO councils(admission_period_id,council_type,name,status) VALUES(:p,"grading",:n,"established")');
    $q->execute(['p' => $ids['period'], 'n' => $tag . ' Grading Council']);
    $ids['initialCouncil'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO council_subjects(council_id,exam_subject_id) VALUES(:c,:s)');
    $q->execute(['c' => $ids['appealCouncil'], 's' => $ids['subject']]);
    $q->execute(['c' => $ids['initialCouncil'], 's' => $ids['subject']]);
    $q = $pdo->prepare('INSERT INTO council_members(council_id,staff_id,role_name,task_name) VALUES(:c,:s,"grader","appeal")');
    $q->execute(['c' => $ids['appealCouncil'], 's' => $ids['staffGrader']]);
    $ids['appealMember'] = (int)$pdo->lastInsertId();
    $q->execute(['c' => $ids['initialCouncil'], 's' => $ids['staffInitial']]);
    $ids['initialMember'] = (int)$pdo->lastInsertId();
    // Isolated TEST 7 fixture: same initial-grader user is also a valid member of the appeal council.
    // Production assignment service correctly refuses this conflict; the test temporarily installs the
    // assignment directly so complete_appeal() can verify the independent initial-grader guard.
    $q->execute(['c' => $ids['appealCouncil'], 's' => $ids['staffInitial']]);
    $ids['initialAppealMember'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO candidates(national_id,full_name,user_id,middle_school_id,status) VALUES(:n,:f,:u,:s,"active")');

    $q->execute([
        'n' => $tag . '1',
        'f' => $tag . ' Candidate 1',
        'u' => $ids['candidateUser'],
        's' => $ids['school']
    ]);

    $ids['candidate'] = (int)$pdo->lastInsertId();

    $q->execute([
        'n' => $tag . '2',
        'f' => $tag . ' Candidate 2',
        'u' => $ids['candidateUser2'],
        's' => $ids['school']
    ]);

    $ids['candidate2'] = (int)$pdo->lastInsertId();

    $q = $pdo->prepare('INSERT INTO exam_papers(admission_period_id,candidate_id,exam_subject_id,exam_room_id,attendance_status,anonymous_code,paper_status) VALUES(:p,:c,:s,:r,"present",:code,"scored")');
    $q->execute([
        'p' => $ids['period'],
        'c' => $ids['candidate'],
        's' => $ids['subject'],
        'r' => $ids['room'],
        'code' => $tag . '_P'
    ]);
    $ids['paper'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO scores(exam_paper_id,anonymous_code,score_1,score_2,total_score,entered_by) VALUES(:p,:code,7.5,NULL,7.5,:u)');
    $q->execute(['p' => $ids['paper'], 'code' => $tag . '_P', 'u' => $ids['initialUser']]);
    $ids['score'] = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO exam_paper_grader_assignments(exam_paper_id,council_member_id,assigned_by_user_id) VALUES(:p,:m,:u)');
    $q->execute(['p' => $ids['paper'], 'm' => $ids['initialMember'], 'u' => $ids['managerUser']]);
    $q = $pdo->prepare('INSERT INTO appeal_requests(admission_period_id,candidate_id,exam_paper_id,exam_subject_id,original_score,reason,payment_status,status) VALUES(:p,:c,:paper,:s,7.5,"TEST appeal","not_required","pending")');
    $q->execute(['p' => $ids['period'], 'c' => $ids['candidate'], 'paper' => $ids['paper'], 's' => $ids['subject']]);
    $ids['appeal'] = (int)$pdo->lastInsertId();
    $pdo->commit();

    // A Assignment / initial grader
    test(1, 'Appeal can be assigned to a council member', function () use ($pdo, $ids) {
        assign_appeal_grader($pdo, $ids['appeal'], $ids['appealMember'], $ids['managerUser']);
        assert_true((int)scalar($pdo, 'SELECT council_member_id FROM appeal_grader_assignments WHERE appeal_request_id=:a', ['a' => $ids['appeal']]) === $ids['appealMember'], 'Assignment not stored.');
    });
    test(2, 'Assigned grader can start grading', function () use ($pdo, $ids) {
        set_appeal_grading($pdo, $ids['appeal'], $ids['graderUser']);
        assert_true(scalar($pdo, 'SELECT status FROM appeal_requests WHERE id=:a', ['a' => $ids['appeal']]) === 'grading', 'Status not grading.');
    });
    test(3, 'Unassigned user rejected', function () use ($pdo, $ids) {
        must_reject_validation(fn() => validate_appeal_result($pdo, $ids['appeal'], 8.0, 'x', $ids['managerUser']), 'chưa được phân công');
    });
    test(4, 'Missing authenticated user is rejected by route contract', function () {
        $result = run_uc03_route(
            ['action' => 'complete', 'appeal_id' => 1, 'new_score' => '8', 'reason' => 'x', 'csrf_token' => 'x'],
            []
        );
        $headerBlob = implode("\n", $result['headers']);
        $redirected = stripos($headerBlob, 'auth/login') !== false;
        $noAuth = empty($result['session']['auth']);
        $didNotComplete = stripos($result['output'], 'Đã cập nhật kết quả phúc khảo') === false
            && stripos($result['output'], 'Đã phân công') === false;
        assert_true($noAuth, 'Unauthenticated route unexpectedly created an auth session.');
        assert_true($didNotComplete, 'Unauthenticated route completed a UC03 mutation.');
        assert_true($redirected || $result['output'] === '' || stripos($result['output'], '403') !== false, 'Unauthenticated UC03 route was not rejected by auth gate.');
    });
    test(5, 'No assignment is rejected', function () use ($pdo, $ids) {
        $pdo->prepare('DELETE FROM appeal_grader_assignments WHERE appeal_request_id=:a')->execute(['a' => $ids['appeal']]);
        try {
            must_reject_validation(fn() => validate_appeal_result($pdo, $ids['appeal'], 8.0, 'x', $ids['graderUser']), 'chưa được phân công');
        } finally {
            $pdo->prepare('INSERT INTO appeal_grader_assignments(appeal_request_id,council_member_id,assigned_by_user_id) VALUES(:a,:m,:u)')->execute(['a' => $ids['appeal'], 'm' => $ids['appealMember'], 'u' => $ids['managerUser']]);
        }
    });
    test(6, 'Initial grader is explicitly identified', function () use ($pdo, $ids) {
        assert_true((int)scalar($pdo, 'SELECT COUNT(*) FROM exam_paper_grader_assignments WHERE exam_paper_id=:p', ['p' => $ids['paper']]) === 1, 'Initial grader mapping missing.');
    });
    test(7, 'Initial grader cannot grade appeal', function () use ($pdo, $ids) {
        $pdo->prepare('UPDATE appeal_grader_assignments SET council_member_id=:m,assigned_by_user_id=:u WHERE appeal_request_id=:a')
            ->execute(['m' => $ids['initialAppealMember'], 'u' => $ids['managerUser'], 'a' => $ids['appeal']]);
        try {
            must_fail(fn() => complete_appeal($pdo, $ids['appeal'], 8.0, 'x', $ids['initialUser']), 'Người chấm bài ban đầu');
        } finally {
            $pdo->prepare('UPDATE appeal_grader_assignments SET council_member_id=:m,assigned_by_user_id=:u WHERE appeal_request_id=:a')
                ->execute(['m' => $ids['appealMember'], 'u' => $ids['managerUser'], 'a' => $ids['appeal']]);
        }
    });
    test(8, 'Missing initial grader is not bypassed', function () use ($pdo, $ids) {
        $pdo->prepare('DELETE FROM exam_paper_grader_assignments WHERE exam_paper_id=:p')->execute(['p' => $ids['paper']]);
        try {
            must_reject_validation(fn() => validate_appeal_result($pdo, $ids['appeal'], 8.0, 'x', $ids['graderUser']), 'MISSING SPECIFICATION / DATA DEPENDENCY');
        } finally {
            $pdo->prepare('INSERT INTO exam_paper_grader_assignments(exam_paper_id,council_member_id,assigned_by_user_id) VALUES(:p,:m,:u)')->execute(['p' => $ids['paper'], 'm' => $ids['initialMember'], 'u' => $ids['managerUser']]);
        }
    });

    // C/D/E
    test(9, 'CSRF valid token helper', function () {
        $_SESSION['csrf_token'] = 'uc03-test';
        assert_true(csrf_verify('uc03-test'), 'Valid CSRF rejected.');
    });
    test(10, 'CSRF missing rejected', function () {
        $_SESSION['csrf_token'] = 'uc03-test';
        assert_true(!csrf_verify(null), 'Missing CSRF accepted.');
    });
    test(11, 'CSRF wrong rejected', function () {
        $_SESSION['csrf_token'] = 'uc03-test';
        assert_true(!csrf_verify('wrong'), 'Wrong CSRF accepted.');
    });
    test(12, 'grading to completed succeeds for unchanged score', function () use ($pdo, $ids) {
        complete_appeal($pdo, $ids['appeal'], 7.5, 'No score change', $ids['graderUser']);
        assert_true(scalar($pdo, 'SELECT status FROM appeal_requests WHERE id=:a', ['a' => $ids['appeal']]) === 'completed', 'Appeal not completed.');
        assert_true((float)scalar($pdo, 'SELECT total_score FROM scores WHERE id=:s', ['s' => $ids['score']]) === 7.5, 'Official score changed unexpectedly.');
    });
    test(13, 'completed appeal cannot be updated again', function () use ($pdo, $ids) {
        must_fail(fn() => complete_appeal($pdo, $ids['appeal'], 8.0, 'again', $ids['graderUser']), 'phúc khảo');
    });
    test(14, 'Invalid appeal id rejected', function () use ($pdo, $ids) {
        must_reject_validation(fn() => validate_appeal_result($pdo, 999999, 8.0, 'x', $ids['graderUser']), 'Không tìm thấy');
    });

    // Second paper/appeal for score-change, transaction and publication-lock tests.
    $q = $pdo->prepare('INSERT INTO exam_papers(admission_period_id,candidate_id,exam_subject_id,exam_room_id,attendance_status,anonymous_code,paper_status) VALUES(:p,:c,:s,:r,"present",:code,"scored")');
    $q->execute(['p' => $ids['period'], 'c' => $ids['candidate2'], 's' => $ids['subject'], 'r' => $ids['room'], 'code' => $tag . '_P2']);
    $paper2 = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO scores(exam_paper_id,anonymous_code,score_1,score_2,total_score,entered_by) VALUES(:p,:code,7.5,NULL,7.5,:u)');
    $q->execute(['p' => $paper2, 'code' => $tag . '_P2', 'u' => $ids['initialUser']]);
    $score2 = (int)$pdo->lastInsertId();
    $q = $pdo->prepare('INSERT INTO exam_paper_grader_assignments(exam_paper_id,council_member_id,assigned_by_user_id) VALUES(:p,:m,:u)');
    $q->execute(['p' => $paper2, 'm' => $ids['initialMember'], 'u' => $ids['managerUser']]);
    $q = $pdo->prepare('INSERT INTO appeal_requests(admission_period_id,candidate_id,exam_paper_id,exam_subject_id,original_score,reason,payment_status,status) VALUES(:p,:c,:paper,:s,7.5,"TEST appeal 2","not_required","pending")');
    $q->execute(['p' => $ids['period'], 'c' => $ids['candidate2'], 'paper' => $paper2, 's' => $ids['subject']]);
    $ids['appeal2'] = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO appeal_grader_assignments(appeal_request_id,council_member_id,assigned_by_user_id) VALUES(:a,:m,:u)')->execute(['a' => $ids['appeal2'], 'm' => $ids['appealMember'], 'u' => $ids['managerUser']]);
    set_appeal_grading($pdo, $ids['appeal2'], $ids['graderUser']);
    test(15, 'new_score != old_score updates official score and history', function () use ($pdo, $ids, $paper2, $score2) {
        complete_appeal($pdo, $ids['appeal2'], 8.25, 'Score changed in appeal', $ids['graderUser']);
        assert_true((float)scalar($pdo, 'SELECT total_score FROM scores WHERE id=:s', ['s' => $score2]) === 8.25, 'Official score was not updated.');
        $r = $pdo->query('SELECT old_score,new_score,changed_by_user_id,reason FROM score_change_logs WHERE appeal_request_id=' . (int)$ids['appeal2'])->fetch();
        assert_true(is_array($r) && (float)$r['old_score'] === 7.5 && (float)$r['new_score'] === 8.25 && (int)$r['changed_by_user_id'] === $ids['graderUser'] && $r['reason'] === 'Score changed in appeal', 'Score history incomplete.');
    });
    test(16, 'old score is preserved', function () use ($pdo, $ids) {
        $r = $pdo->query('SELECT old_score FROM appeal_results WHERE appeal_request_id=' . (int)$ids['appeal2'])->fetchColumn();
        assert_true((float)$r === 7.5, 'Old score was not preserved.');
    });
    test(17, 'duplicate appeal result is rejected', function () use ($pdo, $ids) {
        must_fail(fn() => complete_appeal($pdo, $ids['appeal2'], 8.5, 'duplicate', $ids['graderUser']), 'phúc khảo');
    });

    // TEST 37 verifies transaction rollback by comparing candidateUser2's notification
    // count before and after TEST 18. Do not assume the baseline is zero.
    $candidate2NotificationBaseline = null;

    test(18, 'publication lock blocks changed official score', function () use ($pdo, $ids, $paper2, &$candidate2NotificationBaseline) {
        $candidate2NotificationBaseline = (int)scalar(
            $pdo,
            'SELECT COUNT(*) FROM notifications WHERE user_id=:u AND title="Kết quả phúc khảo"',
            ['u' => $ids['candidateUser2']]
        );

        $pdo->prepare('UPDATE appeal_requests SET status="grading" WHERE id=:a')->execute(['a' => $ids['appeal2']]);
        $pdo->prepare('DELETE FROM appeal_results WHERE appeal_request_id=:a')->execute(['a' => $ids['appeal2']]);
        $pdo->prepare('DELETE FROM score_change_logs WHERE appeal_request_id=:a')->execute(['a' => $ids['appeal2']]);
        $pdo->prepare('UPDATE scores SET total_score=7.5,score_1=7.5 WHERE exam_paper_id=:p')->execute(['p' => $paper2]);
        $pdo->prepare('INSERT INTO publication_events(admission_period_id,publication_type,status,published_by_user_id) VALUES(:p,"exam_results","published",:u)')->execute(['p' => $ids['period'], 'u' => $ids['managerUser']]);
        must_fail(fn() => complete_appeal($pdo, $ids['appeal2'], 8.0, 'published conflict', $ids['graderUser']), 'BLOCKED — OFFICIAL SCORE WORKFLOW');
    });
    test(19, 'publication conflict leaves appeal/result unchanged', function () use ($pdo, $ids, $paper2) {
        assert_true(scalar($pdo, 'SELECT status FROM appeal_requests WHERE id=:a', ['a' => $ids['appeal2']]) === 'grading', 'Appeal status changed after blocked update.');
        assert_true((int)scalar($pdo, 'SELECT COUNT(*) FROM appeal_results WHERE appeal_request_id=:a', ['a' => $ids['appeal2']]) === 0, 'Partial result was persisted.');
        assert_true((float)scalar($pdo, 'SELECT total_score FROM scores WHERE exam_paper_id=:p', ['p' => $paper2]) === 7.5, 'Score changed despite publication block.');
    });
    test(20, 'protected INSERT trigger remains present', function () use ($pdo) {
        assert_true((int)scalar($pdo, "SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='trg_scores_lock_after_exam_publication'") === 1, 'INSERT trigger missing.');
    });
    test(21, 'protected UPDATE trigger remains present', function () use ($pdo) {
        assert_true((int)scalar($pdo, "SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='trg_scores_update_lock_after_exam_publication'") === 1, 'UPDATE trigger missing.');
    });
    test(22, 'protected DELETE trigger remains present', function () use ($pdo) {
        assert_true((int)scalar($pdo, "SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='trg_scores_delete_lock_after_exam_publication'") === 1, 'DELETE trigger missing.');
    });
    test(23, 'direct UPDATE after publication is rejected by trigger', function () use ($pdo, $ids, $paper2) {
        must_fail(fn() => $pdo->prepare('UPDATE scores SET total_score=7.0 WHERE exam_paper_id=:p')->execute(['p' => $paper2]), 'Điểm thi đã công bố');
    });
    test(24, 'direct DELETE after publication is rejected by trigger', function () use ($pdo, $ids, $paper2) {
        must_fail(fn() => $pdo->prepare('DELETE FROM scores WHERE exam_paper_id=:p')->execute(['p' => $paper2]), 'Điểm thi đã công bố');
    });
    test(25, 'direct INSERT after publication is rejected by trigger', function () use ($pdo, $ids, $paper2, $tag) {
        $pdo->prepare('DELETE FROM publication_events WHERE admission_period_id=:p')->execute(['p' => $ids['period']]);
        $pdo->prepare('INSERT INTO publication_events(admission_period_id,publication_type,status,published_by_user_id) VALUES(:p,"exam_results","published",:u)')->execute(['p' => $ids['period'], 'u' => $ids['managerUser']]);
        $newPaper = $pdo->query('SELECT id,anonymous_code FROM exam_papers WHERE id=' . (int)$paper2)->fetch();
        must_fail(fn() => $pdo->prepare('INSERT INTO scores(exam_paper_id,anonymous_code,total_score,entered_by) VALUES(:p,:c,1,:u)')->execute(['p' => $newPaper['id'], 'c' => $tag . '_DUP', 'u' => $ids['graderUser']]), 'Điểm thi đã công bố');
    });
    test(26, 'transaction rollback after publication conflict', function () use ($pdo, $ids) {
        assert_true(scalar($pdo, 'SELECT status FROM appeal_requests WHERE id=:a', ['a' => $ids['appeal2']]) === 'grading', 'Rollback did not preserve grading state.');
    });

    // Excel workflow infrastructure and UC03 structure.
    test(27, 'XLSX template can be generated', function () {
        $b = excel_xlsx_bytes(UC03_HEADERS, [[1, 'P', 'SBD', 7.5, 'grader', 8.0, 'reason']]);
        assert_true(strlen($b) > 100, 'XLSX bytes empty.');
    });
    test(28, 'CSV export helper is available', function () {
        assert_true(function_exists('excel_csv_download'), 'CSV export helper missing.');
    });
    test(29, 'XLSX upload parsing works', function () {
        $path = tempnam(sys_get_temp_dir(), 'uc03_');
        file_put_contents($path, excel_xlsx_bytes(UC03_HEADERS, [[1, 'P', 'SBD', 7.5, 'grader', 8.0, 'reason']]));
        $rows = excel_parse_upload($path, 'uc03.xlsx');
        unlink($path);
        assert_true(count($rows) === 2, 'XLSX parse row count wrong.');
    });
    test(30, 'CSV upload parsing works', function () {
        $path = tempnam(sys_get_temp_dir(), 'uc03_');
        file_put_contents($path, "appeal_id,Số phách,SBD,Điểm cũ,Người chấm phúc khảo,Điểm mới,Lý do/biên bản thay đổi\n1,P,SBD,7.5,grader,8.0,reason\n");
        $rows = excel_parse_upload($path, 'uc03.csv');
        unlink($path);
        assert_true(count($rows) === 2, 'CSV parse row count wrong.');
    });
    test(31, 'wrong Excel headers are rejected', function () use ($pdo, $ids, $tag) {
        must_fail(
            fn() => uc03_validate_import(
                $pdo,
                $ids['period'],
                $ids['subject'],
                [
                    ['wrong', 'headers', 'only'],
                    ['1', 'P', 'SBD', '7.5', 'grader', '8.0', 'reason'],
                ],
                $ids['graderUser']
            ),
            'Sai cấu trúc cột'
        );

        // Ensure the route actor has the real RBAC permission used by update_result.php.
        $roleId = (int)scalar($pdo, 'SELECT id FROM roles WHERE code="so_gdtd" AND active=1 LIMIT 1');
        $permId = (int)scalar($pdo, 'SELECT id FROM permissions WHERE code="appeal.manage" LIMIT 1');
        assert_true($roleId > 0 && $permId > 0, 'RBAC seeds for appeal.manage / so_gdtd are missing.');
        $pdo->prepare('INSERT IGNORE INTO role_permissions(role_id, permission_id) VALUES(:r,:p)')->execute(['r' => $roleId, 'p' => $permId]);
        $pdo->prepare('INSERT IGNORE INTO user_roles(user_id, role_id) VALUES(:u,:r)')->execute(['u' => $ids['managerUser'], 'r' => $roleId]);

        $bad = tempnam(sys_get_temp_dir(), 'uc03_bad_');
        file_put_contents($bad, "wrong,headers,only\n1,P,SBD,7.5,grader,8.0,reason\n");
        try {
            $result = run_uc03_route(
                [
                    'action' => 'upload_preview',
                    'period_id' => (string)$ids['period'],
                    'subject_id' => (string)$ids['subject'],
                    'csrf_token' => 'uc03-upload',
                ],
                [
                    'csrf_token' => 'uc03-upload',
                    'auth' => [
                        'user_id' => $ids['managerUser'],
                        'id' => $ids['managerUser'],
                        'username' => $tag . '_manager',
                        'full_name' => $tag . ' Manager',
                        'role' => 'so_gdtd',
                        'roles' => ['so_gdtd'],
                    ],
                ],
                ['name' => 'bad_headers.csv', 'type' => 'text/csv', 'tmp_name' => $bad]
            );
        } finally {
            @unlink($bad);
        }

        $preview = $result['session']['uc03_preview'] ?? null;
        assert_true($preview === null, 'Wrong-header upload unexpectedly created a preview session.');
        assert_true(
            stripos($result['output'], 'Sai cấu trúc cột') !== false,
            'Route upload did not reject wrong Excel headers. Output: ' . substr($result['output'], 0, 400)
        );
    });
    test(32, 'invalid row validation is exposed', function () {
        assert_true(function_exists('uc03_validate_import'), 'UC03 import validator missing.');
    });
    test(33, 'error export supports XLSX and CSV code paths', function () {
        assert_true(function_exists('excel_xlsx_download') && function_exists('excel_csv_download'), 'Export helpers missing.');
    });
    test(34, 'preview validation separates valid and invalid rows', function () use ($pdo, $ids) {
        $row = null;
        foreach (admin_appeals($pdo, $ids['period'], $ids['subject']) as $candidateRow) {
            if ((int)$candidateRow['id'] === $ids['appeal2']) {
                $row = $candidateRow;
                break;
            }
        }
        assert_true(is_array($row), 'Appeal fixture was not returned by admin query.');
        $parsed = [
            UC03_HEADERS,
            [
                (string)$ids['appeal2'],
                (string)$row['anonymous_code'],
                (string)$row['candidate_code'],
                (string)$row['original_score'],
                (string)$row['assigned_grader_username'],
                '8.0',
                'Valid preview row',
            ],
            [
                (string)$ids['appeal2'],
                (string)$row['anonymous_code'],
                (string)$row['candidate_code'],
                (string)$row['original_score'],
                (string)$row['assigned_grader_username'],
                'not-a-score',
                '',
            ],
        ];
        $preview = uc03_validate_import($pdo, $ids['period'], $ids['subject'], $parsed, $ids['graderUser']);
        assert_true(count($preview['valid']) === 1, 'Expected one valid preview row.');
        assert_true(count($preview['errors']) === 1, 'Expected one invalid preview row.');
        assert_true(str_contains($preview['errors'][0]['error'], 'Điểm mới không phải số'), 'Invalid row reason was not preserved.');
    });
    test(35, 'confirm import rejects a missing or stale preview through route', function () use ($pdo, $ids, $tag) {
        $roleId = (int)scalar($pdo, 'SELECT id FROM roles WHERE code="so_gdtd" AND active=1 LIMIT 1');
        $permId = (int)scalar($pdo, 'SELECT id FROM permissions WHERE code="appeal.manage" LIMIT 1');
        assert_true($roleId > 0 && $permId > 0, 'RBAC seeds for route confirmation are missing.');
        $pdo->prepare('INSERT IGNORE INTO role_permissions(role_id, permission_id) VALUES(:r,:p)')->execute(['r' => $roleId, 'p' => $permId]);
        $pdo->prepare('INSERT IGNORE INTO user_roles(user_id, role_id) VALUES(:u,:r)')->execute(['u' => $ids['managerUser'], 'r' => $roleId]);

        $result = run_uc03_route(
            [
                'action' => 'confirm_import',
                'period_id' => (string)$ids['period'],
                'subject_id' => (string)$ids['subject'],
                'csrf_token' => 'uc03-confirm',
            ],
            [
                'csrf_token' => 'uc03-confirm',
                'auth' => [
                    'user_id' => $ids['managerUser'],
                    'id' => $ids['managerUser'],
                    'username' => $tag . '_manager',
                    'full_name' => $tag . ' Manager',
                    'role' => 'so_gdtd',
                    'roles' => ['so_gdtd'],
                ],
            ]
        );
        assert_true(str_contains($result['output'], 'Preview không hợp lệ'), 'Route accepted confirm without a preview.');
        assert_true(
            scalar($pdo, 'SELECT status FROM appeal_requests WHERE id=:a', ['a' => $ids['appeal2']]) === 'grading',
            'Route guard changed the appeal state.'
        );
    });
    test(36, 'batch completion rolls back on any row error', function () use ($pdo, $ids) {
        // appeal2 is left in grading with no results after the publication-lock suite.
        assert_true(
            scalar($pdo, 'SELECT status FROM appeal_requests WHERE id=:a', ['a' => $ids['appeal2']]) === 'grading',
            'appeal2 fixture is not in grading state for batch rollback.'
        );
        $beforeResults = (int)scalar($pdo, 'SELECT COUNT(*) FROM appeal_results WHERE appeal_request_id=:a', ['a' => $ids['appeal2']]);
        $beforeNotify = (int)scalar(
            $pdo,
            'SELECT COUNT(*) FROM notifications WHERE user_id=:u AND title="Kết quả phúc khảo"',
            ['u' => $ids['candidateUser2']]
        );
        assert_true($beforeResults === 0, 'appeal2 unexpectedly already has a result before batch fault injection.');

        must_fail(
            fn() => complete_appeal_batch(
                $pdo,
                [
                    ['appeal_id' => $ids['appeal2'], 'new_score' => 7.5, 'reason' => 'batch row ok'],
                    ['appeal_id' => 999999999, 'new_score' => 8.0, 'reason' => 'batch row bad'],
                ],
                $ids['graderUser']
            ),
            'Không tìm thấy'
        );

        assert_true(
            scalar($pdo, 'SELECT status FROM appeal_requests WHERE id=:a', ['a' => $ids['appeal2']]) === 'grading',
            'First batch row was not rolled back; appeal2 left completed.'
        );
        assert_true(
            (int)scalar($pdo, 'SELECT COUNT(*) FROM appeal_results WHERE appeal_request_id=:a', ['a' => $ids['appeal2']]) === 0,
            'Partial appeal_results survived batch rollback.'
        );
        $afterNotify = (int)scalar(
            $pdo,
            'SELECT COUNT(*) FROM notifications WHERE user_id=:u AND title="Kết quả phúc khảo"',
            ['u' => $ids['candidateUser2']]
        );
        assert_true($afterNotify === $beforeNotify, 'Partial notification survived batch rollback.');
    });
    test(37, 'no partial notification is created by blocked transaction', function () use ($pdo, $ids, &$candidate2NotificationBaseline) {
        assert_true($candidate2NotificationBaseline !== null, 'TEST 18 notification baseline was not captured.');
        $after = (int)scalar(
            $pdo,
            'SELECT COUNT(*) FROM notifications WHERE user_id=:u AND title="Kết quả phúc khảo"',
            ['u' => $ids['candidateUser2']]
        );
        assert_true($after === $candidate2NotificationBaseline, 'Notification count increased despite transaction rollback: before=' . $candidate2NotificationBaseline . ', after=' . $after);
    });
    test(38, 'RBAC permission is resolved from assigned role', function () use ($ids) {
        $_SESSION['auth'] = [
            'user_id' => $ids['managerUser'],
            'id' => $ids['managerUser'],
            'roles' => ['so_gdtd'],
        ];
        assert_true(auth_has_permission('appeal.manage'), 'Assigned route manager role lacks appeal.manage.');
    });
    test(39, 'assigned grader mismatch is rejected by validator', function () use ($pdo, $ids) {
        must_reject_validation(
            fn() => validate_appeal_result($pdo, $ids['appeal2'], 8.0, 'wrong actor', $ids['managerUser']),
            'chưa được phân công'
        );
    });
    test(40, 'period isolation is enforced during import validation', function () use ($pdo, $ids) {
        $row = admin_appeals($pdo, $ids['period'], $ids['subject'])[1] ?? null;
        assert_true(is_array($row), 'Appeal fixture row missing for isolation test.');
        $preview = uc03_validate_import(
            $pdo,
            $ids['period2'],
            $ids['subject2'],
            [
                UC03_HEADERS,
                [
                    (string)$ids['appeal2'],
                    (string)$row['anonymous_code'],
                    (string)$row['candidate_code'],
                    (string)$row['original_score'],
                    (string)$row['assigned_grader_username'],
                    '8.0',
                    'Wrong period',
                ],
            ],
            $ids['graderUser']
        );
        assert_true(count($preview['valid']) === 0 && count($preview['errors']) === 1, 'Cross-period import was accepted.');
        assert_true(str_contains($preview['errors'][0]['error'], 'không thuộc kỳ/môn'), 'Cross-period error was not reported.');
    });
    test(41, 'assignment requires appeal council membership', function () use ($pdo, $ids) {
        $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='appeal_grader_assignments' AND column_name='council_member_id'");
        assert_true((int)$q->fetchColumn() === 1, 'Assignment is not linked to council member.');
    });
    test(42, 'initial grader is not inferred from scores.entered_by', function () use ($pdo) {
        $src = file_get_contents(__DIR__ . '/../includes/appeal_service.php');
        assert_true(!str_contains($src, 'scores.entered_by'), 'Service incorrectly treats scores.entered_by as initial grader.');
    });
    test(43, 'protected trigger source is not modified by migration', function () {
        $sql = file_get_contents(__DIR__ . '/../database/013_batchI_uc03.sql');
        assert_true(!preg_match('/(DROP|ALTER)\s+TRIGGER/i', $sql), 'Migration modifies a trigger.');
    });
    test(44, 'Batch C score import infrastructure remains usable', function () {
        assert_true(function_exists('excel_parse_upload'), 'Excel parser required by Batch C is missing.');
        assert_true(str_contains(file_get_contents(__DIR__ . '/batchC_runtime.php'), 'create_score_import_batch'), 'Batch C runtime no longer exercises score import.');
    });
    test(45, 'Batch G2 runtime still covers its UC set', function () {
        $source = file_get_contents(__DIR__ . '/batchG2_runtime.php');
        assert_true(
            str_contains($source, 'UC24') && str_contains($source, 'UC25') && str_contains($source, 'UC28')
                && str_contains($source, 'UC34') && str_contains($source, 'UC35') && str_contains($source, 'UC36'),
            'Batch G2 runtime UC coverage is unexpectedly truncated.'
        );
        assert_true(str_contains($source, 'candidate_appeals'), 'Batch G2 runtime no longer covers appeal tracking behavior.');
    });
    test(46, 'Batch H runtime still covers authenticated route flows', function () {
        $source = file_get_contents(__DIR__ . '/batchH_runtime.php');
        assert_true(str_contains($source, 'proc_open'), 'Batch H child-process route harness is missing.');
        assert_true(str_contains($source, 'auth_login'), 'Batch H runtime no longer covers login behavior.');
    });
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo 'FIXTURE/SETUP FAILURE: ' . $e->getMessage() . PHP_EOL;
    $results[0] = ['status' => 'BLOCKED', 'name' => 'Batch I fixture setup', 'message' => $e->getMessage()];
} finally {
    // The test suite intentionally does not delete shared production data. Only tagged fixtures are removed.
    try {
        if ($ids['appeal2']) $pdo->prepare('DELETE FROM appeal_results WHERE appeal_request_id=:a')->execute(['a' => $ids['appeal2']]);
        if ($ids['appeal2']) $pdo->prepare('DELETE FROM appeal_grader_assignments WHERE appeal_request_id=:a')->execute(['a' => $ids['appeal2']]);
        if ($ids['appeal2']) $pdo->prepare('DELETE FROM appeal_requests WHERE id=:a')->execute(['a' => $ids['appeal2']]);
        if ($ids['appeal']) {
            $pdo->prepare('DELETE FROM appeal_results WHERE appeal_request_id=:a')->execute(['a' => $ids['appeal']]);
            $pdo->prepare('DELETE FROM appeal_grader_assignments WHERE appeal_request_id=:a')->execute(['a' => $ids['appeal']]);
            $pdo->prepare('DELETE FROM appeal_requests WHERE id=:a')->execute(['a' => $ids['appeal']]);
        }
        // Remove only this run's publication fixtures before touching scores; protected score triggers must remain active.
        foreach ([$ids['period'], $ids['period2']] as $periodId) if ($periodId) $pdo->prepare('DELETE FROM publication_events WHERE admission_period_id=:p')->execute(['p' => $periodId]);
        if ($ids['paper']) {
            $pdo->prepare('DELETE FROM exam_paper_grader_assignments WHERE exam_paper_id=:p')->execute(['p' => $ids['paper']]);
            $pdo->prepare('DELETE FROM scores WHERE exam_paper_id=:p')->execute(['p' => $ids['paper']]);
            $pdo->prepare('DELETE FROM exam_papers WHERE id=:p')->execute(['p' => $ids['paper']]);
        }
        if ($paper2) {
            $pdo->prepare('DELETE FROM exam_paper_grader_assignments WHERE exam_paper_id=:p')->execute(['p' => $paper2]);
            $pdo->prepare('DELETE FROM scores WHERE exam_paper_id=:p')->execute(['p' => $paper2]);
            $pdo->prepare('DELETE FROM exam_papers WHERE id=:p')->execute(['p' => $paper2]);
        }
        if ($ids['candidate']) $pdo->prepare('DELETE FROM candidates WHERE id=:id')->execute(['id' => $ids['candidate']]);
        if ($ids['candidate2']) $pdo->prepare('DELETE FROM candidates WHERE id=:id')->execute(['id' => $ids['candidate2']]);
        if ($ids['appealMember']) $pdo->prepare('DELETE FROM council_members WHERE id=:id')->execute(['id' => $ids['appealMember']]);
        if ($ids['initialAppealMember']) $pdo->prepare('DELETE FROM council_members WHERE id=:id')->execute(['id' => $ids['initialAppealMember']]);
        if ($ids['initialMember']) $pdo->prepare('DELETE FROM council_members WHERE id=:id')->execute(['id' => $ids['initialMember']]);
        if ($ids['appealCouncil']) $pdo->prepare('DELETE FROM council_subjects WHERE council_id=:id')->execute(['id' => $ids['appealCouncil']]);
        if ($ids['initialCouncil']) $pdo->prepare('DELETE FROM council_subjects WHERE council_id=:id')->execute(['id' => $ids['initialCouncil']]);
        if ($ids['appealCouncil']) $pdo->prepare('DELETE FROM councils WHERE id=:id')->execute(['id' => $ids['appealCouncil']]);
        if ($ids['initialCouncil']) $pdo->prepare('DELETE FROM councils WHERE id=:id')->execute(['id' => $ids['initialCouncil']]);
        if ($ids['staffGrader']) $pdo->prepare('DELETE FROM staff WHERE id=:id')->execute(['id' => $ids['staffGrader']]);
        if ($ids['staffInitial']) $pdo->prepare('DELETE FROM staff WHERE id=:id')->execute(['id' => $ids['staffInitial']]);
        foreach (['graderUser', 'initialUser', 'managerUser', 'candidateUser', 'candidateUser2'] as $k) {
            if ($ids[$k]) {
                $pdo->prepare('DELETE FROM user_roles WHERE user_id=:id')->execute(['id' => $ids[$k]]);
                $pdo->prepare('DELETE FROM notifications WHERE user_id=:id')->execute(['id' => $ids[$k]]);
                $pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $ids[$k]]);
            }
        }
        if ($ids['room']) $pdo->prepare('DELETE FROM exam_rooms WHERE id=:id')->execute(['id' => $ids['room']]);
        if ($ids['site']) $pdo->prepare('DELETE FROM exam_sites WHERE id=:id')->execute(['id' => $ids['site']]);
        foreach (['subject', 'subject2'] as $k) if ($ids[$k]) $pdo->prepare('DELETE FROM exam_subjects WHERE id=:id')->execute(['id' => $ids[$k]]);
        foreach (['period', 'period2'] as $k) if ($ids[$k]) $pdo->prepare('DELETE FROM admission_periods WHERE id=:id')->execute(['id' => $ids[$k]]);
        if ($ids['school']) $pdo->prepare('DELETE FROM schools WHERE id=:id')->execute(['id' => $ids['school']]);
    } catch (Throwable $e) {
        echo 'CLEANUP WARNING: ' . $e->getMessage() . PHP_EOL;
    }
}

$names = array_column($results, 'name');
$counts = ['PASS' => 0, 'FAIL' => 0, 'BLOCKED' => 0, 'MISSING SPECIFICATION' => 0, 'MISSING TEST HOOK' => 0];
foreach ($results as $r) {
    $s = $r['status'];
    if (isset($counts[$s])) $counts[$s]++;
    if (str_contains((string)($r['message'] ?? ''), 'MISSING SPECIFICATION')) $counts['MISSING SPECIFICATION']++;
    if (str_contains((string)($r['message'] ?? ''), 'MISSING TEST HOOK')) $counts['MISSING TEST HOOK']++;
}
$triggerNames = $pdo->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME IN ('trg_scores_lock_after_exam_publication','trg_scores_update_lock_after_exam_publication','trg_scores_delete_lock_after_exam_publication')")->fetchAll(PDO::FETCH_COLUMN);
sort($triggerNames);
echo PHP_EOL . 'Protected triggers present: ' . (count($triggerNames) === 3 ? 'YES' : 'NO') . PHP_EOL;
echo 'TOTAL: ' . count($results) . ' PASS: ' . $counts['PASS'] . ' FAIL: ' . $counts['FAIL'] . ' BLOCKED: ' . $counts['BLOCKED'] . PHP_EOL;
