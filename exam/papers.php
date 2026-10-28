<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_once __DIR__ . '/../includes/excel_service.php';
require_permission('paper.manage');
/**
 * Xuất .xlsx với MỌI ô là chuỗi văn bản (inlineStr) để Excel không bỏ số 0 đầu của số phách (vd 077804).
 */
function ph_xlsx_text_download(string $filename, array $headers, array $rows): void
{
    $col = static function (int $i): string { $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s; return $s; };
    $cell = static function (int $c, int $r, string $v) use ($col): string {
        return '<c r="' . $col($c) . $r . '" t="inlineStr"><is><t xml:space="preserve">' . htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
    };
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="4" width="18" customWidth="1"/></cols><sheetData>';
    $all = array_merge([$headers], $rows);
    foreach ($all as $ri => $row) {
        $xml .= '<row r="' . ($ri + 1) . '">';
        foreach (array_values($row) as $ci => $v) $xml .= $cell($ci, $ri + 1, (string)($v ?? ''));
        $xml .= '</row>';
    }
    $xml .= '</sheetData></worksheet>';
    $tmp = tempnam(sys_get_temp_dir(), 'phx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Danh sách phách" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
    $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
    $zip->close();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

$pdo = db();

/* ======================================================================
 * 1. NGỮ CẢNH ĐÃ CHỌN: Kỳ -> Địa điểm -> Phòng -> Môn
 * ==================================================================== */
$periods = $pdo->query('SELECT id,name FROM admission_periods ORDER BY id DESC')->fetchAll();
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
if (!in_array($periodId, array_map('intval', array_column($periods, 'id')), true)) {
    $periodId = (int)($periods[0]['id'] ?? 0);
}

$sites = exam_sites($pdo, $periodId);
$siteMap = [];
foreach ($sites as $s) $siteMap[(int)$s['id']] = $s;
$siteId = (int)($_GET['site_id'] ?? $_POST['site_id'] ?? 0);
if (!isset($siteMap[$siteId])) $siteId = 0;

$rooms = $siteId ? exam_rooms($pdo, $siteId) : [];
$roomMap = [];
foreach ($rooms as $r) $roomMap[(int)$r['id']] = $r;
$roomId = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
if (!isset($roomMap[$roomId])) $roomId = 0;

$sq = $pdo->prepare('SELECT id,name,subject_code,exam_date,start_time,end_time
                     FROM exam_subjects WHERE admission_period_id=? ORDER BY exam_date,start_time,id');
$sq->execute([$periodId]);
$subjects = $sq->fetchAll();
$subjectMap = [];
foreach ($subjects as $s) $subjectMap[(int)$s['id']] = $s;
$subjectId = (int)($_GET['subject_id'] ?? $_POST['subject_id'] ?? 0);
if (!isset($subjectMap[$subjectId])) $subjectId = 0;

/** Tạo URL của trang với ngữ cảnh hiện tại; $o ghi đè/bỏ bớt tham số (đặt 0 để bỏ). */
$ph_url = function (array $o = []) use (&$periodId, &$siteId, &$roomId, &$subjectId): string {
    $q = ['period_id' => $periodId, 'site_id' => $siteId, 'room_id' => $roomId, 'subject_id' => $subjectId];
    foreach ($o as $k => $v) $q[$k] = $v;
    $q = array_filter($q, static fn($v) => $v !== 0 && $v !== '' && $v !== null);
    return url('/exam/papers.php?' . http_build_query($q));
};

/* ======================================================================
 * 2. XUẤT EXCEL (bước 18.1) - chỉ các bài thi đã có số phách
 * ==================================================================== */
if (($_GET['export'] ?? '') === '1' && $siteId && $roomId && $subjectId) {
    $out = [];
    foreach (paper_rows($pdo, $periodId, $subjectId, $roomId) as $r) {
        if (!empty($r['anonymous_code'])) {
            $out[] = [$r['candidate_code'], $r['anonymous_code'], $r['attendance_status'], $r['penalty_percent']];
        }
    }
    ph_xlsx_text_download('danh_sach_phach.xlsx', ['SBD', 'Số phách', 'Trạng thái', 'Mức trừ (%)'], $out);
}

/* ======================================================================
 * 3. XỬ LÝ POST (thành công thì chuyển hướng sang bước kế tiếp)
 * ==================================================================== */
$error = '';
$codePreview = null;
$previewMode = '';
$flashKey = (string)($_GET['ok'] ?? '');
$flashN = (int)($_GET['n'] ?? 0);
$go = function (array $o) use ($ph_url): void {
    header('Location: ' . $ph_url($o));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        if (!$siteId || !$roomId || !$subjectId) {
            throw new InvalidArgumentException('Chưa chọn đủ địa điểm thi, phòng thi và môn thi.');
        }
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'generate_papers') {
            $n = generate_exam_papers($pdo, $periodId, $subjectId, $roomId);
            $go(['step' => 4, 'ok' => 'generated', 'n' => $n]);
        } elseif ($action === 'attendance') {
            save_attendance($pdo, $periodId, $subjectId, $roomId, (array)($_POST['attendance'] ?? []));
            $go(['step' => 5, 'ok' => 'attendance']);
        } elseif ($action === 'random_preview') {
            $codePreview = preview_random_codes($pdo, $periodId, $subjectId, $roomId);
            $previewMode = 'random';
            $_GET['step'] = 5;
        } elseif ($action === 'manual_preview') {
            $codePreview = [];
            $seen = [];
            foreach ((array)($_POST['codes'] ?? []) as $pid => $code) {
                $code = trim((string)$code);
                if ($code === '' || isset($seen[$code])) {
                    throw new InvalidArgumentException('Số phách bị trống hoặc bị trùng. Vui lòng nhập lại số phách khác.');
                }
                $seen[$code] = true;
                $codePreview[(int)$pid] = $code;
            }
            $previewMode = 'manual';
            $_GET['step'] = 5;
        } elseif ($action === 'random_save' || $action === 'manual_save') {
            save_codes($pdo, $periodId, $subjectId, $roomId, (array)($_POST['codes'] ?? []));
            $go(['step' => 6, 'ok' => 'codes']);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $codePreview = null;
    }
}

/* ======================================================================
 * 4. DỮ LIỆU BÀI THI CỦA PHÒNG/MÔN ĐANG CHỌN
 * ==================================================================== */
$rows = [];
if ($siteId && $roomId && $subjectId) {
    try {
        $rows = paper_rows($pdo, $periodId, $subjectId, $roomId);
        if (!$rows) {
            // Hệ thống tự lập danh sách bài thi từ danh sách thí sinh của phòng (cán bộ không cần thao tác)
            generate_exam_papers($pdo, $periodId, $subjectId, $roomId);
            $rows = paper_rows($pdo, $periodId, $subjectId, $roomId);
        }
    } catch (Throwable $e) {
        $error = $error ?: $e->getMessage();
    }
}
$allowedStatus = ['present', 'present_penalty', 'absent'];
$cnt = ['present' => 0, 'present_penalty' => 0, 'absent' => 0, 'pending' => 0];
$coded = 0;
$eligible = [];
$eligibleCoded = 0;
$sbd = [];
foreach ($rows as $r) {
    $st = (string)($r['attendance_status'] ?? '');
    if (in_array($st, $allowedStatus, true)) $cnt[$st]++; else $cnt['pending']++;
    if (!empty($r['anonymous_code'])) $coded++;
    if (in_array($st, ['present', 'present_penalty'], true)) {
        $eligible[] = $r;
        if (!empty($r['anonymous_code'])) $eligibleCoded++;
    }
    $sbd[(int)$r['id']] = (string)$r['candidate_code'];
}
$total = count($rows);
$phachDone = count($eligible) > 0 && $eligibleCoded === count($eligible);
// Đã có số phách trong phòng/môn này => chốt cứng toàn bộ điểm danh/xử phạt (kể cả thí sinh vắng)
$attLocked = $coded > 0;

/* ======================================================================
 * 5. XÁC ĐỊNH BƯỚC ĐANG HIỂN THỊ
 * ==================================================================== */
$maxStep = !$siteId ? 1 : (!$roomId ? 2 : (!$subjectId ? 3 : 6));
$step = (int)($_GET['step'] ?? $_POST['step'] ?? 0);
if ($step < 1) $step = min($maxStep, 4);
if ($step > $maxStep) $step = $maxStep;
if ($step >= 5 && $total === 0) $step = 4;
if ($step === 6 && $eligibleCoded === 0) $step = 5;

$stepNames = [1 => 'Địa điểm thi', 2 => 'Phòng thi', 3 => 'Môn thi', 4 => 'Điểm danh & xử phạt', 5 => 'Phát sinh phách', 6 => 'Xác nhận & lưu'];
$siteName = $siteId ? (string)$siteMap[$siteId]['name'] : '';
$roomName = $roomId ? (string)$roomMap[$roomId]['name'] : '';
$subjectName = $subjectId ? (string)$subjectMap[$subjectId]['name'] : '';
$periodName = '';
foreach ($periods as $p) if ((int)$p['id'] === $periodId) $periodName = trim(str_replace('[MÔ PHỎNG]', '', (string)$p['name']));
$statusLabel = ['present' => 'Có mặt', 'present_penalty' => 'Trừ điểm', 'absent' => 'Vắng mặt'];
$flashText = [
    'generated' => 'Đã tạo/đồng bộ ' . $flashN . ' bài thi từ danh sách phòng thi.',
    'attendance' => 'Đã lưu trạng thái điểm danh/xử phạt vào cơ sở dữ liệu.',
    'codes' => 'Đã lưu bảng ánh xạ số báo danh - số phách và cập nhật trạng thái "Đã đánh phách".',
][$flashKey] ?? '';

require_once __DIR__ . '/../includes/workflow_layout.php';
workflow_layout_start('Quản lý phách', 'exam');
?>
<style>
.ph-app{--navy:#141F35;--navy2:#1D2B49;--line:#DBDFE2;--line2:#B9C0C7;--ink:#161B22;--soft:#5B6572;--acc:#0E7C86;--acc2:#095A62;--ok:#1B7A43;--okbg:#E7F4EC;--warn:#B4740E;--warnbg:#FBF0DE;--bad:#B23A34;--badbg:#FBEAE9;border:1px solid var(--line);background:#fff;color:var(--ink);font-size:14.5px;line-height:1.5}
.ph-app *{box-sizing:border-box}
.ph-mono{font-family:ui-monospace,Consolas,"IBM Plex Mono",monospace;font-weight:600;letter-spacing:.02em}
.ph-top{background:var(--navy);color:#EDEFF3;padding:16px 28px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.ph-top .mark{font-family:ui-monospace,Consolas,monospace;font-size:12px;background:var(--acc);color:#0A1220;padding:2px 7px;border-radius:3px;margin-right:10px}
.ph-top h2{display:inline;font-size:17px;font-weight:700;margin:0;color:#fff}
.ph-top select{max-width:340px;font-size:13px;padding:4px 8px;border-radius:3px;border:1px solid #3a4a6b;background:#1D2B49;color:#EDEFF3}
.ph-steps{display:flex;border-bottom:1px solid var(--line);background:#FCFCFB;overflow-x:auto}
.ph-tab{flex:1 1 0;min-width:140px;padding:12px 16px 10px;border-right:1px solid var(--line);position:relative;color:var(--soft);text-decoration:none;display:block}
.ph-tab:last-child{border-right:none}
.ph-tab .n{display:block;font-family:ui-monospace,Consolas,monospace;font-size:11px}
.ph-tab .l{font-weight:700;font-size:13px}
.ph-tab.done .n,.ph-tab.active .n{color:var(--acc)}
.ph-tab.done .l,.ph-tab.active .l{color:var(--ink)}
.ph-tab.active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:var(--acc)}
.ph-tab.done::before{content:"✓";position:absolute;top:12px;right:14px;color:var(--acc);font-size:12px;font-weight:700}
.ph-tab.locked{opacity:.5;cursor:not-allowed}
a.ph-tab.done:hover{background:#F4F8F8}
.ph-body{padding:24px 28px 12px}
.ph-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--line)}
.ph-head h3{font-size:19px;margin:0 0 3px}
.ph-head p{margin:0;color:var(--soft);font-size:13px}
.ph-crumb{font-family:ui-monospace,Consolas,monospace;font-size:12px;color:var(--acc2);background:#EAF4F4;padding:4px 9px;border-radius:3px}
.ph-alert{padding:10px 14px;margin-bottom:14px;border-left:3px solid;font-size:13.5px}
.ph-alert.ok{background:var(--okbg);border-color:var(--ok);color:var(--ok)}
.ph-alert.bad{background:var(--badbg);border-color:var(--bad);color:var(--bad)}
.ph-alert.warn{background:var(--warnbg);border-color:var(--warn);color:var(--warn)}
.ph-list{display:grid;gap:10px}
a.ph-row{display:flex;align-items:center;gap:14px;padding:13px 16px;border:1px solid var(--line);background:#FDFDFC;color:var(--ink);text-decoration:none}
a.ph-row:hover{border-color:var(--acc);background:#F6FAFA}
a.ph-row.sel{border-color:var(--acc);background:#F1F8F8;box-shadow:inset 3px 0 0 var(--acc)}
.ph-row .dot{width:16px;height:16px;border-radius:50%;border:1.5px solid var(--line2);flex:0 0 auto}
.ph-row.sel .dot{border-color:var(--acc);box-shadow:inset 0 0 0 3px #fff;background:var(--acc)}
.ph-row .info{flex:1}
.ph-row .info b{display:block;font-weight:600}
.ph-row .info span{color:var(--soft);font-size:12.5px}
.ph-row .stat{font-family:ui-monospace,Consolas,monospace;font-size:12px;color:var(--soft);text-align:right;white-space:nowrap}
.ph-row .stat b{color:var(--ink)}
.ph-wrap{border:1px solid var(--line);overflow-x:auto}
table.ph-tbl{width:100%;border-collapse:collapse;font-size:13.5px}
.ph-tbl th{text-align:left;font-size:12px;color:var(--soft);background:#F2F3F1;padding:9px 12px;border-bottom:1px solid var(--line);border-right:1px solid var(--line)}
.ph-tbl td{padding:8px 12px;border-bottom:1px solid var(--line);border-right:1px solid var(--line);vertical-align:middle}
.ph-tbl th:last-child,.ph-tbl td:last-child{border-right:none}
.ph-tbl tr:last-child td{border-bottom:none}
.ph-tbl tbody tr:nth-child(even){background:#FBFBFA}
.ph-stg{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.ph-st{position:relative;margin:0;cursor:pointer}
.ph-st input{position:absolute;opacity:0;pointer-events:none}
.ph-st span{display:inline-block;font-size:12px;padding:5px 9px;border:1px solid var(--line2);background:#fff;color:var(--soft)}
.ph-st.p input:checked+span{background:var(--okbg);border-color:var(--ok);color:var(--ok)}
.ph-st.t input:checked+span{background:var(--warnbg);border-color:var(--warn);color:var(--warn)}
.ph-st.a input:checked+span{background:var(--badbg);border-color:var(--bad);color:var(--bad)}
.ph-pen{display:none;font-size:12px;padding:4px 6px;border:1px solid var(--line2)}
.ph-badge{display:inline-block;font-size:11px;font-family:ui-monospace,Consolas,monospace;padding:2px 7px;border-radius:3px}
.ph-badge.present{background:var(--okbg);color:var(--ok)}
.ph-badge.present_penalty{background:var(--warnbg);color:var(--warn)}
.ph-badge.absent{background:var(--badbg);color:var(--bad)}
.ph-tally{display:flex;margin-top:14px;border:1px solid var(--line)}
.ph-tally div{flex:1;padding:9px 14px;border-right:1px solid var(--line)}
.ph-tally div:last-child{border-right:none}
.ph-tally .n{display:block;font-family:ui-monospace,Consolas,monospace;font-size:19px;font-weight:600}
.ph-tally .l{font-size:11.5px;color:var(--soft)}
.ph-hint{font-size:12.5px;color:var(--soft);margin:8px 0 0}
.ph-hint.bad{color:var(--bad)}
.ph-actions{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:16px 0 8px;flex-wrap:wrap}
.ph-actions .r{display:flex;gap:10px;flex-wrap:wrap}
.ph-modal{position:fixed;inset:0;background:rgba(20,31,53,.55);z-index:2000;display:flex;align-items:center;justify-content:center;padding:16px}
.ph-modal[hidden]{display:none}
.ph-modal-box{background:#fff;border:1px solid var(--line);max-width:620px;width:100%;max-height:90vh;overflow:auto;padding:20px}
.ph-modal-box h4{margin:0 0 6px;font-size:17px}
.ph-btn{font-size:13.5px;font-weight:600;padding:8px 16px;border:1px solid var(--navy);background:var(--navy);color:#fff;cursor:pointer;text-decoration:none;display:inline-block}
.ph-btn:hover{background:var(--navy2);color:#fff}
.ph-btn.sec{background:#fff;color:var(--ink);border-color:var(--line2)}
.ph-btn.sec:hover{border-color:var(--soft);background:#fff;color:var(--ink)}
.ph-btn.acc{background:var(--acc);border-color:var(--acc)}
.ph-btn.acc:hover{background:var(--acc2)}
.ph-btn.dng{background:transparent;border-color:transparent;color:var(--bad);text-decoration:underline}
.ph-btn[disabled],.ph-btn.off{background:#C7CBD1;border-color:#C7CBD1;color:#fff;cursor:not-allowed;pointer-events:none}
.ph-toggle{display:inline-flex;border:1px solid var(--line2);margin-bottom:14px}
.ph-toggle button{font-size:13px;font-weight:500;padding:7px 16px;border:none;background:#fff;color:var(--soft);cursor:pointer;border-right:1px solid var(--line2)}
.ph-toggle button:last-child{border-right:none}
.ph-toggle button.on{background:var(--navy);color:#fff}
.ph-mode-random .ph-manual-only{display:none}
.ph-mode-manual .ph-random-only{display:none}
.ph-input{width:130px;font-family:ui-monospace,Consolas,monospace;font-size:13px;padding:4px 7px;border:1px solid var(--line2)}
.ph-sum{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.ph-card{border:1px solid var(--line);padding:14px 16px}
.ph-card h4{margin:0 0 8px;font-size:13px;color:var(--soft)}
.ph-card .r{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px dashed var(--line);font-size:13px}
.ph-card .r:last-child{border-bottom:none}
.ph-foot{border-top:1px solid var(--line);padding:12px 28px;display:flex;justify-content:space-between;align-items:center;background:#FCFCFB}
.ph-foot .cap{font-family:ui-monospace,Consolas,monospace;font-size:12px;color:var(--soft)}
@media(max-width:720px){.ph-sum{grid-template-columns:1fr}.ph-body{padding:18px 14px}.ph-tally{flex-wrap:wrap}}
</style>

<div class="ph-app">

  <div class="ph-top">
    <div><span class="mark">PHÁCH</span><h2>Đánh phách bài thi</h2></div>
    <form method="get">
      <select name="period_id" onchange="this.form.submit()" aria-label="Kỳ tuyển sinh">
        <?php foreach ($periods as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $periodId ? 'selected' : '' ?>><?= e(trim(str_replace('[MÔ PHỎNG]', '', (string)$p['name']))) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div class="ph-steps">
    <?php foreach ($stepNames as $n => $label):
        $cls = $n === $step ? 'active' : ($n < $step ? 'done' : 'locked'); ?>
      <?php if ($n < $step): ?>
        <a class="ph-tab <?= $cls ?>" href="<?= e($ph_url(['step' => $n, 'ok' => 0, 'n' => 0])) ?>"><span class="n"><?= sprintf('%02d', $n) ?></span><span class="l"><?= e($label) ?></span></a>
      <?php else: ?>
        <div class="ph-tab <?= $cls ?>"><span class="n"><?= sprintf('%02d', $n) ?></span><span class="l"><?= e($label) ?></span></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div class="ph-body">
    <?php if ($flashText): ?><div class="ph-alert ok"><?= e($flashText) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="ph-alert bad"><?= e($error) ?></div><?php endif; ?>

<?php /* ================= BƯỚC 1: ĐỊA ĐIỂM THI ================= */ ?>
<?php if ($step === 1): ?>
    <div class="ph-head"><div><h3>Chọn địa điểm thi</h3><p>Chọn địa điểm để xem danh sách phòng thi cần đánh phách.</p></div></div>
    <?php if (!$sites): ?><div class="ph-alert warn">Kỳ này chưa có điểm thi. Hãy lập điểm thi trong mục Quản lý kỳ thi.</div><?php endif; ?>
    <div class="ph-list">
    <?php
      $roomCountQ = $pdo->prepare('SELECT COUNT(*) FROM exam_rooms WHERE exam_site_id=?');
      $candCountQ = $pdo->prepare('SELECT COUNT(*) FROM room_assignments WHERE admission_period_id=? AND exam_site_id=?');
      foreach ($sites as $s):
        $sid = (int)$s['id'];
        $roomCountQ->execute([$sid]); $rc = (int)$roomCountQ->fetchColumn();
        $candCountQ->execute([$periodId, $sid]); $cc = (int)$candCountQ->fetchColumn();
    ?>
      <a class="ph-row <?= $sid === $siteId ? 'sel' : '' ?>" href="<?= e($ph_url(['step' => 2, 'site_id' => $sid, 'room_id' => 0, 'subject_id' => 0, 'ok' => 0, 'n' => 0])) ?>">
        <span class="dot"></span>
        <span class="info"><b><?= e((string)$s['name']) ?></b><span><?= e((string)($s['address'] ?? ($s['site_code'] ?? ''))) ?></span></span>
        <span class="stat">Phòng thi: <b><?= $rc ?></b><br>Thí sinh: <b><?= $cc ?></b></span>
      </a>
    <?php endforeach; ?>
    </div>

<?php /* ================= BƯỚC 2: PHÒNG THI ================= */ ?>
<?php elseif ($step === 2): ?>
    <div class="ph-head"><div><h3>Chọn phòng thi</h3><p>Danh sách phòng thi thuộc địa điểm đã chọn.</p></div><div class="ph-crumb"><?= e($siteName) ?></div></div>
    <?php if (!$rooms): ?><div class="ph-alert warn">Địa điểm này chưa có phòng thi.</div><?php endif; ?>
    <div class="ph-list">
    <?php
      $assignQ = $pdo->prepare('SELECT COUNT(*) FROM room_assignments WHERE admission_period_id=? AND exam_room_id=?');
      foreach ($rooms as $r):
        $rid = (int)$r['id'];
        $assignQ->execute([$periodId, $rid]); $ac = (int)$assignQ->fetchColumn();
    ?>
      <a class="ph-row <?= $rid === $roomId ? 'sel' : '' ?>" href="<?= e($ph_url(['step' => 3, 'room_id' => $rid, 'subject_id' => 0, 'ok' => 0, 'n' => 0])) ?>">
        <span class="dot"></span>
        <span class="info"><b><?= e((string)$r['name']) ?></b><span>Mã phòng: <?= e((string)$r['room_code']) ?></span></span>
        <span class="stat">Sức chứa: <b><?= (int)$r['capacity'] ?></b><br>Thí sinh: <b><?= $ac ?></b></span>
      </a>
    <?php endforeach; ?>
    </div>

<?php /* ================= BƯỚC 3: MÔN THI ================= */ ?>
<?php elseif ($step === 3): ?>
    <div class="ph-head"><div><h3>Chọn môn thi</h3><p>Danh sách môn thi của kỳ, kèm tiến độ đánh phách tại phòng đã chọn.</p></div><div class="ph-crumb"><?= e($siteName . ' · ' . $roomName) ?></div></div>
    <?php if (!$subjects): ?><div class="ph-alert warn">Kỳ này chưa có môn thi.</div><?php endif; ?>
    <?php
      $prog = [];
      $pq = $pdo->prepare('SELECT exam_subject_id, COUNT(*) AS total, SUM(anonymous_code IS NOT NULL) AS coded
                           FROM exam_papers WHERE admission_period_id=? AND exam_room_id=? GROUP BY exam_subject_id');
      $pq->execute([$periodId, $roomId]);
      foreach ($pq as $x) $prog[(int)$x['exam_subject_id']] = $x;
      // Môn chưa có bài thi: số bài thi dự kiến = số thí sinh được xếp vào phòng
      $roomCandQ = $pdo->prepare('SELECT COUNT(*) FROM room_assignments WHERE admission_period_id=? AND exam_room_id=?');
      $roomCandQ->execute([$periodId, $roomId]);
      $roomCand = (int)$roomCandQ->fetchColumn();
    ?>
    <div class="ph-list">
    <?php foreach ($subjects as $s):
        $sj = (int)$s['id'];
        $when = trim(($s['exam_date'] ? date('d/m/Y', strtotime((string)$s['exam_date'])) : '') . ' ' .
                ($s['start_time'] ? substr((string)$s['start_time'], 0, 5) : '') .
                ($s['end_time'] ? ' – ' . substr((string)$s['end_time'], 0, 5) : ''));
    ?>
      <a class="ph-row <?= $sj === $subjectId ? 'sel' : '' ?>" href="<?= e($ph_url(['step' => 4, 'subject_id' => $sj, 'ok' => 0, 'n' => 0])) ?>">
        <span class="dot"></span>
        <span class="info"><b><?= e((string)$s['name']) ?></b><span><?= e($when !== '' ? $when : 'Chưa có lịch thi') ?></span></span>
        <span class="stat">Bài thi: <b><?= (int)($prog[$sj]['total'] ?? $roomCand) ?></b><br>Đã đánh phách: <b><?= (int)($prog[$sj]['coded'] ?? 0) ?></b></span>
      </a>
    <?php endforeach; ?>
    </div>

<?php /* ================= BƯỚC 4: ĐIỂM DANH & XỬ PHẠT ================= */ ?>
<?php elseif ($step === 4): ?>
    <div class="ph-head"><div><h3>Điểm danh &amp; xử phạt</h3><p>Đối chiếu danh sách ký tên và biên bản xử phạt, cập nhật trạng thái cho từng thí sinh.</p></div><div class="ph-crumb"><?= e($siteName . ' · ' . $roomName . ' · ' . $subjectName) ?></div></div>

    <?php if ($attLocked): ?>
      <div class="ph-alert ok">Phòng/môn này đã đánh phách nên điểm danh và xử phạt đã được chốt, không thể thay đổi. Thí sinh vắng mặt có lý do chính đáng sẽ được xử lý bằng đợt thi bổ sung (ngoài chức năng này).</div>
    <?php endif; ?>
    <?php if (!$rows): ?>
      <div class="ph-alert warn">Phòng này chưa có thí sinh nào được xếp cho môn đã chọn nên chưa thể điểm danh. Vui lòng kiểm tra lại phân phòng thi.</div>
    <?php else: ?>
      <form method="post" id="ph-att-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="attendance">
        <input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="site_id" value="<?= $siteId ?>">
        <input type="hidden" name="room_id" value="<?= $roomId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="step" value="4">
        <div class="ph-wrap">
          <table class="ph-tbl">
            <thead><tr><th style="width:44px">STT</th><th style="width:120px">SBD</th><th>Họ và tên</th><th style="width:340px">Trạng thái</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r):
                $pid = (int)$r['id'];
                $st = (string)($r['attendance_status'] ?? '');
                $pen = (int)($r['penalty_percent'] ?? 0);
                $locked = $attLocked;
            ?>
              <tr data-att-row <?= $locked ? 'data-locked="' . e(in_array($st, $allowedStatus, true) ? $st : 'present') . '"' : '' ?>>
                <td><?= $i + 1 ?></td>
                <td class="ph-mono"><?= e((string)$r['candidate_code']) ?></td>
                <td><?= e((string)$r['full_name']) ?></td>
                <td>
                <?php if ($locked): ?>
                  <span class="ph-badge <?= e($st) ?>"><?= e($statusLabel[$st] ?? $st) ?><?= $st === 'present_penalty' ? ' ' . $pen . '%' : '' ?></span>
                  <span class="ph-hint">· <?= !empty($r['anonymous_code']) ? 'đã đánh phách, không sửa được' : 'đã chốt điểm danh, không sửa được' ?></span>
                  <input type="hidden" name="attendance[<?= $pid ?>][status]" value="<?= e($st) ?>">
                  <input type="hidden" name="attendance[<?= $pid ?>][penalty]" value="<?= $pen ?: '' ?>">
                <?php else: ?>
                  <div class="ph-stg">
                    <label class="ph-st p"><input type="radio" name="attendance[<?= $pid ?>][status]" value="present" <?= $st === 'present' ? 'checked' : '' ?>><span>Có mặt</span></label>
                    <label class="ph-st t"><input type="radio" name="attendance[<?= $pid ?>][status]" value="present_penalty" <?= $st === 'present_penalty' ? 'checked' : '' ?>><span>Có mặt - trừ điểm</span></label>
                    <select class="ph-pen" name="attendance[<?= $pid ?>][penalty]">
                      <option value="">Mức trừ</option>
                      <?php foreach ([25, 50, 100] as $pp): ?><option value="<?= $pp ?>" <?= $pen === $pp ? 'selected' : '' ?>><?= $pp ?>%</option><?php endforeach; ?>
                    </select>
                    <label class="ph-st a"><input type="radio" name="attendance[<?= $pid ?>][status]" value="absent" <?= $st === 'absent' ? 'checked' : '' ?>><span>Vắng mặt</span></label>
                  </div>
                <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="ph-tally">
          <div><span class="n" id="t-total"><?= $total ?></span><span class="l">Tổng số thí sinh</span></div>
          <div><span class="n" id="t-present"><?= $cnt['present'] ?></span><span class="l">Có mặt</span></div>
          <div><span class="n" id="t-penalty"><?= $cnt['present_penalty'] ?></span><span class="l">Bị trừ điểm</span></div>
          <div><span class="n" id="t-absent"><?= $cnt['absent'] ?></span><span class="l">Vắng mặt</span></div>
          <div><span class="n" id="t-pending"><?= $cnt['pending'] ?></span><span class="l">Chưa đánh dấu</span></div>
        </div>
        <p class="ph-hint" id="ph-att-hint">Cần đánh dấu trạng thái cho toàn bộ thí sinh trước khi lưu. Bấm lại nút đang chọn để bỏ chọn.</p>

        <div class="ph-actions">
          <a class="ph-btn dng" href="<?= e($ph_url(['step' => 3, 'subject_id' => 0, 'ok' => 0, 'n' => 0])) ?>" onclick="return confirm('Hủy thao tác điểm danh? Các lựa chọn chưa lưu sẽ không được giữ lại.')">Hủy</a>
          <div class="r">
            <?php if ($cnt['pending'] === 0): ?>
              <a class="ph-btn sec" href="<?= e($ph_url(['step' => 5, 'ok' => 0, 'n' => 0])) ?>">Đã điểm danh · sang bước phát sinh phách →</a>
            <?php endif; ?>
            <button class="ph-btn acc" id="ph-att-save" <?= $attLocked ? 'disabled' : '' ?>>Xác nhận lưu điểm danh</button>
          </div>
        </div>
      </form>

      <div class="ph-modal" id="ph-confirm" hidden>
        <div class="ph-modal-box">
          <h4>Xác nhận lưu điểm danh</h4>
          <p class="ph-hint" style="margin:0 0 10px"><?= e($siteName . ' · ' . $roomName . ' · ' . $subjectName) ?></p>
          <div class="ph-tally" style="margin-top:0">
            <div><span class="n" id="c-total">0</span><span class="l">Tổng số</span></div>
            <div><span class="n" id="c-present">0</span><span class="l">Có mặt</span></div>
            <div><span class="n" id="c-penalty">0</span><span class="l">Bị trừ điểm</span></div>
            <div><span class="n" id="c-absent">0</span><span class="l">Vắng mặt</span></div>
          </div>
          <div id="c-list" style="margin:12px 0"></div>
          <div class="ph-alert warn" style="margin-bottom:12px">Sau khi lưu và đánh phách, trạng thái điểm danh/xử phạt <b>không thể thay đổi</b>. Hãy đối chiếu lại danh sách ký tên và biên bản xử phạt, đặc biệt các thí sinh vắng mặt ở trên.</div>
          <div class="ph-actions" style="margin:0">
            <button type="button" class="ph-btn sec" id="c-back">← Quay lại kiểm tra</button>
            <div class="r"><button type="button" class="ph-btn acc" id="c-ok">Tôi đã đối chiếu · Xác nhận lưu</button></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

<?php /* ================= BƯỚC 5: PHÁT SINH PHÁCH ================= */ ?>
<?php elseif ($step === 5): ?>
    <div class="ph-head"><div><h3>Phát sinh phách</h3><p>Sinh số phách cho bài thi của thí sinh có mặt (không áp dụng cho thí sinh vắng mặt).</p></div><div class="ph-crumb"><?= count($eligible) ?> bài thi cần đánh phách</div></div>

    <?php if ($cnt['pending'] > 0): ?>
      <div class="ph-alert warn">Còn <?= $cnt['pending'] ?> thí sinh chưa được điểm danh. Vui lòng quay lại bước 4 để hoàn tất trước khi đánh phách.</div>
    <?php endif; ?>

    <?php if ($codePreview !== null): ?>
      <div class="ph-alert ok">Bảng ánh xạ xem trước (<?= count($codePreview) ?> bài thi). Dữ liệu CHƯA được lưu cho đến khi bạn bấm "Xác nhận lưu".</div>
      <div class="ph-wrap" style="margin-bottom:12px">
        <table class="ph-tbl">
          <thead><tr><th style="width:44px">STT</th><th>SBD</th><th>Số phách</th></tr></thead>
          <tbody>
          <?php $k = 0; foreach ($codePreview as $pid => $code): $k++; ?>
            <tr><td><?= $k ?></td><td class="ph-mono"><?= e($sbd[(int)$pid] ?? '') ?></td><td class="ph-mono"><?= e((string)$code) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <form method="post" class="ph-actions">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $previewMode === 'manual' ? 'manual_save' : 'random_save' ?>">
        <input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="site_id" value="<?= $siteId ?>">
        <input type="hidden" name="room_id" value="<?= $roomId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="step" value="5">
        <?php foreach ($codePreview as $pid => $code): ?><input type="hidden" name="codes[<?= (int)$pid ?>]" value="<?= e((string)$code) ?>"><?php endforeach; ?>
        <a class="ph-btn dng" href="<?= e($ph_url(['step' => 5, 'ok' => 0, 'n' => 0])) ?>" onclick="return confirm('Hủy bản xem trước? Số phách chưa lưu sẽ không được giữ lại (điểm danh/xử phạt vẫn được giữ).')">Hủy</a>
        <button class="ph-btn acc">Xác nhận lưu bảng ánh xạ</button>
      </form>
      <hr style="border-color:var(--line);margin:18px 0">
    <?php endif; ?>

    <?php if ($codePreview === null): /* đang xem trước thì ẩn phần chọn chế độ, chỉ còn Xác nhận lưu / Hủy */ ?>
    <?php if ($phachDone): ?>
      <div class="ph-alert ok">Toàn bộ bài thi có mặt đã được đánh phách. <a href="<?= e($ph_url(['step' => 6, 'ok' => 0, 'n' => 0])) ?>">Xem tổng kết →</a></div>
    <?php endif; ?>

    <div class="ph-toggle" id="ph-toggle">
      <button type="button" class="on" data-mode="random">Phát sinh ngẫu nhiên</button>
      <button type="button" data-mode="manual">Nhập phách thủ công</button>
    </div>

    <div id="ph-mode" class="ph-mode-random">
      <div class="ph-random-only" style="margin-bottom:12px">
        <form method="post" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="random_preview">
          <input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="site_id" value="<?= $siteId ?>">
          <input type="hidden" name="room_id" value="<?= $roomId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="step" value="5">
          <button class="ph-btn sec" <?= ($cnt['pending'] > 0 || $phachDone) ? 'disabled' : '' ?>>↻ Phát sinh phách ngẫu nhiên → xem trước</button>
        </form>
        <p class="ph-hint">Hệ thống loại thí sinh vắng mặt và sinh số phách duy nhất trong kỳ thi. Nếu trùng, hệ thống tự sinh lại.</p>
      </div>

      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="manual_preview">
        <input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="site_id" value="<?= $siteId ?>">
        <input type="hidden" name="room_id" value="<?= $roomId ?>"><input type="hidden" name="subject_id" value="<?= $subjectId ?>"><input type="hidden" name="step" value="5">
        <div class="ph-wrap">
          <table class="ph-tbl">
            <thead><tr><th style="width:44px">STT</th><th style="width:120px">SBD</th><th>Họ và tên</th><th style="width:130px">Trạng thái</th><th style="width:170px">Số phách</th></tr></thead>
            <tbody>
            <?php foreach ($eligible as $i => $r): $st = (string)$r['attendance_status']; ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td class="ph-mono"><?= e((string)$r['candidate_code']) ?></td>
                <td><?= e((string)$r['full_name']) ?></td>
                <td><span class="ph-badge <?= e($st) ?>"><?= e($statusLabel[$st] ?? $st) ?><?= $st === 'present_penalty' ? ' ' . (int)$r['penalty_percent'] . '%' : '' ?></span></td>
                <td>
                  <span class="ph-random-only ph-mono"><?= !empty($r['anonymous_code']) ? e((string)$r['anonymous_code']) : '<span class="ph-hint">Chưa sinh</span>' ?></span>
                  <input class="ph-manual-only ph-input" name="codes[<?= (int)$r['id'] ?>]" value="<?= e((string)($r['anonymous_code'] ?? '')) ?>" maxlength="30" placeholder="Nhập số phách" <?= !empty($r['anonymous_code']) ? 'readonly' : '' ?>>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$eligible): ?><tr><td colspan="5" class="ph-hint">Chưa có thí sinh có mặt để đánh phách.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="ph-actions ph-manual-only">
          <span class="ph-hint">Nhập số phách cho toàn bộ bài thi có mặt, rồi kiểm tra trùng trước khi lưu.</span>
          <button class="ph-btn" <?= ($cnt['pending'] > 0 || $phachDone) ? 'disabled' : '' ?>>Kiểm tra phách thủ công → xem trước</button>
        </div>
      </form>
    </div>
    <?php endif; ?>

<?php /* ================= BƯỚC 6: XÁC NHẬN & LƯU ================= */ ?>
<?php elseif ($step === 6): ?>
    <div class="ph-head"><div><h3>Xác nhận &amp; lưu</h3><p>Tổng kết trước khi hoàn tất đánh phách cho môn thi này.</p></div><div class="ph-crumb">Trạng thái bài thi: Đã đánh phách</div></div>
    <div class="ph-sum">
      <div class="ph-card">
        <h4>Tổng kết điểm danh</h4>
        <div class="r"><span>Tổng số thí sinh</span><b class="ph-mono"><?= $total ?></b></div>
        <div class="r"><span>Có mặt (bình thường)</span><b class="ph-mono"><?= $cnt['present'] ?></b></div>
        <div class="r"><span>Có mặt — bị trừ điểm</span><b class="ph-mono"><?= $cnt['present_penalty'] ?></b></div>
        <div class="r"><span>Vắng mặt</span><b class="ph-mono"><?= $cnt['absent'] ?></b></div>
      </div>
      <div class="ph-card">
        <h4>Tổng kết đánh phách</h4>
        <div class="r"><span>Bài thi đã gắn số phách</span><b class="ph-mono"><?= $eligibleCoded ?> / <?= count($eligible) ?></b></div>
        <div class="r"><span>Địa điểm</span><b><?= e($siteName) ?></b></div>
        <div class="r"><span>Phòng thi</span><b><?= e($roomName) ?></b></div>
        <div class="r"><span>Môn thi</span><b><?= e($subjectName) ?></b></div>
      </div>
    </div>

    <div class="ph-wrap">
      <table class="ph-tbl">
        <thead><tr><th style="width:44px">STT</th><th>SBD</th><th>Họ và tên</th><th>Trạng thái</th><th>Số phách</th></tr></thead>
        <tbody>
        <?php $k = 0; foreach ($eligible as $r): if (empty($r['anonymous_code'])) continue; $k++; $st = (string)$r['attendance_status']; ?>
          <tr><td><?= $k ?></td><td class="ph-mono"><?= e((string)$r['candidate_code']) ?></td><td><?= e((string)$r['full_name']) ?></td>
              <td><span class="ph-badge <?= e($st) ?>"><?= e($statusLabel[$st] ?? $st) ?></span></td><td class="ph-mono"><?= e((string)$r['anonymous_code']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="ph-actions" style="border-top:1px solid var(--line);padding-top:14px">
      <a class="ph-btn sec" href="<?= e($ph_url(['export' => 1, 'step' => 0, 'ok' => 0, 'n' => 0])) ?>">⇩ Xuất file Excel danh sách phách</a>
      <div class="r">
        <a class="ph-btn sec" href="<?= e($ph_url(['step' => 3, 'subject_id' => 0, 'ok' => 0, 'n' => 0])) ?>">Đánh phách môn khác</a>
        <a class="ph-btn acc" href="<?= e($ph_url(['step' => 1, 'site_id' => 0, 'room_id' => 0, 'subject_id' => 0, 'ok' => 0, 'n' => 0])) ?>">Hoàn tất</a>
      </div>
    </div>
<?php endif; ?>
  </div>

  <div class="ph-foot">
    <?php if ($step > 1): ?>
      <a class="ph-btn sec" href="<?= e($ph_url(['step' => $step - 1, 'ok' => 0, 'n' => 0])) ?>">← Quay lại</a>
    <?php else: ?><span></span><?php endif; ?>
    <span class="cap">Bước <?= $step ?> / 6 — <?= e($stepNames[$step]) ?></span>
    <span></span>
  </div>
</div>

<script>
(function () {
  /* Bước 4: bật/tắt ô mức trừ và đếm thí sinh chưa đánh dấu */
  var form = document.getElementById('ph-att-form');
  if (form) {
    var rows = form.querySelectorAll('[data-att-row]');
    var btn = document.getElementById('ph-att-save');
    var hint = document.getElementById('ph-att-hint');
    var lockedAll = btn && btn.hasAttribute('disabled');
    var set = function (id, v) { document.getElementById(id).textContent = v; };
    var refresh = function () {
      var c = {present: 0, present_penalty: 0, absent: 0, pending: 0};
      rows.forEach(function (r) {
        if (r.dataset.locked) { c[r.dataset.locked]++; return; }
        var chosen = r.querySelector('input[type=radio]:checked');
        var sel = r.querySelector('select');
        if (!chosen) { c.pending++; sel.style.display = 'none'; return; }
        var isPen = chosen.value === 'present_penalty';
        sel.style.display = isPen ? 'inline-block' : 'none';
        if (isPen && !sel.value) { c.pending++; return; }
        c[chosen.value]++;
      });
      set('t-present', c.present); set('t-penalty', c.present_penalty);
      set('t-absent', c.absent); set('t-pending', c.pending);
      if (c.pending > 0) {
        hint.textContent = 'Còn ' + c.pending + ' thí sinh chưa được đánh dấu (hoặc chưa chọn mức trừ).';
        hint.className = 'ph-hint bad';
        if (btn) btn.disabled = true;
      } else {
        hint.textContent = 'Đã đánh dấu trạng thái đầy đủ cho toàn bộ thí sinh.';
        hint.className = 'ph-hint';
        if (btn) btn.disabled = lockedAll;
      }
    };
    form.addEventListener('change', refresh);

    /* Màn hình tóm tắt + cảnh báo trước khi lưu điểm danh (bước 11) */
    var modal = document.getElementById('ph-confirm');
    var summary = function () {
      var c = {total: 0, present: 0, present_penalty: 0, absent: 0}, abs = [];
      rows.forEach(function (r) {
        var st = r.dataset.locked || (r.querySelector('input[type=radio]:checked') || {}).value;
        if (!st) return;
        c.total++; c[st]++;
        if (st === 'absent') abs.push(r.children[1].textContent.trim() + ' — ' + r.children[2].textContent.trim());
      });
      set('c-total', c.total); set('c-present', c.present); set('c-penalty', c.present_penalty); set('c-absent', c.absent);
      var box = document.getElementById('c-list');
      box.innerHTML = '';
      var p = document.createElement('div');
      p.className = 'ph-hint';
      p.textContent = abs.length ? 'Thí sinh vắng mặt (' + abs.length + '):' : 'Không có thí sinh vắng mặt.';
      box.appendChild(p);
      abs.forEach(function (t) { var d = document.createElement('div'); d.className = 'ph-mono'; d.textContent = t; box.appendChild(d); });
    };
    var confirmed = false;
    if (btn && modal) {
      form.addEventListener('submit', function (e) {
        if (confirmed) return;
        e.preventDefault();
        summary();
        modal.hidden = false;
      });
      document.getElementById('c-back').addEventListener('click', function () { modal.hidden = true; });
      document.getElementById('c-ok').addEventListener('click', function () {
        confirmed = true; modal.hidden = true;
        if (form.requestSubmit) form.requestSubmit(btn); else form.submit();
      });
    }
    /* Bấm lại nút đang chọn để bỏ chọn (trả thí sinh về trạng thái chưa đánh dấu) */
    var last = {};
    form.querySelectorAll('input[type=radio]:checked').forEach(function (x) { last[x.name] = x.value; });
    form.addEventListener('click', function (e) {
      var x = e.target;
      if (!x || x.type !== 'radio') return;
      if (last[x.name] === x.value) {
        x.checked = false;
        last[x.name] = null;
        var row = x.closest('[data-att-row]');
        var sel = row && row.querySelector('select');
        if (sel) sel.value = '';
        refresh();
      } else {
        last[x.name] = x.value;
      }
    });
    refresh();
  }

  /* Bước 5: chuyển chế độ ngẫu nhiên / thủ công */
  var tg = document.getElementById('ph-toggle');
  if (tg) {
    var wrap = document.getElementById('ph-mode');
    tg.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-mode]');
      if (!b) return;
      tg.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      wrap.className = 'ph-mode-' + b.dataset.mode;
    });
  }
})();
</script>
<?php workflow_layout_end(); ?>