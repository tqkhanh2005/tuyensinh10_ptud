<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

if ($argc < 4) {
    fwrite(STDERR, "Usage: php auth/create_user.php <username> <password> <role>\n");
    exit(1);
}

[, $username, $password, $role] = $argv;
$username = trim($username);
$role = trim($role);

if ($username === '' || $password === '' || $role === '') {
    fwrite(STDERR, "Username/password/role không được rỗng.\n");
    exit(1);
}

$pdo = db();

try {
    $roleStmt = $pdo->prepare('SELECT id, code FROM roles WHERE code = :code AND active = 1 LIMIT 1');
    $roleStmt->execute(['code' => $role]);
    $roleRow = $roleStmt->fetch();
    if (!$roleRow) {
        fwrite(STDERR, "Role không tồn tại hoặc không hoạt động: {$role}\n");
        exit(1);
    }

    $pdo->beginTransaction();

    $userStmt = $pdo->prepare(
        'INSERT INTO users(username, password_hash, role, active)
         VALUES(:username, :password_hash, :role, 1)'
    );
    $userStmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);

    $userId = (int)$pdo->lastInsertId();
    $mapStmt = $pdo->prepare(
        'INSERT INTO user_roles(user_id, role_id)
         VALUES(:user_id, :role_id)'
    );
    $mapStmt->execute([
        'user_id' => $userId,
        'role_id' => (int)$roleRow['id'],
    ]);

    $pdo->commit();
    echo "Tạo tài khoản thành công: {$username}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Không thể tạo tài khoản; thao tác đã được rollback.\n");
    exit(1);
}
