<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();

$pageTitle = 'Đổi mật khẩu - TUYỂN SINH LỚP 10';
$activePage = 'change-password';
$bodyClass = 'tw-bg-background tw-font-body tw-text-on-surface';
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } else {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $user = auth_user();

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'Vui lòng nhập đầy đủ mật khẩu hiện tại, mật khẩu mới và xác nhận mật khẩu mới.';
        }

        if (!$errors && !hash_equals($newPassword, $confirmPassword)) {
            $errors[] = 'Mật khẩu xác nhận không trùng khớp.';
        }

        if (!$errors) {
            $hasUppercase = preg_match('/[A-Z]/', $newPassword) === 1;
            $hasLowercase = preg_match('/[a-z]/', $newPassword) === 1;
            $hasNumber = preg_match('/[0-9]/', $newPassword) === 1;
            $hasSpecial = preg_match('/[^A-Za-z0-9]/', $newPassword) === 1;

            if (strlen($newPassword) < 8 || !$hasUppercase || !$hasLowercase || !$hasNumber || !$hasSpecial) {
                $errors[] = 'Mật khẩu mới phải có ít nhất 8 ký tự, ít nhất 1 chữ hoa ASCII, 1 chữ thường ASCII, 1 chữ số và 1 ký tự đặc biệt.';
            }
        }

        if (!$errors) {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT password_hash, active FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int)$user['user_id']]);
            $row = $stmt->fetch();

            if (!$row || !(bool)$row['active']) {
                $errors[] = 'Tài khoản không còn hoạt động.';
            } elseif (!password_verify($currentPassword, (string)$row['password_hash'])) {
                $errors[] = 'Mật khẩu hiện tại không chính xác.';
            }
        }

        if (!$errors) {
            // The specification does not define a separate complexity policy.
            // PASSWORD_DEFAULT provides the required secure password hashing mechanism.
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($newHash === false) {
                $errors[] = 'Không thể cập nhật mật khẩu, vui lòng thử lại sau.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id AND active = 1');
                    $stmt->execute(['hash' => $newHash, 'id' => (int)$user['user_id']]);
                    if ($stmt->rowCount() !== 1) {
                        throw new RuntimeException('Không thể cập nhật mật khẩu.');
                    }
                    $pdo->commit();
                    $success = 'Đổi mật khẩu thành công.';
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Change password error: ' . $e->getMessage());
                    $errors[] = 'Không thể cập nhật mật khẩu, vui lòng thử lại sau.';
                }
            }
        }
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-5" style="margin-top: 5rem;">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
                <h1 class="h3 fw-bold mb-4">Đổi mật khẩu</h1>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert"><?= e($success) ?></div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?= e($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/auth/change-password.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                        <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password">Mật khẩu mới</label>
                        <input class="form-control" id="new_password" name="new_password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="confirm_password">Xác nhận mật khẩu mới</label>
                        <input class="form-control" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Đổi mật khẩu</button>
                        <a class="btn btn-outline-secondary" href="<?= e(url('/')) ?>">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; require __DIR__ . '/../includes/scripts.php'; ?>
</body>
</html>
