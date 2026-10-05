<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appeal_service.php';
require_once __DIR__ . '/../includes/excel_service.php';

require_permission('appeal.manage');
$pdo = db();
$currentUser = auth_user();
$currentUserId = (int)$currentUser['user_id'];
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? $_POST['subject_id'] ?? 0);
$error = '';
$message = '';
$preview = null;


function uc03_template_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        if (($r['status'] ?? '') !== 'grading') continue;
        if (empty($r['assigned_grader_user_id'])) continue;
        $out[] = [
            (int)$r['id'],
            (string)$r['anonymous_code'],
            (string)$r['candidate_code'],
            (float)$r['original_score'],
            (string)($r['assigned_grader_username'] ?? $r['assigned_grader_name'] ?? ''),
            '',
            '',
        ];
    }
    return $out;
}

function uc03_download_error_file(string $format, array $rows): void
{
    $headers = array_merge(UC03_HEADERS, ['Lỗi']);
    $data = [];
    foreach ($rows as $r) $data[] = array_merge($r['row'], [$r['error']]);
    if ($format === 'csv') excel_csv_download('uc03_loi_phuc_khao.csv', $headers, $data);
    excel_xlsx_download('uc03_loi_phuc_khao.xlsx', $headers, $data);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();
        $action = (string)($_POST['action'] ?? '');
        $appealId = (int)($_POST['appeal_id'] ?? 0);

        if ($action === 'assign') {
            if (!auth_has_permission('council.manage')) throw new RuntimeException('403 Forbidden');
            assign_appeal_grader($pdo, $appealId, (int)($_POST['council_member_id'] ?? 0), $currentUserId);
            $message = 'Đã phân công cán bộ chấm phúc khảo.';
        } elseif ($action === 'start_grading') {
            set_appeal_grading($pdo, $appealId, $currentUserId);
            $message = 'Đã chuyển đơn sang trạng thái Đang chấm.';
        } elseif ($action === 'complete') {
            $check = validate_appeal_result($pdo, $appealId, $_POST['new_score'] ?? null, (string)($_POST['reason'] ?? ''), $currentUserId);
            if (!$check['ok']) throw new RuntimeException($check['error']);
            complete_appeal($pdo, $appealId, (float)$check['new_score'], $check['reason'], $currentUserId);
            $message = 'Đã cập nhật kết quả phúc khảo.';
        } elseif ($action === 'download_template') {
            $rows = admin_appeals($pdo, $periodId, $subjectId > 0 ? $subjectId : null);
            excel_xlsx_download('uc03_mau_ket_qua_phuc_khao.xlsx', UC03_HEADERS, uc03_template_rows($rows));
        } elseif ($action === 'download_template_csv') {
            $rows = admin_appeals($pdo, $periodId, $subjectId > 0 ? $subjectId : null);
            excel_csv_download('uc03_mau_ket_qua_phuc_khao.csv', UC03_HEADERS, uc03_template_rows($rows));
        } elseif ($action === 'upload_preview') {
            if ($periodId <= 0 || $subjectId <= 0) throw new InvalidArgumentException('Phải chọn kỳ tuyển sinh và môn thi trước khi upload.');
            if (!isset($_FILES['result_file']) || !is_array($_FILES['result_file']) || (int)$_FILES['result_file']['error'] !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Không thể nhận file tải lên.');
            $tmp = (string)$_FILES['result_file']['tmp_name'];
            $name = (string)$_FILES['result_file']['name'];
            $parsed = excel_parse_upload($tmp, $name);
            $preview = uc03_validate_import($pdo, $periodId, $subjectId, $parsed, $currentUserId);
            $_SESSION['uc03_preview'] = ['period_id' => $periodId, 'subject_id' => $subjectId, 'user_id' => $currentUserId, 'valid' => $preview['valid'], 'errors' => $preview['errors'], 'created_at' => time()];
            $message = 'Đã kiểm tra file. Hãy xem trước và xác nhận lưu các dòng hợp lệ.';
        } elseif ($action === 'download_errors') {
            $state = $_SESSION['uc03_preview'] ?? null;
            if (!is_array($state) || (int)$state['user_id'] !== $currentUserId) throw new RuntimeException('Không có dữ liệu preview hợp lệ để xuất lỗi.');
            uc03_download_error_file((string)($_POST['format'] ?? 'xlsx'), $state['errors'] ?? []);
        } elseif ($action === 'confirm_import') {
            $state = $_SESSION['uc03_preview'] ?? null;
            if (!is_array($state) || (int)$state['user_id'] !== $currentUserId || (int)$state['period_id'] !== $periodId || (int)$state['subject_id'] !== $subjectId) throw new RuntimeException('Preview không hợp lệ hoặc đã hết phiên.');
            complete_appeal_batch($pdo, $state['valid'] ?? [], $currentUserId);
            unset($_SESSION['uc03_preview']);
            $message = 'Đã lưu các dòng hợp lệ theo transaction và gửi thông báo kết quả.';
        } else {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$subjects = [];
if ($periodId > 0) {
    $q = $pdo->prepare(
        'SELECT s.id, s.subject_code, s.name,
                SUM(CASE WHEN ar.status IN ("pending","grading") THEN 1 ELSE 0 END) AS pending_count
         FROM exam_subjects s
         LEFT JOIN appeal_requests ar ON ar.exam_subject_id = s.id AND ar.admission_period_id = :p
         WHERE s.admission_period_id = :p2
         GROUP BY s.id, s.subject_code, s.name
         ORDER BY s.name'
    );
    $q->execute(['p' => $periodId, 'p2' => $periodId]);
    $subjects = $q->fetchAll();
}
$rows = $periodId > 0 ? admin_appeals($pdo, $periodId, $subjectId > 0 ? $subjectId : null) : [];
$previewState = $_SESSION['uc03_preview'] ?? null;
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Cập nhật kết quả phúc khảo</title></head><body>
<h1>Cập nhật kết quả phúc khảo</h1>
<?php if($message):?><p><?=e($message)?></p><?php endif;?><?php if($error):?><p><?=e($error)?></p><?php endif;?>
<form method="get"><label>Kỳ tuyển sinh <input type="number" name="period_id" value="<?=e((string)$periodId)?>" required></label><label>Môn <select name="subject_id"><option value="0">Tất cả</option><?php foreach($subjects as $s):?><option value="<?=$s['id']?>" <?=($subjectId===(int)$s['id']?'selected':'')?>><?=e($s['subject_code'].' - '.$s['name'].' ('.$s['pending_count'].')')?></option><?php endforeach;?></select></label><button>Lọc</button></form>
<?php if($periodId>0 && $subjectId>0):?>
<form method="post" style="display:inline-block;margin:10px 10px 10px 0"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="action" value="download_template"><button>Tải mẫu XLSX</button></form>
<form method="post" style="display:inline-block;margin:10px 10px 10px 0"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="action" value="download_template_csv"><button>Tải mẫu CSV</button></form>
<form method="post" enctype="multipart/form-data" style="margin:10px 0"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="action" value="upload_preview"><input type="file" name="result_file" accept=".xlsx,.csv,.xls" required><button>Upload + Preview</button></form>
<?php endif;?>
<?php if(is_array($previewState) && (int)($previewState['period_id']??0)===$periodId && (int)($previewState['subject_id']??0)===$subjectId && (int)($previewState['user_id']??0)===$currentUserId):?>
<h2>Preview</h2><p>Hợp lệ: <?=count($previewState['valid']??[])?> — Lỗi: <?=count($previewState['errors']??[])?></p>
<?php if(!empty($previewState['errors'])):?><form method="post" style="display:inline-block"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="action" value="download_errors"><input type="hidden" name="format" value="xlsx"><button>Xuất lỗi XLSX</button></form> <form method="post" style="display:inline-block"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="action" value="download_errors"><input type="hidden" name="format" value="csv"><button>Xuất lỗi CSV</button></form><?php endif;?>
<?php if(!empty($previewState['valid'])):?><form method="post" style="margin:10px 0"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="action" value="confirm_import"><button>Xác nhận lưu các dòng hợp lệ</button></form><?php endif;?>
<?php endif;?>
<?php if($rows):?><table border="1" cellpadding="6"><tr><th>Đơn</th><th>Thí sinh</th><th>Môn</th><th>Số phách</th><th>Điểm cũ</th><th>Phân công</th><th>Trạng thái</th><th>Thao tác</th></tr>
<?php foreach($rows as $r):?><tr><td>#<?=e((string)$r['id'])?></td><td><?=e($r['candidate_code'].' - '.$r['full_name'])?></td><td><?=e($r['subject_code'].' - '.$r['subject_name'])?></td><td><?=e((string)$r['anonymous_code'])?></td><td><?=e((string)$r['original_score'])?></td><td>
<?php if($r['assigned_grader_name']):?><?=e($r['assigned_grader_name'].' ('.($r['assigned_grader_username']??'').')')?><?php else:?><form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="appeal_id" value="<?=$r['id']?>"><input type="hidden" name="action" value="assign"><select name="council_member_id" required><option value="">Chọn cán bộ</option><?php foreach(appeal_grader_candidates($pdo,(int)$r['id']) as $m):?><option value="<?=$m['council_member_id']?>"><?=e($m['full_name'].' ('.$m['username'].') — '.$m['council_name'])?></option><?php endforeach;?></select><button>Phân công</button></form><?php endif;?></td><td><?=e(appeal_status_label($r['status']))?></td><td>
<?php if($r['status']==='pending' && $r['assigned_grader_user_id']):?><form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="appeal_id" value="<?=$r['id']?>"><input type="hidden" name="action" value="start_grading"><button>Chuyển sang Đang chấm</button></form>
<?php elseif($r['status']==='grading' && (int)$r['assigned_grader_user_id']===$currentUserId):?><form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="appeal_id" value="<?=$r['id']?>"><input type="hidden" name="action" value="complete"><input name="new_score" type="number" min="0" max="<?=e((string)$r['max_score'])?>" step="0.01" value="<?=e((string)$r['original_score'])?>" required><input name="reason" placeholder="Lý do/biên bản" required><button>Xác nhận kết quả</button></form>
<?php elseif($r['status']==='completed'):?>Đã hoàn tất: <?=e((string)$r['new_score'])?><?php else:?>Chỉ cán bộ được phân công mới được cập nhật<?php endif;?></td></tr><?php endforeach;?></table><?php elseif($periodId):?><p>Không có đơn phúc khảo phù hợp.</p><?php endif;?>
</body></html>
