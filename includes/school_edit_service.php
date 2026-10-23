<?php
declare(strict_types=1);

require_once __DIR__ . '/school_batch_service.php';

/** Update contact and name fields; the school's level is immutable here. */
function school_edit_save(PDO $pdo, int $id, array $data): array
{
    $row = school_batch_rows([$data])[0];
    $pdo->beginTransaction();
    try {
        $existing = school_find($pdo, $id);
        if (!$existing) {
            throw new OutOfBoundsException('Không tìm thấy trường. Trường có thể đã bị xóa.');
        }
        $row['level'] = $existing['level'];
        $errors = school_batch_validate($pdo, [$row], $id)[0] ?? [];
        if ($errors) {
            $pdo->rollBack();
            return $errors;
        }
        unset($row['level']);
        $stmt = $pdo->prepare('UPDATE schools SET name=:name, address=:address, phone=:phone, email=:email WHERE id=:id');
        $stmt->execute($row + ['id' => $id]);
        $pdo->commit();
        return [];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
