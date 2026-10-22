<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/exam_service.php';
require_permission('exam.manage');
$pdo=db();$period=(int)($_GET['period_id'] ?? 0);
$q=$pdo->prepare('SELECT name FROM admission_periods WHERE id=?');$q->execute([$period]);
if(!$q->fetchColumn()){http_response_code(404);exit('Không tìm thấy kỳ tuyển sinh.');}
$rows=exam_roster($pdo,$period);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="danh-sach-phong-thi-'.$period.'.csv"');
header('Cache-Control: no-store');
$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
fputcsv($out,['SBD','Họ tên','Ngày sinh','Điểm thi','Địa chỉ','Phòng','Chế độ'],',','"','');
$cfg=period_configuration($pdo,$period);$mode=$cfg && $cfg['settings']['mode']==='simulation'?'MÔ PHỎNG':'Theo kế hoạch của kỳ';
foreach($rows as $r){$cells=[$r['exam_number'] ?? '',$r['full_name'],$r['date_of_birth'],$r['exam_site_name'],$r['exam_site_address'],$r['exam_room_name'],$mode];$cells=array_map(static fn($v)=>preg_match('/^[\s]*[=+@-]/u',(string)$v)?"'".$v:(string)$v,$cells);fputcsv($out,$cells,',','"','');}fclose($out);
