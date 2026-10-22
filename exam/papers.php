<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_once __DIR__ . '/../includes/excel_service.php';
require_permission('paper.manage');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? $_POST['subject_id'] ?? 0);
$roomId = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
require_once __DIR__.'/../includes/grading_filters.php';
$context=grading_filter_context($pdo,$periodId,$subjectId);$periodId=$context['period'];$subjectId=$context['subject'];
$error = '';
$message = '';
$codePreview = null;

if (isset($_GET['export']) && $_GET['export'] === '1') {
    $rows = paper_rows($pdo, $periodId, $subjectId, $roomId);
    $out = [];
    foreach ($rows as $r) if (in_array($r['attendance_status'],['present','present_penalty'],true)) $out[] = [$r['candidate_code'], $r['anonymous_code'], $r['attendance_status'], $r['penalty_percent']];
    excel_xlsx_download('danh_sach_phach.xls.xlsx', ['SBD', 'So phach', 'Trang thai', 'Muc tru (%)'], $out);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'generate_papers') {
            $created = generate_exam_papers($pdo, $periodId, $subjectId, $roomId);
            $message = 'Đã tạo/đồng bộ ' . $created . ' bài thi từ danh sách phòng thi.';
        } elseif ($action === 'attendance') {
            save_attendance($pdo, $periodId, $subjectId, $roomId, (array)($_POST['attendance'] ?? []));
            $message = 'Đã lưu điểm danh/xử phạt.';
        } elseif ($action === 'random_preview') {
            $codePreview = preview_random_codes($pdo, $periodId, $subjectId, $roomId);
        } elseif ($action === 'random_save' || $action === 'manual_save') {
            save_codes($pdo, $periodId, $subjectId, $roomId, (array)($_POST['codes'] ?? []));
            $message = 'Đã lưu bảng ánh xạ số báo danh - số phách.';
        } elseif ($action === 'manual_preview') {
            $codePreview = (array)($_POST['codes'] ?? []);
            $seen = [];
            foreach ($codePreview as $code) {
                $code = trim((string)$code);
                if ($code === '' || isset($seen[$code])) throw new InvalidArgumentException('Số phách trống hoặc bị trùng trong bản xem trước.');
                $seen[$code] = true;
            }
            $message = 'Đã kiểm tra bản xem trước. Chưa lưu dữ liệu.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$rows = paper_rows($pdo, $periodId, $subjectId, $roomId);
$subject = null;
try { $subject = exam_subject_context($pdo, $periodId, $subjectId); } catch (Throwable $e) { $error = $error ?: $e->getMessage(); }
?>
<?php require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Quản lý phách', 'exam'); ?>
<h1>Quản lý phách</h1><?php grading_filters($context);?><p><a href="<?=e(url('/exam/grading.php?period_id='.$periodId))?>">Chấm thi và phúc khảo</a></p>
<?php if ($message): ?><p><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
<p>Quản lý số phách theo môn thi và phòng thi. Tải danh sách để đối chiếu trước khi lưu.</p>
<form method="post"><?= csrf_field() ?><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="room_id" value="<?= $roomId ?>"><input type="hidden" name="action" value="generate_papers"><button>Tạo/đồng bộ danh sách bài thi</button></form>
<?php if ($rows): ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="attendance"><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="room_id" value="<?= $roomId ?>">
<table border="1"><tr><th>SBD</th><th>Thí sinh</th><th>Điểm danh/xử phạt</th><th>Phách hiện tại</th><th>Trạng thái</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['candidate_code']) ?></td><td><?= e($r['full_name']) ?></td><td><select name="attendance[<?= (int)$r['id'] ?>][status]"><option value="present" <?= $r['attendance_status']==='present'?'selected':'' ?>>Có mặt</option><option value="present_penalty" <?= $r['attendance_status']==='present_penalty'?'selected':'' ?>>Có mặt - bị trừ điểm</option><option value="absent" <?= $r['attendance_status']==='absent'?'selected':'' ?>>Vắng mặt</option></select><select name="attendance[<?= (int)$r['id'] ?>][penalty]"><option value="">-- mức trừ --</option><?php foreach ([25,50,100] as $p): ?><option value="<?= $p ?>" <?= (int)$r['penalty_percent']===$p?'selected':'' ?>><?= $p ?>%</option><?php endforeach; ?></select></td><td><?= e((string)($r['anonymous_code'] ?? '')) ?></td><td><?= e($r['paper_status']) ?></td></tr><?php endforeach; ?></table><button>Lưu điểm danh/xử phạt</button></form>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="random_preview"><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="room_id" value="<?= $roomId ?>"><button>Phát sinh phách ngẫu nhiên → Preview</button></form>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="manual_preview"><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="room_id" value="<?= $roomId ?>"><table border="1"><tr><th>SBD</th><th>Số phách thủ công</th></tr><?php foreach ($rows as $r): if (in_array($r['attendance_status'],['present','present_penalty'],true)): ?><tr><td><?= e($r['candidate_code']) ?></td><td><input name="codes[<?= (int)$r['id'] ?>]" value="<?= e((string)($r['anonymous_code'] ?? '')) ?>"></td></tr><?php endif; endforeach; ?></table><button>Kiểm tra phách thủ công → Preview</button></form>
<a href="?period_id=<?= $periodId ?>&subject_id=<?= $subjectId ?>&room_id=<?= $roomId ?>&export=1">Xuất Excel danh sách phách</a>
<?php endif; ?>
<?php if ($codePreview !== null): ?><h2>Preview bảng ánh xạ</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="<?= isset($_POST['action']) && $_POST['action']==='manual_preview' ? 'manual_save' : 'random_save' ?>"><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="room_id" value="<?= $roomId ?>"><table border="1"><tr><th>Paper ID</th><th>Số phách</th></tr><?php foreach ($codePreview as $paperId => $code): ?><tr><td><?= (int)$paperId ?></td><td><?= e((string)$code) ?><input type="hidden" name="codes[<?= (int)$paperId ?>]" value="<?= e((string)$code) ?>"></td></tr><?php endforeach; ?></table><button>Xác nhận lưu</button><button type="button" onclick="history.back()">Hủy</button></form><?php endif; ?>
<?php workflow_layout_end(); ?>
