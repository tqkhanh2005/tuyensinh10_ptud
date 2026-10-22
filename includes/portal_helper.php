<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Kiểm tra xem người dùng hiện tại có phải là Quản trị viên (Admin hoặc Sở GD&ĐT) hay không.
 */
function auth_is_admin(): bool
{
    return auth_has_role('so_gdtd') || auth_has_role('admin');
}

/** Fail closed before any teacher page reads school data or accepts a submission. */
function require_teacher_access(): void
{
    require_auth();
    if (auth_is_admin()) return;
    $statement = db()->prepare('SELECT 1 FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id AND r.active=1 JOIN staff st ON st.user_id=u.id AND st.active=1 JOIN schools s ON s.id=st.school_id AND s.active=1 WHERE u.id=:id AND u.active=1 AND r.code IN ("teacher","teacher_thcs","teacher_thpt","staff") LIMIT 1');
    $statement->execute(['id'=>(int)(auth_user()['user_id'] ?? 0)]);
    if (!auth_is_teacher() || !$statement->fetchColumn()) {
        http_response_code(403);
        exit('403 Forbidden - Tài khoản giáo viên hoặc trường liên kết không còn hoạt động.');
    }
}

/**
 * Lấy thông tin cán bộ / giáo viên và trường liên kết (nếu có) của user hiện tại.
 */
function auth_get_teacher_info(): ?array
{
    $u = auth_user();
    if (!$u) {
        return null;
    }

    static $cache = [];
    $userId = (int)($u['user_id'] ?? $u['id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM staff WHERE user_id = :user_id AND active = 1 LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            $cache[$userId] = null;
            return null;
        }

        $row = $staff;
        $row['school_id'] = null;
        $row['school_code'] = null;
        $row['school_name'] = $staff['workplace'];
        $row['school_level'] = null;
        $row['school_active'] = 0;

    $schoolId = !empty($staff['school_id']) ? (int)$staff['school_id'] : 0;
        if ($schoolId > 0) {
            $schStmt = $pdo->prepare('
                SELECT id AS school_id, school_code, name AS school_name, level AS school_level, active AS school_active
                FROM schools 
                WHERE id = :school_id
                LIMIT 1
            ');
            $schStmt->execute(['school_id' => $schoolId]);
            $sch = $schStmt->fetch(PDO::FETCH_ASSOC);
            if ($sch) {
                $row['school_id'] = (int)$sch['school_id'];
                $row['school_code'] = $sch['school_code'];
                $row['school_name'] = $sch['school_name'];
                $row['school_level'] = $sch['school_level'];
                $row['school_active'] = (int)$sch['school_active'];
            }
        }

        $cache[$userId] = $row;
    } catch (Throwable $e) {
        error_log('auth_get_teacher_info error: ' . $e->getMessage());
        $cache[$userId] = null;
    }

    return $cache[$userId];
}

/**
 * Kiểm tra xem người dùng hiện tại có phải là giáo viên / cán bộ hay không.
 * Chỉ trả về true khi:
 * 1. User đã đăng nhập.
 * 2. User có role teacher (hoặc các role tương thích nghiệp vụ).
 * 3. Có staff liên kết bằng staff.user_id.
 * 4. staff.active = 1.
 * 5. Trường công tác hợp lệ (school_id > 0).
 * 6. Trường active (school_active = 1).
 */
function auth_is_teacher(): bool
{
    if (!auth_check()) {
        return false;
    }

    if (!auth_has_role('teacher') && !auth_has_role('teacher_thcs') && !auth_has_role('teacher_thpt') && !auth_has_role('staff')) {
        return false;
    }

    $info = auth_get_teacher_info();
    if (!$info) {
        return false;
    }

    if ((int)($info['active'] ?? 0) !== 1) {
        return false;
    }

    if (empty($info['school_id']) || (int)$info['school_id'] <= 0) {
        return false;
    }

    if ((int)($info['school_active'] ?? 0) !== 1) {
        return false;
    }

    return true;
}

/**
 * Xác định cấp học của giáo viên ('THCS', 'THPT' hoặc 'CHUNG')
 */
function auth_teacher_level(): string
{
    $info = auth_get_teacher_info();
    return $info && in_array($info['school_level'], ['THCS','THPT'], true) ? $info['school_level'] : 'CHUNG';
}

/**
 * Kiểm tra xem người dùng hiện tại có phải là Thí sinh hay không.
 */
function auth_is_candidate(): bool
{
    return auth_check() && auth_has_role('candidate');
}

/**
 * Lấy thông tin hồ sơ thí sinh liên kết với user hiện tại.
 */
function auth_get_candidate_info(): ?array
{
    $u = auth_user();
    if (!$u) {
        return null;
    }

    static $cache = [];
    $userId = (int)($u['user_id'] ?? $u['id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare('
            SELECT c.*, s.name AS middle_school_name, s.school_code AS middle_school_code
            FROM candidates c
            LEFT JOIN schools s ON s.id = c.middle_school_id
            WHERE c.user_id = :user_id
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cache[$userId] = $row ?: null;
    } catch (Throwable $e) {
        error_log('auth_get_candidate_info error: ' . $e->getMessage());
        $cache[$userId] = null;
    }

    return $cache[$userId];
}

/**
 * Lấy URL đích sau khi đăng nhập dựa trên vai trò.
 */
function auth_landing_url(): string
{
    if (auth_is_admin()) {
        return url('/admin/index.php');
    }
    if (auth_is_teacher()) {
        return url('/teacher/index.php');
    }
    return url('/');
}

