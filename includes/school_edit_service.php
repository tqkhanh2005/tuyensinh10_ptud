<?php
declare(strict_types=1);

require_once __DIR__ . '/school_batch_service.php';

/** Update only the five editable fields, after validating against other schools. */
function school_edit_save(PDO $pdo, int $id, array $data): array
{
    $row = school_batch_rows([$data])[0];
    $pdo->beginTransaction();
    try {
        $errors = school_batch_validate($pdo, [$row], $id)[0] ?? [];
        if (!school_find($pdo, $id)) {
            throw new OutOfBoundsException('Không tìm thấy trường. Trường có thể đã bị xóa.');
        }
        if ($errors) {
            $pdo->rollBack();
            return $errors;
        }
        $stmt = $pdo->prepare('UPDATE schools SET name=:name, level=:level, address=:address, phone=:phone, email=:email WHERE id=:id');
        $stmt->execute($row + ['id' => $id]);
        $pdo->commit();
        return [];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
