<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function auth_login(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    $pdo = db();
    $s = $pdo->prepare('SELECT id, username, password_hash, full_name, active FROM users WHERE username = :username LIMIT 1');
    $s->execute(['username' => $username]);
    $u = $s->fetch();

    if (!$u || !(bool)$u['active'] || !password_verify($password, $u['password_hash'])) {
        return false;
    }

    // Authorization is resolved only from the normalized RBAC relationship.
    $roleStmt = $pdo->prepare(
        'SELECT r.id, r.code
         FROM user_roles ur
         INNER JOIN roles r ON r.id = ur.role_id
         WHERE ur.user_id = :user_id AND r.active = 1
         ORDER BY r.id ASC'
    );
    $roleStmt->execute(['user_id' => (int)$u['id']]);
    $roles = $roleStmt->fetchAll();

    // A user without an active RBAC role must not enter an authenticated session.
    if (!$roles) {
        return false;
    }

    $roleCodes = array_values(array_map(static fn(array $role): string => (string)$role['code'], $roles));

    $hash = password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)
        ? password_hash($password, PASSWORD_DEFAULT)
        : null;

    if ($hash !== null) {
        $s = $pdo->prepare('UPDATE users SET password_hash = :hash, last_login_at = NOW() WHERE id = :id');
        $s->execute(['hash' => $hash, 'id' => (int)$u['id']]);
    } else {
        $s = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $s->execute(['id' => (int)$u['id']]);
    }

    if (!headers_sent()) {
        @session_regenerate_id(true);
    }
    $_SESSION['auth'] = [
        'user_id' => (int)$u['id'],
        // Compatibility for existing modules that still read auth_user()['id'].
        'id' => (int)$u['id'],
        'username' => (string)$u['username'],
        'full_name' => $u['full_name'],
        // Legacy compatibility only; authorization uses the roles array / RBAC tables.
        'role' => $roleCodes[0],
        'roles' => $roleCodes,
    ];

    return true;
}

function auth_user(): ?array
{
    return isset($_SESSION['auth']) && is_array($_SESSION['auth']) ? $_SESSION['auth'] : null;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function require_auth(): void
{
    if (!auth_check()) {
        header('Location: ' . url('/auth/login'));
        exit;
    }
    $q=db()->prepare('SELECT 1 FROM users u WHERE u.id=? AND u.active=1 AND EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id AND r.active=1 WHERE ur.user_id=u.id)');
    $q->execute([(int)(auth_user()['user_id']??auth_user()['id']??0)]);
    if(!$q->fetchColumn()){http_response_code(403);exit('403 Forbidden - Tài khoản không còn hoạt động hoặc vai trò đã bị thu hồi.');}
}

function auth_has_role(string $role): bool
{
    $u = auth_user();
    if ($u === null) {
        return false;
    }

    $roles = $u['roles'] ?? [];
    if(!is_array($roles) || !in_array($role,$roles,true))return false;
    $q=db()->prepare('SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id AND r.active=1 JOIN users u ON u.id=ur.user_id AND u.active=1 WHERE ur.user_id=? AND r.code=? LIMIT 1');
    $q->execute([(int)($u['user_id']??$u['id']??0),$role]);
    return (bool)$q->fetchColumn();
}

function require_role(string $role): void
{
    require_auth();
    if (!auth_has_role($role)) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

function auth_has_permission(string $permission): bool
{
    $u = auth_user();
    if (!$u) {
        return false;
    }

    $s = db()->prepare(
        'SELECT 1
         FROM user_roles ur
         INNER JOIN users account_user ON account_user.id = ur.user_id AND account_user.active = 1
         INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
         INNER JOIN permissions p ON p.id = rp.permission_id
         INNER JOIN roles r ON r.id = ur.role_id
         WHERE ur.user_id = :user_id
           AND r.active = 1
           AND p.code = :permission
         LIMIT 1'
    );
    $s->execute(['user_id' => (int)$u['user_id'], 'permission' => $permission]);

    return (bool)$s->fetchColumn();
}

function require_permission(string $permission): void
{
    require_auth();
    if (!auth_has_permission($permission)) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && hash_equals((string)$_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if (!csrf_verify($_POST['csrf_token'] ?? $_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
    }
}

function auth_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool)($params['secure'] ?? false),
            'httponly' => (bool)($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
