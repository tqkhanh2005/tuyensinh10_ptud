<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/admission_service.php';
require_once __DIR__.'/../includes/candidate_service.php';
require_permission('enrollment.confirm');
$candidate=candidate_find_by_user((int)auth_user()['id']);
if(!$candidate){http_response_code(404);exit('Không tìm thấy hồ sơ.');}
foreach(['candidate_id','id'] as $key)if(isset($_GET[$key]) && (int)$_GET[$key] !== (int)$candidate['id']){http_response_code(403);exit('Không có quyền truy cập hồ sơ khác.');}
$period=(int)($_GET['period_id']??0);$ctx=enrollment_context(db(),(int)$candidate['id'],$period);
if($ctx['error'] || !$ctx['confirmation']){http_response_code(404);exit('Chưa có biên nhận hợp lệ cho kỳ tuyển sinh này.');}
$r=$ctx['result'];$receipt=$ctx['confirmation'];$simulation=($ctx['configuration']['settings']['mode']??'simulation')==='simulation';
header('Cache-Control: private, no-store');
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Biên nhận xác nhận nhập học</title><style>body{font:16px/1.6 Arial,sans-serif;color:#172b4d;margin:2rem auto;max-width:800px;padding:1rem}h1{font-size:24px}dt{font-weight:bold}dd{margin:0 0 12px;overflow-wrap:anywhere}.notice{border:1px solid #aa7700;padding:12px}button,a{margin-right:16px}@media print{@page{size:A4;margin:20mm}.actions{display:none}body{margin:0;padding:0;max-width:none}}</style></head><body>
<div class="actions"><button onclick="window.print()">In / lưu PDF</button><a href="confirm.php?period_id=<?=$period?>">Quay lại</a></div>
<h1>BIÊN NHẬN XÁC NHẬN NHẬP HỌC</h1>
<?php if($simulation):?><p class="notice"><strong>MÔ PHỎNG — Không có giá trị xác nhận tuyển sinh chính thức.</strong></p><?php endif;?>
<p>Tuyển sinh lớp 10 tại TP.HCM — <?=e($ctx['period']['name'])?> — <?=e($ctx['period']['academic_year'])?></p>
<dl><dt>Họ và tên</dt><dd><?=e($r['full_name'])?></dd><dt>Ngày sinh</dt><dd><?=e($r['date_of_birth'])?></dd><dt>Số báo danh</dt><dd><?=e($r['exam_number']??'Chưa cấp')?></dd><dt>Trường trúng tuyển</dt><dd><?=e($r['school_name'])?><br><?=e($r['school_address']??'')?></dd><dt>Nguyện vọng</dt><dd><?=e((string)$r['priority_order'])?></dd><dt>Thời điểm xác nhận (giờ Việt Nam)</dt><dd><?=e($receipt['confirmed_at'])?></dd><dt>Mã biên nhận</dt><dd><?=e($receipt['receipt_code']??'Bản ghi trước khi cấp mã biên nhận')?></dd></dl>
<p>Hệ thống đã ghi nhận xác nhận nhập học cho hồ sơ và kỳ tuyển sinh nêu trên. Lưu biên nhận để tra cứu khi cần.</p></body></html>
