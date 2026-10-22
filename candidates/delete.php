<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';

require_permission('candidate.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

require_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: ' . url('/candidates/index.php?delete_error=1'));
    exit;
}

try {
    candidate_deactivate((int) $id);
    header('Location: ' . url('/candidates/index.php?deleted=1'));
    exit;
} catch (RuntimeException $e) {
    error_log('Candidate delete rejected: ' . $e->getMessage());
    header('Location: ' . url('/candidates/index.php?delete_error=1'));
    exit;
} catch (Throwable $e) {
    error_log('Candidate delete error: ' . $e->getMessage());
    header('Location: ' . url('/candidates/index.php?delete_error=1'));
    exit;
}
