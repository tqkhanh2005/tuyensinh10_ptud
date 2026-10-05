<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_permission('school.manage');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
require_csrf();
$pdo=db(); $id=(int)($_POST['id']??0);
try {
    school_delete($pdo,$id);
    header('Location: '.url('/schools/index.php')); exit;
} catch (PDOException $e) {
    http_response_code(409);
    exit('Không thể xóa trường đang có dữ liệu liên quan.');
}
