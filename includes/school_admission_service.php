<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function school_validate(array $data, bool $isUpdate = false): array
{
    $errors = [];
    $required = ['school_code', 'name', 'level'];

    foreach ($required as $field) {
        if (trim((string)($data[$field] ?? '')) === '') {
            $errors[$field] = 'Trường bắt buộc nhập thông tin này.';
        }
    }

    if (!in_array(($data['level'] ?? ''), ['THCS', 'THPT'], true)) {
        $errors['level'] = 'Cấp học không hợp lệ.';
    }

    if (isset($data['email']) && trim((string)$data['email']) !== '' &&
        !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ.';
    }

    if (isset($data['phone']) && trim((string)$data['phone']) !== '' &&
        !preg_match('/^[0-9+().\-\s]{7,30}$/', (string)$data['phone'])) {
        $errors['phone'] = 'Số điện thoại không hợp lệ.';
    }

    return $errors;
}

function school_list(PDO $pdo, string $q = '', string $level = '', string $area = ''): array
{
    $sql = 'SELECT * FROM schools WHERE 1=1';
    $params = [];

    if ($q !== '') {
        $sql .= ' AND (school_code LIKE :q OR name LIKE :q OR district LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    if (in_array($level, ['THCS', 'THPT'], true)) {
        $sql .= ' AND level = :level';
        $params['level'] = $level;
    }
    if ($area !== '') {
        $sql .= ' AND admission_area = :area';
        $params['area'] = $area;
    }

    $sql .= ' ORDER BY level, name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function school_find(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM schools WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function school_create(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO schools
        (school_code, name, level, address, district, admission_area, phone, email)
        VALUES (:school_code, :name, :level, :address, :district, :admission_area, :phone, :email)'
    );
    $stmt->execute([
        'school_code' => trim($data['school_code']),
        'name' => trim($data['name']),
        'level' => $data['level'],
        'address' => trim((string)($data['address'] ?? '')) ?: null,
        'district' => trim((string)($data['district'] ?? '')) ?: null,
        'admission_area' => trim((string)($data['admission_area'] ?? '')) ?: null,
        'phone' => trim((string)($data['phone'] ?? '')) ?: null,
        'email' => trim((string)($data['email'] ?? '')) ?: null,
    ]);
    return (int)$pdo->lastInsertId();
}

function school_update(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE schools SET school_code=:school_code, name=:name, level=:level,
         address=:address, district=:district, admission_area=:admission_area,
         phone=:phone, email=:email WHERE id=:id'
    );
    $stmt->execute([
        'id' => $id,
        'school_code' => trim($data['school_code']),
        'name' => trim($data['name']),
        'level' => $data['level'],
        'address' => trim((string)($data['address'] ?? '')) ?: null,
        'district' => trim((string)($data['district'] ?? '')) ?: null,
        'admission_area' => trim((string)($data['admission_area'] ?? '')) ?: null,
        'phone' => trim((string)($data['phone'] ?? '')) ?: null,
        'email' => trim((string)($data['email'] ?? '')) ?: null,
    ]);
}

function school_delete(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM schools WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function admission_period_list(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM admission_periods ORDER BY academic_year DESC, id DESC')->fetchAll();
}

function admission_period_find(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM admission_periods WHERE id=:id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admission_period_create(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO admission_periods
        (name, academic_year, admission_method, plan_content, registration_start, registration_end, status)
        VALUES (:name,:academic_year,:admission_method,:plan_content,:registration_start,:registration_end,:status)'
    );
    $stmt->execute([
        'name' => trim($data['name']),
        'academic_year' => trim($data['academic_year']),
        'admission_method' => trim((string)($data['admission_method'] ?? '')) ?: null,
        'plan_content' => trim((string)($data['plan_content'] ?? '')) ?: null,
        'registration_start' => ($data['registration_start'] ?? '') !== '' ? $data['registration_start'] : null,
        'registration_end' => ($data['registration_end'] ?? '') !== '' ? $data['registration_end'] : null,
        'status' => in_array(($data['status'] ?? ''), ['draft','published','closed'], true) ? $data['status'] : 'draft',
    ]);
    return (int)$pdo->lastInsertId();
}

function quota_rows(PDO $pdo, int $periodId, string $q = '', string $area = ''): array
{
    $sql = 'SELECT s.id AS school_id, s.school_code, s.name, s.admission_area,
                   COALESCE(aq.quota, 0) AS quota,
                   COALESCE(aq.status, "not_set") AS quota_status
            FROM schools s
            LEFT JOIN admission_quotas aq
              ON aq.school_id=s.id AND aq.admission_period_id=:period_id
            WHERE s.level="THPT" AND s.active=1';
    $params = ['period_id' => $periodId];

    if ($q !== '') {
        $sql .= ' AND (s.school_code LIKE :q OR s.name LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    if ($area !== '') {
        $sql .= ' AND s.admission_area=:area';
        $params['area'] = $area;
    }
    $sql .= ' ORDER BY s.name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function quota_save_all(
    PDO $pdo,
    int $periodId,
    array $quotas
): void {
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO admission_quotas
                (
                    admission_period_id,
                    school_id,
                    quota,
                    status
                )
             VALUES
                (
                    :period_id,
                    :school_id,
                    :quota,
                    "set"
                )
             ON DUPLICATE KEY UPDATE
                quota=VALUES(quota),
                status="set",
                updated_at=CURRENT_TIMESTAMP'
        );

        foreach ($quotas as $schoolId => $quota) {
            if (
                !ctype_digit((string)$schoolId)
                || !ctype_digit((string)$quota)
            ) {
                throw new InvalidArgumentException(
                    'Chỉ tiêu phải là số nguyên dương.'
                );
            }

            if ((int)$quota <= 0) {
                throw new InvalidArgumentException(
                    'Chỉ tiêu phải là số nguyên dương.'
                );
            }

            $stmt->execute([
                'period_id' => $periodId,
                'school_id' => (int)$schoolId,
                'quota' => (int)$quota,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function quota_publish(PDO $pdo, int $periodId): void
{
    $stmt = $pdo->prepare(
        'UPDATE admission_quotas
         SET published_at=CURRENT_TIMESTAMP
         WHERE admission_period_id=:period_id'
    );
    $stmt->execute(['period_id' => $periodId]);
}
