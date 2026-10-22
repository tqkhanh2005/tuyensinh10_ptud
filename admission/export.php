<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/admission_service.php';
require_permission('admission.manage');$pdo=db();$run=(int)($_GET['run_id'] ?? 0);
$q=$pdo->prepare('SELECT admission_period_id,status FROM admission_runs WHERE id=?');$q->execute([$run]);$r=$q->fetch();
if(!$r){http_response_code(404);exit('Không tìm thấy lượt xét tuyển.');}
$snapshot=selection_schema($pdo)?selection_snapshot($pdo,$run):null;
if(!$snapshot || $r['status']==='blocked'){http_response_code(409);exit('Chưa có kết quả đầy đủ cho lượt này.');}
header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="ket-qua-xet-tuyen-'.$run.'.csv"');header('Cache-Control: no-store');
$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['SBD','Họ tên','Nguyện vọng','Trường','Điểm thi','Điểm cộng','Điểm xét tuyển','Kết quả','Lý do','Chế độ'],',','"','');
foreach(admission_results($pdo,$run) as $row){$c=$snapshot['report']['candidates'][(int)$row['candidate_id']];$cells=[$c['exam_number'],$row['full_name'],$row['priority_order'],$row['school_name'],$row['score_total'],$row['priority_points'],$row['admission_score'],['admitted'=>'Trúng tuyển','not_admitted'=>'Không trúng tuyển','unprocessed'=>'Không đủ điều kiện'][$row['result_status']],implode(' ',$c['reasons']),$snapshot['inputs']['configuration']['mode']==='simulation'?'MÔ PHỎNG':'Theo cấu hình kỳ'];$cells=array_map(static fn($v)=>preg_match('/^\s*[=+@-]/u',(string)$v)?"'".$v:(string)$v,$cells);fputcsv($out,$cells,',','"','');}fclose($out);
