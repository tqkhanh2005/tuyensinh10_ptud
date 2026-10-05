<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_once __DIR__ . '/../includes/excel_service.php';
require_permission('score.enter');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? $_POST['subject_id'] ?? 0);
$error = '';
$message = '';
$preview = null;
$batchId = (int)($_GET['batch_id'] ?? $_POST['batch_id'] ?? 0);

if (isset($_GET['download']) && $_GET['download'] === 'template') {
    $subject = exam_subject_context($pdo, $periodId, $subjectId);
    $q = $pdo->prepare('SELECT p.anonymous_code FROM exam_papers p WHERE p.admission_period_id=:p AND p.exam_subject_id=:s AND p.anonymous_code IS NOT NULL AND p.attendance_status IN ("present","present_penalty") ORDER BY p.anonymous_code');
    $q->execute(['p' => $periodId, 's' => $subjectId]);
    $rows = [];
    foreach ($q as $r) $rows[] = [(string)$r['anonymous_code'], '', '', ''];
    excel_xlsx_download('mau_nhap_diem.xls.xlsx', ['anonymous_code', 'score_1', 'score_2', 'total_score'], $rows);
}

if (isset($_GET['download']) && $_GET['download'] === 'errors' && $batchId > 0) {
    $q = $pdo->prepare('SELECT row_number,anonymous_code,error_message FROM exam_score_import_rows r JOIN exam_score_import_batches b ON b.id=r.batch_id WHERE r.batch_id=:b AND b.user_id=:u AND r.is_valid=0 ORDER BY row_number');
    $q->execute(['b' => $batchId, 'u' => (int)auth_user()['id']]);
    $rows = [];
    foreach ($q as $r) $rows[] = [$r['row_number'], $r['anonymous_code'], $r['error_message']];
    excel_xlsx_download('loi_nhap_diem.xls.xlsx', ['row', 'anonymous_code', 'error'], $rows);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'manual_preview') {
            $preview = validate_score_rows($pdo, $periodId, $subjectId, (array)($_POST['score'] ?? []));
        } elseif ($action === 'manual_save') {
            $result = validate_score_rows($pdo, $periodId, $subjectId, (array)($_POST['score'] ?? []));
            if (!$result['valid']) throw new InvalidArgumentException('Không có dữ liệu hợp lệ để lưu.');
            save_scores($pdo, (int)auth_user()['id'], $periodId, $subjectId, $result['valid']);
            $message = 'Đã lưu các dòng điểm hợp lệ.';
        } elseif ($action === 'upload_preview') {
            if (!isset($_FILES['score_file']) || $_FILES['score_file']['error'] !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Vui lòng tải file Excel hợp lệ.');
            if ((int)$_FILES['score_file']['size'] > 5 * 1024 * 1024) throw new InvalidArgumentException('File vượt quá giới hạn kỹ thuật 5 MB.');
            $batchId = create_score_import_batch($pdo, (int)auth_user()['id'], $periodId, $subjectId, (string)$_FILES['score_file']['name'], excel_parse_upload((string)$_FILES['score_file']['tmp_name'], (string)$_FILES['score_file']['name']));
            $q = $pdo->prepare('SELECT valid_rows,invalid_rows,status FROM exam_score_import_batches WHERE id=:id AND user_id=:u');
            $q->execute(['id' => $batchId, 'u' => (int)auth_user()['id']]); $preview = $q->fetch();
            $message = 'Đã kiểm tra file; chưa lưu điểm.';
        } elseif ($action === 'import_save') {
            save_score_import_batch($pdo, $batchId, (int)auth_user()['id']);
            $message = 'Đã lưu các dòng hợp lệ từ file.';
            $batchId = 0;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$q = $pdo->prepare('SELECT p.anonymous_code,p.paper_status FROM exam_papers p WHERE p.admission_period_id=:p AND p.exam_subject_id=:s AND p.anonymous_code IS NOT NULL ORDER BY p.anonymous_code');
$q->execute(['p' => $periodId, 's' => $subjectId]);
$papers = $q->fetchAll();
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>Nhập điểm</title></head><body>
<h1>Nhập điểm theo số phách</h1>
<p>Luồng chính theo UC02: tải file mẫu → tải file kết quả → kiểm tra cấu trúc/dữ liệu → preview → xác nhận lưu.</p>
<p><strong>[MISSING SPECIFICATION]</strong> Tài liệu không quy định MIME/đuôi Excel chính thức; hệ thống hỗ trợ XLSX khi ZipArchive có sẵn, cùng CSV/Excel XML fallback.</p>
<?php if ($message): ?><p><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
<p><a href="?period_id=<?= $periodId ?>&subject_id=<?= $subjectId ?>&download=template">Tải file mẫu</a></p>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="upload_preview"><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="file" name="score_file" accept=".xlsx,.xls,.csv" required><button>Kiểm tra & Preview file</button></form>
<?php if ($batchId > 0 && $preview): ?><h2>Preview import #<?= $batchId ?></h2><p>Hợp lệ: <?= (int)$preview['valid_rows'] ?> | Lỗi: <?= (int)$preview['invalid_rows'] ?></p><p><a href="?period_id=<?= $periodId ?>&subject_id=<?= $subjectId ?>&batch_id=<?= $batchId ?>&download=errors">Tải file các dòng lỗi</a></p><?php if ((int)$preview['valid_rows'] > 0): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="import_save"><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="batch_id" value="<?= $batchId ?>"><button>Xác nhận lưu các dòng hợp lệ</button><button type="button" onclick="history.back()">Hủy</button></form><?php endif; ?><?php endif; ?>
<h2>Nhập trực tiếp/Preview</h2>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="manual_preview"><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><table border="1"><tr><th>Số phách</th><th>Điểm 1</th><th>Điểm 2</th><th>Tổng điểm</th><th>Trạng thái</th></tr><?php foreach ($papers as $p): ?><tr><td><?= e($p['anonymous_code']) ?></td><td><input name="score[<?= e($p['anonymous_code']) ?>][score_1]" type="number" step="0.01"></td><td><input name="score[<?= e($p['anonymous_code']) ?>][score_2]" type="number" step="0.01"></td><td><input name="score[<?= e($p['anonymous_code']) ?>][total_score]" type="number" step="0.01"></td><td><?= e($p['paper_status']) ?></td></tr><?php endforeach; ?></table><button>Kiểm tra/Preview</button></form>
<?php if (is_array($preview) && array_key_exists('valid', $preview)): ?><h3>Kết quả kiểm tra</h3><p>Hợp lệ: <?= count($preview['valid']) ?> | Lỗi: <?= count($preview['errors']) ?></p><?php if ($preview['errors']): ?><ul><?php foreach ($preview['errors'] as $err): ?><li><?= e($err['code']) ?>: <?= e($err['error']) ?></li><?php endforeach; ?></ul><?php endif; ?><?php if ($preview['valid']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="manual_save"><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><?php foreach ($preview['valid'] as $v): ?><input type="hidden" name="score[<?= e($v['code']) ?>][score_1]" value="<?= e((string)($v['score_1'] ?? '')) ?>"><input type="hidden" name="score[<?= e($v['code']) ?>][score_2]" value="<?= e((string)($v['score_2'] ?? '')) ?>"><input type="hidden" name="score[<?= e($v['code']) ?>][total_score]" value="<?= e((string)$v['total_score']) ?>"><?php endforeach; ?><button>Xác nhận lưu dòng hợp lệ</button><button type="button" onclick="history.back()">Hủy</button></form><?php endif; ?><?php endif; ?>
</body></html>
