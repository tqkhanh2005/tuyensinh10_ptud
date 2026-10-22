<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
require_once __DIR__.'/../includes/handover_service.php';
$options=getopt('',['backup:','workflow-report:','portal-report:','report:']);
$pdo=db();$before=cleanup_fingerprints(cleanup_snapshot($pdo));$checks=[];
$check=static function(bool $ok,string $label)use(&$checks){$checks[]=['ok'=>$ok,'label'=>$label];echo ($ok?'PASS: ':'FAIL: '),$label,PHP_EOL;};
foreach(['pdo_mysql','mbstring','zip','curl','SimpleXML','fileinfo'] as $extension)$check(extension_loaded($extension),'PHP extension '.$extension);
$check(version_compare(PHP_VERSION,'8.1','>='),'PHP 8.1 or later');
foreach(['admission_period_configs','admission_period_events','exam_candidate_numbers','exam_organization_states','exam_grading_states','admission_run_snapshots','admission_tie_resolutions','enrollment_confirmations'] as $table)$check(array_key_exists($table,$before),'Schema '.$table);
$indexes=array_column($pdo->query('SHOW INDEX FROM enrollment_confirmations')->fetchAll(),'Key_name');$check(in_array('uq_enrollment_period_candidate',$indexes,true) && in_array('uq_enrollment_receipt',$indexes,true),'Enrollment is unique by period and receipt');
$check((int)$pdo->query('SELECT COUNT(*) FROM enrollment_confirmations ec JOIN admission_results ar ON ar.id=ec.admission_result_id WHERE ec.admission_period_id IS NULL OR ec.admission_period_id<>ar.admission_period_id OR ec.candidate_id<>ar.candidate_id OR ec.school_id<>ar.school_id OR ar.enrollment_status<>"confirmed"')->fetchColumn()===0,'Confirmation records match their results');
$check((int)$pdo->query('SELECT COUNT(*) FROM (SELECT ar.admission_period_id,ar.candidate_id FROM admission_results ar WHERE ar.result_status="admitted" AND ar.admission_run_id=(SELECT MAX(r.id) FROM admission_runs r WHERE r.admission_period_id=ar.admission_period_id AND r.status="finalized") GROUP BY ar.admission_period_id,ar.candidate_id HAVING COUNT(*)>1) bad')->fetchColumn()===0,'One admission per candidate in each finalized period');
$triggers=$pdo->query('SHOW TRIGGERS')->fetchAll();$check(count(array_filter($triggers,static fn($r)=>$r['Table']==='scores'))>=3,'Published score protection triggers present');
$hash=handover_code_hash();
try{
    $backup=$options['backup']??'';$meta=json_decode((string)file_get_contents($backup.'.json'),true,512,JSON_THROW_ON_ERROR);
    $check(!empty($meta['restore_verified']) && is_file($backup) && hash_equals($meta['sha256'],hash_file('sha256',$backup)) && ($meta['fingerprints']??null)===$before,'Current backup restored and all rows verified');
    $workflow=json_decode((string)file_get_contents($options['workflow-report']??''),true,512,JSON_THROW_ON_ERROR);
    $required=['B','C','D','E','F','G1','G2','H','I','School','Period','Organization','Grading','Selection','Enrollment','Handover'];$all=true;foreach($required as $suite)$all=$all && !empty($workflow['suites'][$suite]['ok']);
    $check($all && !empty($workflow['application_data_unchanged']) && !empty($workflow['code_unchanged']) && ($workflow['code_fingerprint']??'')===$hash && ($workflow['database_fingerprints']??null)===$before,'All workflow suites passed against current code and data');
    $portal=json_decode((string)file_get_contents($options['portal-report']??''),true,512,JSON_THROW_ON_ERROR);
    $check(($portal['checks_passed']??0)>0 && ($portal['code_fingerprint']??'')===$hash && ($portal['database_fingerprints']??null)===$before,'HTTP checks match current code and data');
}catch(Throwable $e){$check(false,'Missing or unreadable verification evidence');}
$check(cleanup_fingerprints(cleanup_snapshot($pdo))===$before,'Handover check leaves application data unchanged');
$ok=!array_filter($checks,static fn($r)=>!$r['ok']);$report=['ready_for_simulation_handover'=>$ok,'official_selection_certified'=>false,'checked_at'=>date(DATE_ATOM),'code_fingerprint'=>$hash,'checks'=>$checks];
if(isset($options['report'])){
    $parent=realpath(dirname($options['report']));$root=strtolower(str_replace('\\','/',dirname(__DIR__)).'/');
    if(!$parent || str_starts_with(strtolower(str_replace('\\','/',$parent)).'/',$root))throw new RuntimeException('Report must be outside the web root.');
    file_put_contents($options['report'],json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
exit($ok?0:1);
