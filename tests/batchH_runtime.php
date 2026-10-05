<?php

declare(strict_types=1);

/**
 * Batch H runtime verification for UC29/UC30.
 *
 * Run from the project root:
 *   C:\\xampp\\php\\php.exe tests\\batchH_runtime.php
 *
 * The login and change-password page tests execute the real production routes
 * in child PHP processes so CAPTCHA, CSRF, session creation and redirects are
 * not bypassed by the test itself.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = db();
$token = 'H' . date('YmdHis') . bin2hex(random_bytes(3));
$results = [];
$ids = ['role' => 0, 'inactive' => 0, 'norole' => 0, 'user' => 0, 'userNoRole' => 0];
$originalPassword = 'OldPass1!';
$newPassword = 'NewPass2@';

final class ChildDbConnectionException extends RuntimeException
{
}

function assert_true(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}

function test(int $number, string $name, callable $fn): void
{
    global $results;
    try {
        $fn();
        $results[$number] = ['status' => 'PASS', 'name' => $name];
    } catch (ChildDbConnectionException $e) {
        $results[$number] = ['status' => 'BLOCKED', 'name' => $name, 'message' => $e->getMessage()];
    } catch (Throwable $e) {
        $results[$number] = ['status' => 'FAIL', 'name' => $name, 'message' => $e->getMessage()];
    }
}

function ensure_test_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_start();
}

function reset_direct_auth_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_write_close();
    }

    $newId = bin2hex(random_bytes(16));
    session_id($newId);
    ensure_test_session();
    $_SESSION = [];
}

function diagnostic_block(array $result): string
{
    $d = $result['diagnostic'] ?? [];
    $lines = ['--- SESSION DIAGNOSTIC ---'];
    foreach (['before_config' => 'before config', 'after_config' => 'after config', 'before_route' => 'before route', 'after_route' => 'after route'] as $key => $label) {
        $x = is_array($d[$key] ?? null) ? $d[$key] : [];
        $lines[] = $label . ':';
        $lines[] = 'headers_sent = ' . var_export($x['headers_sent'] ?? null, true);
        $lines[] = 'header_file = ' . (string)($x['header_file'] ?? '');
        $lines[] = 'header_line = ' . (string)($x['header_line'] ?? '');
        $lines[] = 'session_status = ' . (string)($x['session_status_name'] ?? ($x['session_status'] ?? ''));
        $lines[] = 'session_id = ' . (string)($x['session_id'] ?? '');
        $lines[] = 'session_name = ' . (string)($x['session_name'] ?? '');
    }
    $w = is_array($d['session_regenerate_warning'] ?? null) ? $d['session_regenerate_warning'] : null;
    $lines[] = 'session_regenerate warning:';
    if ($w === null) {
        $lines[] = 'none';
    } else {
        $lines[] = 'message = ' . (string)($w['message'] ?? '');
        $lines[] = 'file = ' . (string)($w['file'] ?? '');
        $lines[] = 'line = ' . (string)($w['line'] ?? '');
    }

    $lines[] = 'child environment:';
    $childEnvironment = $d['child_environment'] ?? [];
    $lines[] = is_array($childEnvironment) ? json_encode($childEnvironment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'none';
    $lines[] = 'child db diagnostic:';
    $childDb = $d['child_db'] ?? null;
    $lines[] = is_array($childDb) ? json_encode($childDb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'none';
    $lines[] = 'route output:';
    $lines[] = (string)($result['output'] ?? '');
    $lines[] = 'test POST payload (password fields redacted):';
    $lines[] = json_encode($d['test_post'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $lines[] = 'route-local return/error state:';
    $routeReturn = $d['route_return'] ?? null;
    $lines[] = is_array($routeReturn) ? json_encode($routeReturn, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'none';
    $lines[] = 'route exit code = ' . (string)($result['exit_code'] ?? '');
    $lines[] = 'route headers:';
    $headers = $d['route_headers'] ?? [];
    if (is_array($headers) && $headers) {
        foreach ($headers as $header) {
            $lines[] = (string)$header;
        }
    } else {
        $lines[] = 'none';
    }
    $lines[] = 'route exception:';
    $exception = is_array($d['route_exception'] ?? null) ? $d['route_exception'] : null;
    if ($exception === null) {
        $lines[] = 'none';
    } else {
        $lines[] = 'class = ' . (string)($exception['class'] ?? '');
        $lines[] = 'message = ' . (string)($exception['message'] ?? '');
        $lines[] = 'file = ' . (string)($exception['file'] ?? '');
        $lines[] = 'line = ' . (string)($exception['line'] ?? '');
    }
    $lines[] = 'route fatal:';
    $fatal = is_array($d['route_fatal'] ?? null) ? $d['route_fatal'] : null;
    if ($fatal === null) {
        $lines[] = 'none';
    } else {
        $lines[] = 'type = ' . (string)($fatal['type'] ?? '');
        $lines[] = 'message = ' . (string)($fatal['message'] ?? '');
        $lines[] = 'file = ' . (string)($fatal['file'] ?? '');
        $lines[] = 'line = ' . (string)($fatal['line'] ?? '');
    }
    $lines[] = 'route warnings:';
    $warnings = $d['route_warnings'] ?? [];
    if (is_array($warnings) && $warnings) {
        foreach ($warnings as $warning) {
            $lines[] = 'severity = ' . (string)($warning['severity'] ?? '') . '; message = ' . (string)($warning['message'] ?? '') . '; file = ' . (string)($warning['file'] ?? '') . '; line = ' . (string)($warning['line'] ?? '');
        }
    } else {
        $lines[] = 'none';
    }
    $lines[] = 'child stdout:';
    $lines[] = (string)($result['stdout'] ?? '');
    $lines[] = 'child stderr:';
    $lines[] = (string)($result['stderr'] ?? '');
    return implode(PHP_EOL, $lines);
}

function redact_post_payload(array $post): array
{
    $safe = $post;
    foreach (['password', 'current_password', 'new_password', 'confirm_password'] as $key) {
        if (array_key_exists($key, $safe)) {
            $safe[$key] = '[REDACTED]';
        }
    }
    return $safe;
}

function auth_session_summary(array $session): array
{
    $auth = isset($session['auth']) && is_array($session['auth']) ? $session['auth'] : [];
    return [
        'auth_present' => isset($session['auth']),
        'user_id' => $auth['user_id'] ?? ($auth['id'] ?? null),
        'username' => $auth['username'] ?? null,
        'role' => $auth['role'] ?? null,
        'auth_keys' => array_keys($auth),
    ];
}

function route_test_context(array $result, array $post): string
{
    $d = $result['diagnostic'] ?? [];
    $lines = [
        'POST payload (password fields redacted) = ' . json_encode(redact_post_payload($post), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'mode = ' . (string)($post['mode'] ?? ''),
        'csrf_token = ' . (string)($post['csrf_token'] ?? ''),
        'captchaToken = ' . (string)($post['captchaToken'] ?? ''),
        'captchaText = ' . (string)($post['captchaText'] ?? ''),
        'session auth summary = ' . json_encode(auth_session_summary($result['session'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
    if (isset($d['route_return'])) {
        $lines[] = 'route return = ' . var_export($d['route_return'], true);
    }
    return implode(PHP_EOL, $lines);
}

function run_route(string $script, array $post, array $session, string $sessionId): array
{
    $root = dirname(__DIR__);
    $child = tempnam(sys_get_temp_dir(), 'batchH_child_') . '.php';
    $resultFile = tempnam(sys_get_temp_dir(), 'batchH_result_') . '.json';
    $sessionPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'batchH_session_' . bin2hex(random_bytes(8));
    if (!mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) {
        @unlink($child);
        @unlink($resultFile);
        throw new RuntimeException('Không thể tạo session store cho Batch H child process.');
    }

    // Keep filesystem paths out of generated PHP source. They are passed through
    // the child process environment so Windows backslashes can never corrupt PHP
    // string literals in the generated script.
    $env = [
        'BATCHH_ROOT' => $root,
        'BATCHH_CONFIG' => $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.php',
        'BATCHH_TARGET' => $root . DIRECTORY_SEPARATOR . ltrim($script, '/\\'),
        'BATCHH_RESULT' => $resultFile,
        'BATCHH_SESSION_ID' => $sessionId,
        'BATCHH_SESSION_PATH' => $sessionPath,
        'BATCHH_POST' => base64_encode(serialize($post)),
        'BATCHH_SESSION' => base64_encode(serialize($session)),
    ];

    $code = <<<'PHP'
<?php
declare(strict_types=1);

$root = getenv('BATCHH_ROOT');
$config = getenv('BATCHH_CONFIG');
$target = getenv('BATCHH_TARGET');
$resultFile = getenv('BATCHH_RESULT');
$sessionId = getenv('BATCHH_SESSION_ID');
$sessionPath = getenv('BATCHH_SESSION_PATH');

if ($root === false || $config === false || $target === false || $resultFile === false || $sessionId === false || $sessionPath === false) {
    fwrite(STDERR, 'Missing Batch H child environment.' . PHP_EOL);
    exit(2);
}

$post = unserialize(base64_decode((string)getenv('BATCHH_POST'), true));
$session = unserialize(base64_decode((string)getenv('BATCHH_SESSION'), true));
if (!is_array($post) || !is_array($session)) {
    fwrite(STDERR, 'Invalid Batch H child payload.' . PHP_EOL);
    exit(2);
}

ob_start();

$diagnostic = [
    'before_config' => [],
    'after_config' => [],
    'before_route' => [],
    'after_route' => [],
    'session_regenerate_warning' => null,
    'route_headers' => [],
    'route_exception' => null,
    'route_fatal' => null,
    'route_warnings' => [],
    'route_return' => null,
    'child_environment' => [],
    'child_db' => null,
];

$captureState = static function (): array {
    $headerFile = null;
    $headerLine = null;
    $sent = headers_sent($headerFile, $headerLine);
    return [
        'headers_sent' => $sent,
        'header_file' => $headerFile,
        'header_line' => $headerLine,
        'session_status' => session_status(),
        'session_status_name' => match (session_status()) {
            PHP_SESSION_DISABLED => 'PHP_SESSION_DISABLED',
            PHP_SESSION_NONE => 'PHP_SESSION_NONE',
            PHP_SESSION_ACTIVE => 'PHP_SESSION_ACTIVE',
            default => 'UNKNOWN',
        },
        'session_id' => session_id(),
        'session_name' => session_name(),
    ];
};

$diagnostic['child_environment'] = [
    'cwd' => getcwd(),
    'project_root' => $root,
    'root_realpath' => realpath($root) ?: null,
    'config_path' => $config,
    'config_realpath' => realpath($config) ?: null,
    'target_path' => $target,
    'target_realpath' => realpath($target) ?: null,
    'php_binary' => PHP_BINARY,
    'php_version' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'environment' => (static function (): array {
        $env = getenv();
        if (!is_array($env)) {
            return [];
        }
        $safe = [];
        foreach ($env as $key => $value) {
            $key = (string)$key;
            $upper = strtoupper($key);
            if (!str_contains($upper, 'DB') && !str_contains($upper, 'MYSQL') && !str_contains($upper, 'MARIADB') && !str_contains($upper, 'PDO')) {
                continue;
            }
            if (str_contains($upper, 'PASS') || str_contains($upper, 'SECRET') || str_contains($upper, 'TOKEN') || str_contains($upper, 'KEY')) {
                $safe[$key] = '[REDACTED]';
            } else {
                $safe[$key] = (string)$value;
            }
        }
        ksort($safe);
        return $safe;
    })(),
];
$diagnostic['before_config'] = $captureState();

register_shutdown_function(static function () use (&$diagnostic, $captureState, $resultFile): void {
    restore_error_handler();

    $lastError = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (is_array($lastError) && in_array((int)($lastError['type'] ?? 0), $fatalTypes, true)) {
        $diagnostic['route_fatal'] = [
            'type' => (int)($lastError['type'] ?? 0),
            'message' => (string)($lastError['message'] ?? ''),
            'file' => (string)($lastError['file'] ?? ''),
            'line' => (int)($lastError['line'] ?? 0),
        ];
    }

    $diagnostic['route_headers'] = headers_list();
    $diagnostic['after_route'] = $captureState();
    file_put_contents($resultFile, json_encode([
        'session_id' => session_id(),
        'session' => $_SESSION,
        'output' => ob_get_contents() ?: '',
        'diagnostic' => $diagnostic,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
});

// The production config owns session_start(). The fixture is injected only after
// that real production session is active; no second session is created here.
session_save_path($sessionPath);
session_id($sessionId);
require $config;
$diagnostic['after_config'] = $captureState();

// Verify the same production db() function before executing the route. This is
// test-only instrumentation: it loads the project's db.php and calls db(),
// without creating an alternate PDO connection or replacing production code.
try {
    require_once $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php';
    $childPdo = db();
    $diagnostic['child_db'] = [
        'connected' => true,
        'driver' => $childPdo->getAttribute(PDO::ATTR_DRIVER_NAME),
        'server_version' => $childPdo->getAttribute(PDO::ATTR_SERVER_VERSION),
    ];
    unset($childPdo);
} catch (Throwable $e) {
    $diagnostic['child_db'] = [
        'connected' => false,
        'class' => get_class($e),
        'message' => $e->getMessage(),
    ];
    // Do not execute a production route when its own db() cannot connect.
    // The parent will classify this as a child-harness/environment failure.
    $diagnostic['route_return'] = ['child_db_connection_failed' => true];
    $diagnostic['route_exception'] = [
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
    file_put_contents($resultFile, json_encode([
        'session_id' => session_id(),
        'session' => $_SESSION,
        'output' => ob_get_contents() ?: '',
        'diagnostic' => $diagnostic,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    exit(3);
}

$_SESSION = $session;
$diagnostic['before_route'] = $captureState();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $post;
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');
set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$diagnostic): bool {
    $warning = [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
    ];
    $diagnostic['route_warnings'][] = $warning;
    if (str_contains($message, 'session_regenerate_id()')) {
        $diagnostic['session_regenerate_warning'] = $warning;
    }
    // Return false so PHP's normal warning/notice behavior remains intact; this
    // handler only records the event for the parent diagnostic.
    return false;
});

try {
    require $target;
    $defined = get_defined_vars();
    foreach (['errors', 'loginError', 'success', 'mode', 'username', 'captchaText', 'captchaToken'] as $name) {
        if (array_key_exists($name, $defined)) {
            $diagnostic['route_return'][$name] = $name === 'errors' && is_array($defined[$name])
                ? $defined[$name]
                : $defined[$name];
        }
    }
} catch (Throwable $e) {
    $diagnostic['route_exception'] = [
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
    throw $e;
}
PHP;

    $cleanupSessionPath = static function () use ($sessionPath): void {
        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . DIRECTORY_SEPARATOR . 'sess_*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            @rmdir($sessionPath);
        }
    };

    if (file_put_contents($child, $code) === false) {
        @unlink($child);
        @unlink($resultFile);
        $cleanupSessionPath();
        throw new RuntimeException('Không thể tạo PHP child script.');
    }

    // Verify the generated child script itself before executing the production route.
    $lintDescriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $lint = proc_open([PHP_BINARY, '-l', $child], $lintDescriptors, $lintPipes, $root);
    if (!is_resource($lint)) {
        @unlink($child);
        @unlink($resultFile);
        $cleanupSessionPath();
        throw new RuntimeException('Không thể kiểm tra syntax PHP child script.');
    }
    $lintStdout = stream_get_contents($lintPipes[1]);
    $lintStderr = stream_get_contents($lintPipes[2]);
    fclose($lintPipes[1]);
    fclose($lintPipes[2]);
    $lintExitCode = proc_close($lint);
    if ($lintExitCode !== 0) {
        @unlink($child);
        @unlink($resultFile);
        $cleanupSessionPath();
        throw new RuntimeException('Generated child script parse error: ' . trim($lintStderr . ' ' . $lintStdout));
    }

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    // proc_open() receives the full inherited OS environment. Using $_ENV here
    // is unsafe because variables_order may omit E, causing the child to lose
    // DB-related environment variables even though the parent can use them.
    $processEnv = getenv();
    if (!is_array($processEnv)) {
        $processEnv = [];
    }

    foreach ($env as $key => $value) {
    $processEnv[$key] = $value;
}

$process = proc_open(
    [PHP_BINARY, $child],
    $descriptors,
    $pipes,
    $root,
    $processEnv
);
    if (!is_resource($process)) {
        @unlink($child);
        @unlink($resultFile);
        $cleanupSessionPath();
        throw new RuntimeException('Không thể khởi chạy PHP child process.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $data = is_file($resultFile) ? json_decode((string)file_get_contents($resultFile), true) : null;
    @unlink($child);
    @unlink($resultFile);
    $cleanupSessionPath();
    if (!is_array($data)) {
        throw new RuntimeException('Child route did not return test data. STDERR: ' . trim($stderr) . ' STDOUT: ' . trim($stdout));
    }
    if (($data['diagnostic']['child_db']['connected'] ?? false) !== true) {
        $childDb = $data['diagnostic']['child_db'] ?? [];
        throw new ChildDbConnectionException(
            'CHILD DB CONNECTION FAILED' . PHP_EOL .
            'class = ' . (string)($childDb['class'] ?? '') . PHP_EOL .
            'message = ' . (string)($childDb['message'] ?? '') . PHP_EOL .
            'environment = ' . json_encode($data['diagnostic']['child_environment'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
    $data['exit_code'] = $exitCode;
    $data['stderr'] = $stderr;
    $data['stdout'] = $stdout;
    $data['diagnostic']['test_post'] = redact_post_payload($post);
    return $data;
}

try {
    // ---------- Fixtures ----------
    $pdo->beginTransaction();
    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code=:code LIMIT 1');
    $roleStmt->execute(['code' => 'candidate']);
    $ids['role'] = (int)$roleStmt->fetchColumn();
    if ($ids['role'] <= 0) {
        $roleStmt = $pdo->prepare('INSERT INTO roles(code,name,active) VALUES("candidate", "Batch H candidate", 1)');
        $roleStmt->execute();
        $ids['role'] = (int)$pdo->lastInsertId();
    }

    $insertUser = $pdo->prepare('INSERT INTO users(username,password_hash,full_name,role,active) VALUES(:u,:h,:n,"candidate",:a)');
    $insertUser->execute(['u' => 'htest_active_' . $token, 'h' => password_hash($originalPassword, PASSWORD_DEFAULT), 'n' => 'Batch H Active', 'a' => 1]);
    $ids['user'] = (int)$pdo->lastInsertId();
    $insertUser->execute(['u' => 'htest_inactive_' . $token, 'h' => password_hash($originalPassword, PASSWORD_DEFAULT), 'n' => 'Batch H Inactive', 'a' => 0]);
    $ids['inactive'] = (int)$pdo->lastInsertId();
    $insertUser->execute(['u' => 'htest_norole_' . $token, 'h' => password_hash($originalPassword, PASSWORD_DEFAULT), 'n' => 'Batch H No Role', 'a' => 1]);
    $ids['norole'] = (int)$pdo->lastInsertId();

    $ur = $pdo->prepare('INSERT INTO user_roles(user_id,role_id) VALUES(:u,:r)');
    $ur->execute(['u' => $ids['user'], 'r' => $ids['role']]);
    $ur->execute(['u' => $ids['inactive'], 'r' => $ids['role']]);
    $pdo->commit();

    // ---------- UC29 ----------
    test(1, 'UC29 valid login succeeds', function () use ($originalPassword) {
        reset_direct_auth_session();
        assert_true(auth_login('htest_active_' . $GLOBALS['token'], $originalPassword), 'Valid login failed.');
    });

    test(2, 'UC29 invalid password rejected', function () use ($originalPassword) {
        reset_direct_auth_session();
        assert_true(!auth_login('htest_active_' . $GLOBALS['token'], 'WrongPass1!'), 'Invalid password was accepted.');
    });

    test(3, 'UC29 inactive user rejected', function () use ($originalPassword) {
        reset_direct_auth_session();
        assert_true(!auth_login('htest_inactive_' . $GLOBALS['token'], $originalPassword), 'Inactive user was accepted.');
    });

    test(4, 'UC29 user without active RBAC role rejected', function () use ($originalPassword) {
        reset_direct_auth_session();
        assert_true(!auth_login('htest_norole_' . $GLOBALS['token'], $originalPassword), 'User without active RBAC role was accepted.');
    });

    test(5, 'UC29 CAPTCHA failure rejected by production login route', function () use ($originalPassword) {
        $sid = bin2hex(random_bytes(16));
        $result = run_route('auth/login.php', [
            'mode' => 'student', 'username' => 'htest_active_' . $GLOBALS['token'], 'password' => $originalPassword,
            'captchaText' => 'WRONG', 'captchaToken' => 'missing-token', 'csrf_token' => 'csrf-ok',
        ], ['csrf_token' => 'csrf-ok'], $sid);
        assert_true(str_contains((string)$result['output'], 'Mã xác nhận không chính xác hoặc đã hết hạn'), 'CAPTCHA failure was not rejected by login route.');
        assert_true(!isset($result['session']['auth']), 'CAPTCHA failure created an authenticated session.');
    });

    test(6, 'UC29 CSRF failure rejected by production login route', function () use ($originalPassword) {
        $sid = bin2hex(random_bytes(16));
        $result = run_route('auth/login.php', [
            'mode' => 'student', 'username' => 'htest_active_' . $GLOBALS['token'], 'password' => $originalPassword,
            'captchaText' => 'ABCDE', 'captchaToken' => 'valid-but-unreachable', 'csrf_token' => 'wrong',
        ], ['csrf_token' => 'expected'], $sid);
        assert_true(str_contains((string)$result['output'], 'Yêu cầu không hợp lệ'), 'CSRF failure was not rejected by login route.');
        assert_true(!isset($result['session']['auth']), 'CSRF failure created an authenticated session.');
    });

    $successfulLogin = null;
    test(7, 'UC29 successful login creates expected authenticated session', function () use ($originalPassword, &$successfulLogin) {
        $sid = bin2hex(random_bytes(16));
        $username = 'htest_active_' . $GLOBALS['token'];
        $userStmt = $GLOBALS['pdo']->prepare('SELECT u.id, u.username, u.active, r.id AS role_id, r.code AS role_code, r.name AS role_name, r.active AS role_active FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id WHERE u.username=:username ORDER BY r.id LIMIT 1');
        $userStmt->execute(['username' => $username]);
        $fixture = $userStmt->fetch();
        $directSessionBefore = $_SESSION;
        reset_direct_auth_session();
        $directReturn = auth_login($username, $originalPassword);
        $directSessionAfter = $_SESSION;
        $directSummary = auth_session_summary($directSessionAfter);
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = $directSessionBefore;
        }

        $post = [
            'mode' => 'student', 'username' => $username, 'password' => $originalPassword,
            'captchaText' => 'ABCDE', 'captchaToken' => 'captcha-1', 'csrf_token' => 'csrf-1',
        ];
        $result = run_route('auth/login.php', $post,
            ['csrf_token' => 'csrf-1', 'captchas' => ['captcha-1' => ['code' => 'ABCDE', 'expires' => time() + 120]]], $sid);
        $result['diagnostic']['fixture'] = [
            'username' => $username,
            'user_id' => (int)($fixture['id'] ?? 0),
            'active' => (int)($fixture['active'] ?? -1),
            'role' => [
                'id' => (int)($fixture['role_id'] ?? 0),
                'code' => $fixture['role_code'] ?? null,
                'name' => $fixture['role_name'] ?? null,
                'active' => isset($fixture['role_active']) ? (int)$fixture['role_active'] : null,
            ],
            'direct_auth_login_return' => $directReturn,
            'direct_auth_session_after' => $directSummary,
        ];
        $successfulLogin = ['initial' => $sid, 'result' => $result];
        if (!isset($result['session']['auth']['user_id'])) {
            throw new RuntimeException('Authenticated session was not created.' . PHP_EOL . route_test_context($result, $post) . PHP_EOL . 'fixture = ' . json_encode($result['diagnostic']['fixture'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL . diagnostic_block($result));
        }
        assert_true($result['session']['auth']['username'] === $username, 'Authenticated username is incorrect.' . PHP_EOL . route_test_context($result, $post) . PHP_EOL . diagnostic_block($result));
    });

    test(8, 'UC29 session ID regenerates on successful login', function () use (&$successfulLogin) {
        assert_true(is_array($successfulLogin), 'Successful-login fixture missing.');
        assert_true($successfulLogin['result']['session_id'] !== $successfulLogin['initial'], 'Session ID was not regenerated.');
    });

    test(9, 'UC29 plaintext password is not stored in session', function () use (&$successfulLogin, $originalPassword) {
        assert_true(is_array($successfulLogin), 'Successful-login fixture missing.');
        $serialized = json_encode($successfulLogin['result']['session'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        assert_true(!str_contains((string)$serialized, $originalPassword), 'Plaintext password was found in authenticated session.');
        assert_true(!isset($successfulLogin['result']['session']['auth']['password']), 'Password field exists in authenticated session.');
    });

    // ---------- UC30 ----------

    $policyCases = [
        11 => ['short', 'Aa1!xxx'],
        12 => ['missing uppercase', 'aa1!aaaa'],
        13 => ['missing lowercase', 'AA1!AAAA'],
        14 => ['missing number', 'Aa!aaaaa'],
        15 => ['missing special', 'Aa1aaaaa'],
    ];
    foreach ($policyCases as $number => [$label, $badPassword]) {
        test($number, 'UC30 password policy: ' . $label, function () use ($badPassword, $originalPassword) {
            $sid = bin2hex(random_bytes(16));
            $result = run_route('auth/change-password.php', [
                'current_password' => $originalPassword, 'new_password' => $badPassword, 'confirm_password' => $badPassword, 'csrf_token' => 'csrf-change',
            ], ['csrf_token' => 'csrf-change', 'auth' => ['user_id' => $GLOBALS['ids']['user'], 'id' => $GLOBALS['ids']['user'], 'username' => 'htest_active_' . $GLOBALS['token'], 'roles' => ['candidate']]], $sid);
            assert_true(str_contains((string)$result['output'], 'Mật khẩu mới phải có ít nhất 8 ký tự'), 'Invalid password policy case was accepted.');
        });
    }

    test(16, 'UC30 confirmation mismatch rejected', function () use ($originalPassword) {
        $sid = bin2hex(random_bytes(16));
        $result = run_route('auth/change-password.php', [
            'current_password' => $originalPassword, 'new_password' => 'NewPass3#', 'confirm_password' => 'NewPass4#', 'csrf_token' => 'csrf-change',
        ], ['csrf_token' => 'csrf-change', 'auth' => ['user_id' => $GLOBALS['ids']['user'], 'id' => $GLOBALS['ids']['user'], 'username' => 'htest_active_' . $GLOBALS['token'], 'roles' => ['candidate']]], $sid);
        assert_true(str_contains((string)$result['output'], 'Mật khẩu xác nhận không trùng khớp'), 'Confirmation mismatch was accepted.');
    });

    test(17, 'UC30 wrong current password rejected', function () use ($newPassword) {
        $sid = bin2hex(random_bytes(16));
        $userId = (int)$GLOBALS['ids']['user'];
        $row = $GLOBALS['pdo']->prepare('SELECT id, username, active, password_hash FROM users WHERE id = :id');
        $row->execute(['id' => $userId]);
        $dbUser = $row->fetch();
        $fixture = ['user_id' => $userId, 'username' => 'htest_active_' . $GLOBALS['token']];
        $oldVerify = is_array($dbUser) ? password_verify('OldPass1!', (string)$dbUser['password_hash']) : false;
        $wrongVerify = is_array($dbUser) ? password_verify('WrongOld1!', (string)$dbUser['password_hash']) : false;
        $result = run_route('auth/change-password.php', [
            'current_password' => 'WrongOld1!', 'new_password' => $newPassword, 'confirm_password' => $newPassword, 'csrf_token' => 'csrf-change',
        ], ['csrf_token' => 'csrf-change', 'auth' => ['user_id' => $userId, 'id' => $userId, 'username' => $fixture['username'], 'roles' => ['candidate']]], $sid);
        if (!str_contains((string)$result['output'], 'Mật khẩu hiện tại không chính xác')) {
            throw new RuntimeException(
                'Wrong current password was accepted.' . PHP_EOL .
                'session user_id = ' . (string)($result['session']['auth']['user_id'] ?? '') . PHP_EOL .
                'session username = ' . (string)($result['session']['auth']['username'] ?? '') . PHP_EOL .
                'session auth present = ' . (isset($result['session']['auth']) ? 'true' : 'false') . PHP_EOL .
                'database user id = ' . (string)($dbUser['id'] ?? '') . PHP_EOL .
                'database username = ' . (string)($dbUser['username'] ?? '') . PHP_EOL .
                'database active = ' . (string)($dbUser['active'] ?? '') . PHP_EOL .
                'Old password verify = ' . ($oldVerify ? 'true' : 'false') . PHP_EOL .
                'Wrong password verify = ' . ($wrongVerify ? 'true' : 'false') . PHP_EOL .
                route_test_context($result, [
                    'current_password' => 'WrongOld1!', 'new_password' => $newPassword, 'confirm_password' => $newPassword, 'csrf_token' => 'csrf-change',
                ]) . PHP_EOL .
                diagnostic_block($result)
            );
        }
    });

    test(18, 'UC30 CSRF failure rejected', function () use ($originalPassword, $newPassword) {
        $sid = bin2hex(random_bytes(16));
        $result = run_route('auth/change-password.php', [
            'current_password' => $originalPassword, 'new_password' => $newPassword, 'confirm_password' => $newPassword, 'csrf_token' => 'wrong',
        ], ['csrf_token' => 'expected', 'auth' => ['user_id' => $GLOBALS['ids']['user'], 'id' => $GLOBALS['ids']['user'], 'username' => 'htest_active_' . $GLOBALS['token'], 'roles' => ['candidate']]], $sid);
        assert_true(str_contains((string)$result['output'], 'Yêu cầu không hợp lệ'), 'CSRF failure was not rejected.');
    });

    test(19, 'UC30 inactive account rejected', function () use ($originalPassword, $newPassword) {
        $sid = bin2hex(random_bytes(16));
        $userId = (int)$GLOBALS['ids']['inactive'];
        $row = $GLOBALS['pdo']->prepare('SELECT id, username, active FROM users WHERE id = :id');
        $row->execute(['id' => $userId]);
        $dbUser = $row->fetch();
        $result = run_route('auth/change-password.php', [
            'current_password' => $originalPassword, 'new_password' => $newPassword, 'confirm_password' => $newPassword, 'csrf_token' => 'csrf-change',
        ], ['csrf_token' => 'csrf-change', 'auth' => ['user_id' => $userId, 'id' => $userId, 'username' => 'htest_inactive_' . $GLOBALS['token'], 'roles' => ['candidate']]], $sid);
        if (!str_contains((string)$result['output'], 'Tài khoản không còn hoạt động')) {
            throw new RuntimeException(
                'Inactive account was accepted for password change.' . PHP_EOL .
                'session user_id = ' . (string)($result['session']['auth']['user_id'] ?? '') . PHP_EOL .
                'session username = ' . (string)($result['session']['auth']['username'] ?? '') . PHP_EOL .
                'session auth present = ' . (isset($result['session']['auth']) ? 'true' : 'false') . PHP_EOL .
                'database user id = ' . (string)($dbUser['id'] ?? '') . PHP_EOL .
                'database username = ' . (string)($dbUser['username'] ?? '') . PHP_EOL .
                'database active = ' . (string)($dbUser['active'] ?? '') . PHP_EOL .
                route_test_context($result, [
                    'current_password' => $originalPassword, 'new_password' => $newPassword, 'confirm_password' => $newPassword, 'csrf_token' => 'csrf-change',
                ]) . PHP_EOL .
                diagnostic_block($result)
            );
        }
    });

    test(10, 'UC30 valid password change succeeds', function () use ($originalPassword, $newPassword) {
        $sid = bin2hex(random_bytes(16));
        $userId = (int)$GLOBALS['ids']['user'];
        $beforeStmt = $GLOBALS['pdo']->prepare('SELECT id, username, active, password_hash FROM users WHERE id = :id');
        $beforeStmt->execute(['id' => $userId]);
        $before = $beforeStmt->fetch();
        $post = [
            'current_password' => $originalPassword, 'new_password' => $newPassword, 'confirm_password' => $newPassword, 'csrf_token' => 'csrf-change',
        ];
        $result = run_route('auth/change-password.php', $post, ['csrf_token' => 'csrf-change', 'auth' => ['user_id' => $userId, 'id' => $userId, 'username' => 'htest_active_' . $GLOBALS['token'], 'roles' => ['candidate']]], $sid);
        $afterStmt = $GLOBALS['pdo']->prepare('SELECT id, username, active, password_hash FROM users WHERE id = :id');
        $afterStmt->execute(['id' => $userId]);
        $after = $afterStmt->fetch();
        $beforeHash = is_array($before) ? (string)$before['password_hash'] : '';
        $afterHash = is_array($after) ? (string)$after['password_hash'] : '';
        $result['diagnostic']['password_change'] = [
            'before' => [
                'active' => is_array($before) ? (int)$before['active'] : null,
                'hash_exists' => $beforeHash !== '',
                'hash_length' => strlen($beforeHash),
                'hash_changed_after' => $beforeHash !== '' && $afterHash !== '' ? $beforeHash !== $afterHash : null,
                'old_verify' => $beforeHash !== '' ? password_verify($originalPassword, $beforeHash) : false,
                'new_verify' => $beforeHash !== '' ? password_verify($newPassword, $beforeHash) : false,
            ],
            'after' => [
                'active' => is_array($after) ? (int)$after['active'] : null,
                'hash_exists' => $afterHash !== '',
                'hash_length' => strlen($afterHash),
                'old_verify' => $afterHash !== '' ? password_verify($originalPassword, $afterHash) : false,
                'new_verify' => $afterHash !== '' ? password_verify($newPassword, $afterHash) : false,
            ],
            'session_auth' => auth_session_summary($result['session'] ?? []),
        ];
        if (!str_contains((string)$result['output'], 'Đổi mật khẩu thành công')) {
            throw new RuntimeException('Valid password change did not succeed.' . PHP_EOL . route_test_context($result, $post) . PHP_EOL . json_encode($result['diagnostic']['password_change'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL . diagnostic_block($result));
        }
        assert_true(($result['diagnostic']['password_change']['after']['new_verify'] ?? false) === true, 'Success message was returned but new password does not verify after update.' . PHP_EOL . diagnostic_block($result));
    });


    test(20, 'UC30 successful change stores hash, not plaintext', function () use ($newPassword, $originalPassword) {
        $row = $GLOBALS['pdo']->query("SELECT password_hash FROM users WHERE id=" . (int)$GLOBALS['ids']['user'])->fetch();
        assert_true(is_array($row) && $row['password_hash'] !== $newPassword, 'Password was stored as plaintext.');
        $newVerify = is_array($row) ? password_verify($newPassword, (string)$row['password_hash']) : false;
        $oldVerify = is_array($row) ? password_verify($originalPassword, (string)$row['password_hash']) : false;
        assert_true($newVerify, 'Stored password hash does not verify. new_password_verify=' . ($newVerify ? 'true' : 'false') . ' old_password_verify=' . ($oldVerify ? 'true' : 'false'));
    });

    test(21, 'UC30 old password no longer authenticates', function () use ($originalPassword) {
        ensure_test_session();
        assert_true(!auth_login('htest_active_' . $GLOBALS['token'], $originalPassword), 'Old password still authenticates.');
    });

    test(22, 'UC30 new password authenticates', function () use ($newPassword) {
        ensure_test_session();
        assert_true(auth_login('htest_active_' . $GLOBALS['token'], $newPassword), 'New password does not authenticate.');
        if (session_status() === PHP_SESSION_ACTIVE) {
    auth_logout();
}
    });
} finally {
    try {
        if ($ids['user'] > 0 || $ids['inactive'] > 0 || $ids['norole'] > 0) {
            $pdo->prepare('DELETE FROM user_roles WHERE user_id IN (:u,:i,:n)')->execute(['u' => $ids['user'], 'i' => $ids['inactive'], 'n' => $ids['norole']]);
            $pdo->prepare('DELETE FROM users WHERE id IN (:u,:i,:n)')->execute(['u' => $ids['user'], 'i' => $ids['inactive'], 'n' => $ids['norole']]);
        }
    } catch (Throwable $e) {
        echo 'CLEANUP ERROR: ' . $e->getMessage() . PHP_EOL;
    }
}

$pass = $fail = $blocked = 0;
foreach ($results as $number => $r) {
    if ($r['status'] === 'PASS') {
        $pass++;
        echo "PASS TEST {$number}: {$r['name']}" . PHP_EOL;
    } elseif ($r['status'] === 'BLOCKED') {
        $blocked++;
        echo "BLOCKED TEST {$number}: {$r['name']} — {$r['message']}" . PHP_EOL;
    } else {
        $fail++;
        echo "FAIL TEST {$number}: {$r['name']} — {$r['message']}" . PHP_EOL;
    }
}

echo PHP_EOL;
echo 'TOTAL TESTS: ' . count($results) . PHP_EOL;
echo "PASS: {$pass}" . PHP_EOL;
echo "FAIL: {$fail}" . PHP_EOL;
echo "BLOCKED: {$blocked}" . PHP_EOL;
exit(($fail > 0 || $blocked > 0) ? 1 : 0);
