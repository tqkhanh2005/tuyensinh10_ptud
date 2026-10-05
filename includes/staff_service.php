<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function staff_validate(array $input): array
{
    $data = [
        'full_name' => trim((string)($input['full_name'] ?? '')),
        'workplace' => trim((string)($input['workplace'] ?? '')),
        'phone' => trim((string)($input['phone'] ?? '')),
        'email' => trim((string)($input['email'] ?? '')),
        'staff_role' => trim((string)($input['staff_role'] ?? '')),
    ];

    $errors = [];

    if ($data['full_name'] === '') $errors['full_name'] = 'Họ tên không được để trống.';
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

function staff_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM staff WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function staff_list(): array
{
    $stmt = db()->query('SELECT * FROM staff ORDER BY active DESC, full_name ASC, id ASC');
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
    $stmt->bindValue(':phone', $data['phone']);
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':full_name', $data['full_name']);
    $stmt->bindValue(':workplace', $data['workplace']);
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
            'INSERT INTO staff (full_name, workplace, phone, email, staff_role, active)
             VALUES (:full_name, :workplace, :phone, :email, :staff_role, 1)'
        );
        $stmt->execute([
            'full_name' => $data['full_name'],
            'workplace' => $data['workplace'],
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'email' => $data['email'] !== '' ? $data['email'] : null,
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
                 phone = :phone,
                 email = :email,
                 staff_role = :staff_role
             WHERE id = :id'
        );
        $ok = $stmt->execute([
            'full_name' => $data['full_name'],
            'workplace' => $data['workplace'],
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'email' => $data['email'] !== '' ? $data['email'] : null,
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
