<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function staff_active_schools(): array
{
    $stmt = db()->query('SELECT id, school_code, name, level FROM schools WHERE active = 1 ORDER BY level ASC, name ASC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function staff_find_school(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, school_code, name, level, active FROM schools WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function staff_validate(array $input): array
{
    $schoolId = filter_var($input['school_id'] ?? null, FILTER_VALIDATE_INT);
    $data = [
        'full_name' => trim((string)($input['full_name'] ?? '')),
        'workplace' => trim((string)($input['workplace'] ?? '')),
        'school_id' => ($schoolId && $schoolId > 0) ? (int)$schoolId : null,
        'phone' => trim((string)($input['phone'] ?? '')),
        'email' => trim((string)($input['email'] ?? '')),
        'staff_role' => trim((string)($input['staff_role'] ?? '')),
    ];

    $errors = [];

    if ($data['full_name'] === '') $errors['full_name'] = 'Họ tên không được để trống.';

    if ($data['school_id'] !== null) {
        $sch = staff_find_school($data['school_id']);
        if (!$sch || (int)$sch['active'] !== 1) {
            $errors['school_id'] = 'Trường công tác không tồn tại hoặc đã ngừng hoạt động.';
        } else {
            if ($data['workplace'] === '') {
                $data['workplace'] = $sch['name'];
            }
        }
    }

    if ($data['workplace'] === '') $errors['workplace'] = 'Đơn vị công tác không được để trống.';
    if ($data['staff_role'] === '') $errors['staff_role'] = 'Vai trò không được để trống.';

    if ($data['phone'] !== '' && !preg_match('/^[0-9+().\-\s]{7,30}$/', $data['phone'])) {
        $errors['phone'] = 'Số điện thoại không hợp lệ.';
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ.';
    }

    return [$data, $errors];
}

function staff_validate_account(array $input): array
{
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $errors = [];

    if ($username === '') {
        $errors['username'] = 'Tên đăng nhập không được để trống.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $errors['username'] = 'Tên đăng nhập từ 3 đến 50 ký tự, chỉ gồm chữ cái, chữ số, gạch nối, gạch dưới hoặc dấu chấm.';
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(:username) LIMIT 1');
        $stmt->execute(['username' => $username]);
        if ($stmt->fetchColumn()) {
            $errors['username'] = 'Tên đăng nhập đã tồn tại trong hệ thống.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Mật khẩu không được để trống.';
    } elseif (mb_strlen($password) < 6) {
        $errors['password'] = 'Mật khẩu phải có độ dài tối thiểu 6 ký tự.';
    }

    $accountData = [
        'username' => $username,
        'password' => $password,
    ];

    return [$accountData, $errors];
}

function staff_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM staff WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function staff_list(): array
{
    $stmt = db()->query('
        SELECT s.*, u.username,
               (
                   SELECT 1
                   FROM user_roles ur
                   JOIN roles r ON r.id = ur.role_id
                   WHERE ur.user_id = u.id AND r.code = "teacher" AND r.active = 1
                   LIMIT 1
               ) AS is_teacher
        FROM staff s
        LEFT JOIN users u ON u.id = s.user_id
        ORDER BY s.active DESC, s.full_name ASC, s.id ASC
    ');
    return $stmt->fetchAll();
}

function staff_has_duplicate(array $data, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM staff
            WHERE active = 1
              AND (
                (phone <> \'\' AND phone = :phone)
                OR (email <> \'\' AND LOWER(email) = LOWER(:email))
                OR (LOWER(full_name) = LOWER(:full_name) AND LOWER(workplace) = LOWER(:workplace))
              )';

    if ($excludeId !== null) {
        $sql .= ' AND id <> :exclude_id';
    }
    $sql .= ' LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':phone', $data['phone'] ?? '');
    $stmt->bindValue(':email', $data['email'] ?? '');
    $stmt->bindValue(':full_name', $data['full_name'] ?? '');
    $stmt->bindValue(':workplace', $data['workplace'] ?? '');
    if ($excludeId !== null) $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
    $stmt->execute();
    return (bool)$stmt->fetchColumn();
}

function staff_create(array $data): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if (staff_has_duplicate($data)) throw new RuntimeException('Cán bộ - giáo viên đã tồn tại.');
        $stmt = $pdo->prepare(
            'INSERT INTO staff (full_name, workplace, school_id, phone, email, staff_role, active)
             VALUES (:full_name, :workplace, :school_id, :phone, :email, :staff_role, 1)'
        );
        $stmt->execute([
            'full_name' => $data['full_name'],
            'workplace' => $data['workplace'],
            'school_id' => !empty($data['school_id']) ? (int)$data['school_id'] : null,
            'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
            'email' => ($data['email'] ?? '') !== '' ? $data['email'] : null,
            'staff_role' => $data['staff_role'],
        ]);
        $id = (int)$pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function staff_create_with_account(array $staffData, array $accountData): int
{
    $pdo = db();

    // 1. Validate school_id
    $schoolId = (int)($staffData['school_id'] ?? 0);
    if ($schoolId <= 0) {
        throw new InvalidArgumentException('Bắt buộc phải chọn trường công tác cho cán bộ - giáo viên khi cấp tài khoản.');
    }
    $sch = staff_find_school($schoolId);
    if (!$sch || (int)$sch['active'] !== 1) {
        throw new InvalidArgumentException('Trường công tác không tồn tại hoặc đã ngừng hoạt động.');
    }
    $staffData['school_id'] = $schoolId;
    if (empty($staffData['workplace'])) {
        $staffData['workplace'] = $sch['name'];
    }

    // 2. Validate username & password
    $username = trim((string)($accountData['username'] ?? ''));
    $password = (string)($accountData['password'] ?? '');
    if ($username === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        throw new InvalidArgumentException('Tên đăng nhập không hợp lệ (yêu cầu từ 3-50 ký tự chữ, số, _, -, .).');
    }
    if (mb_strlen($password) < 6) {
        throw new InvalidArgumentException('Mật khẩu phải có độ dài tối thiểu 6 ký tự.');
    }

    $pdo->beginTransaction();
    try {
        // Kiểm tra username trùng
        $checkUser = $pdo->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(:u) FOR UPDATE');
        $checkUser->execute(['u' => $username]);
        if ($checkUser->fetchColumn()) {
            throw new RuntimeException('Tên đăng nhập đã được sử dụng.');
        }

        // Kiểm tra duplicate staff
        if (staff_has_duplicate($staffData)) {
            throw new RuntimeException('Cán bộ - giáo viên đã tồn tại.');
        }

        // Tạo user
        $userStmt = $pdo->prepare(
            'INSERT INTO users (username, password_hash, full_name, role, email, phone, active, created_at, updated_at)
             VALUES (:username, :password_hash, :full_name, "teacher", :email, :phone, 1, NOW(), NOW())'
        );
        $userStmt->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $staffData['full_name'],
            'email' => ($staffData['email'] ?? '') !== '' ? $staffData['email'] : null,
            'phone' => ($staffData['phone'] ?? '') !== '' ? $staffData['phone'] : null,
        ]);
        $userId = (int)$pdo->lastInsertId();

        // Gán role teacher trong user_roles
        $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code = "teacher" AND active = 1 LIMIT 1');
        $roleStmt->execute();
        $roleId = (int)$roleStmt->fetchColumn();
        if ($roleId <= 0) {
            throw new RuntimeException('Vai trò giáo viên (teacher) chưa tồn tại trong hệ thống.');
        }

        $urStmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
        $urStmt->execute(['user_id' => $userId, 'role_id' => $roleId]);

        // Tạo staff liên kết user_id và school_id
        $staffStmt = $pdo->prepare(
            'INSERT INTO staff (user_id, school_id, full_name, workplace, phone, email, staff_role, active, created_at, updated_at)
             VALUES (:user_id, :school_id, :full_name, :workplace, :phone, :email, :staff_role, 1, NOW(), NOW())'
        );
        $staffStmt->execute([
            'user_id' => $userId,
            'school_id' => $staffData['school_id'],
            'full_name' => $staffData['full_name'],
            'workplace' => $staffData['workplace'],
            'phone' => ($staffData['phone'] ?? '') !== '' ? $staffData['phone'] : null,
            'email' => ($staffData['email'] ?? '') !== '' ? $staffData['email'] : null,
            'staff_role' => $staffData['staff_role'],
        ]);
        $staffId = (int)$pdo->lastInsertId();

        $pdo->commit();
        return $staffId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Ghi log lỗi không chứa mật khẩu
        error_log('staff_create_with_account error: ' . $e->getMessage());
        throw $e;
    }
}

function staff_update(int $id, array $data): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $find = $pdo->prepare('SELECT id FROM staff WHERE id = :id FOR UPDATE');
        $find->execute(['id' => $id]);
        if (!$find->fetchColumn()) throw new RuntimeException('Không tìm thấy cán bộ - giáo viên.');
        if (staff_has_duplicate($data, $id)) throw new RuntimeException('Cán bộ - giáo viên đã tồn tại.');

        $stmt = $pdo->prepare(
            'UPDATE staff
             SET full_name = :full_name,
                 workplace = :workplace,
                 school_id = :school_id,
                 phone = :phone,
                 email = :email,
                 staff_role = :staff_role
             WHERE id = :id'
        );
        $ok = $stmt->execute([
            'full_name' => $data['full_name'],
            'workplace' => $data['workplace'],
            'school_id' => !empty($data['school_id']) ? (int)$data['school_id'] : null,
            'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
            'email' => ($data['email'] ?? '') !== '' ? $data['email'] : null,
            'staff_role' => $data['staff_role'],
            'id' => $id,
        ]);
        if (!$ok) throw new RuntimeException('Không thể cập nhật cán bộ - giáo viên.');
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function staff_has_active_dependencies(int $id): bool
{
    $pdo = db();

    $council = $pdo->prepare(
        'SELECT 1
         FROM council_members cm
         INNER JOIN councils c ON c.id = cm.council_id
         WHERE cm.staff_id = :id
           AND c.status <> "cancelled"
         LIMIT 1'
    );
    $council->execute(['id' => $id]);
    if ($council->fetchColumn()) return true;

    $invigilator = $pdo->prepare(
        'SELECT 1
         FROM exam_invigilator_assignments
         WHERE staff_id = :id AND status = "assigned"
         LIMIT 1'
    );
    $invigilator->execute(['id' => $id]);
    return (bool)$invigilator->fetchColumn();
}

function staff_deactivate(int $id): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, active FROM staff WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        $staff = $stmt->fetch();
        if (!$staff) throw new RuntimeException('Không tìm thấy cán bộ - giáo viên.');
        if ((int)$staff['active'] !== 1) throw new RuntimeException('Cán bộ - giáo viên đã không hoạt động.');

        if (staff_has_active_dependencies($id)) {
            throw new RuntimeException('Không thể xóa cán bộ - giáo viên đang được phân công.');
        }

        $update = $pdo->prepare('UPDATE staff SET active = 0 WHERE id = :id AND active = 1');
        $update->execute(['id' => $id]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('Không thể xóa cán bộ - giáo viên.');
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/**
 * Lấy thông tin tài khoản người dùng liên kết với cán bộ - giáo viên.
 */
function staff_get_account_info(int $staffId): ?array
{
    $staff = staff_find($staffId);
    if (!$staff || empty($staff['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('
        SELECT u.id AS user_id, u.username, u.active AS user_active,
               EXISTS (
                   SELECT 1
                   FROM user_roles ur
                   JOIN roles r ON r.id = ur.role_id
                   WHERE ur.user_id = u.id AND r.code = "teacher" AND r.active = 1
               ) AS is_teacher
        FROM users u
        WHERE u.id = :user_id
        LIMIT 1
    ');
    $stmt->execute(['user_id' => (int)$staff['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'staff_id' => (int)$staff['id'],
        'staff_name' => $staff['full_name'],
        'school_id' => (int)($staff['school_id'] ?? 0),
        'workplace' => $staff['workplace'],
        'user_id' => (int)$row['user_id'],
        'username' => $row['username'],
        'user_active' => (int)$row['user_active'],
        'is_teacher' => (bool)$row['is_teacher'],
    ];
}

/**
 * Đặt lại mật khẩu tài khoản Giáo viên (Teacher Portal).
 * Chỉ cho phép đặt lại cho user liên kết có vai trò teacher.
 *
 * @throws RuntimeException|InvalidArgumentException|Throwable
 */
function staff_reset_password(int $staffId, string $newPassword): bool
{
    $staff = staff_find($staffId);
    if (!$staff) {
        throw new RuntimeException('Không tìm thấy cán bộ - giáo viên.');
    }

    $userId = (int)($staff['user_id'] ?? 0);
    if ($userId <= 0) {
        throw new RuntimeException('Cán bộ - giáo viên này chưa được cấp tài khoản người dùng.');
    }

    if ((int)$staff['active'] !== 1) {
        throw new RuntimeException('Không thể đặt lại mật khẩu cho cán bộ - giáo viên đang không hoạt động.');
    }

    $pdo = db();
    $uStmt = $pdo->prepare('SELECT id, username, active FROM users WHERE id = :user_id LIMIT 1');
    $uStmt->execute(['user_id' => $userId]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new RuntimeException('Tài khoản người dùng liên kết không tồn tại.');
    }

    // Xác minh user có role teacher
    $rStmt = $pdo->prepare('
        SELECT 1
        FROM user_roles ur
        JOIN roles r ON r.id = ur.role_id
        WHERE ur.user_id = :user_id
          AND r.code = "teacher"
          AND r.active = 1
        LIMIT 1
    ');
    $rStmt->execute(['user_id' => $userId]);
    if (!$rStmt->fetchColumn()) {
        throw new RuntimeException('Chỉ cho phép đặt lại mật khẩu cho tài khoản Giáo viên (Teacher Portal).');
    }

    if (mb_strlen($newPassword) < 6) {
        throw new InvalidArgumentException('Mật khẩu mới phải có độ dài tối thiểu 6 ký tự.');
    }

    $pdo->beginTransaction();
    try {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $up = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $up->execute(['hash' => $hash, 'id' => $userId]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('staff_reset_password error occurred for staff_id: ' . $staffId);
        throw $e;
    }
}
