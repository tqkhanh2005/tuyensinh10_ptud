<?php
declare(strict_types=1);
require_once __DIR__.'/publication_service.php';
/** Caller must resolve the active teacher's school from the authenticated account. */
function teacher_period_report(PDO $pdo,int $school,string $level,int $period): array {
    if($school<1 || !in_array($level,['THCS','THPT'],true))throw new RuntimeException('Chưa liên kết trường hợp lệ.');
    $params=[$period,$school];$scope=$level==='THCS'?'c.middle_school_id=?':'a.school_id=?';
    $q=$pdo->prepare('SELECT DISTINCT c.id,c.eligible FROM candidates c JOIN aspirations a ON a.candidate_id=c.id WHERE a.admission_period_id=? AND '.$scope.' AND c.status="active"');$q->execute($params);$students=$q->fetchAll();
    $summary=['total_candidates'=>count($students),'eligible_count'=>count(array_filter($students,static fn($r)=>(bool)$r['eligible'])),'total_admitted'=>0,'total_confirmed'=>0,'quota'=>0];$summary['ineligible_count']=$summary['total_candidates']-$summary['eligible_count'];
    $q=$pdo->prepare('SELECT COALESCE(SUM(quota),0) FROM admission_quotas WHERE admission_period_id=? AND school_id=? AND status="set"');$q->execute($params);if($level==='THPT')$summary['quota']=(int)$q->fetchColumn();
    $scope=$level==='THCS'?'c.middle_school_id=?':'ar.school_id=?';
    $q=$pdo->prepare('SELECT COUNT(*),COALESCE(SUM(ar.enrollment_status="confirmed"),0) FROM admission_results ar JOIN candidates c ON c.id=ar.candidate_id WHERE ar.admission_period_id=? AND '.$scope.' AND ar.result_status="admitted" AND ar.admission_run_id=(SELECT MAX(r.id) FROM admission_runs r WHERE r.admission_period_id=ar.admission_period_id AND r.status="finalized") AND EXISTS(SELECT 1 FROM publication_events pe WHERE pe.admission_period_id=ar.admission_period_id AND pe.publication_type="admission_results" AND pe.status="published")');$q->execute($params);[$admitted,$confirmed]=$q->fetch(PDO::FETCH_NUM);$summary['total_admitted']=(int)$admitted;$summary['total_confirmed']=(int)$confirmed;
    if($level==='THCS')$academic=[['grade'=>'Đã xác nhận','cnt'=>(int)$confirmed],['grade'=>'Trúng tuyển, chưa xác nhận','cnt'=>(int)$admitted-(int)$confirmed],['grade'=>'Chưa có kết quả trúng tuyển đã công bố','cnt'=>max(0,count($students)-(int)$admitted)]];
    else {$q=$pdo->prepare('SELECT CONCAT("Nguyện vọng ",priority_order) grade,COUNT(*) cnt FROM aspirations WHERE admission_period_id=? AND school_id=? GROUP BY priority_order ORDER BY priority_order');$q->execute($params);$academic=$q->fetchAll();}
    $schoolJoin=$level==='THCS'?'s.id=a.school_id':'s.id=c.middle_school_id';$scope=$level==='THCS'?'c.middle_school_id=?':'a.school_id=?';
    $q=$pdo->prepare('SELECT s.name school_name,COUNT(DISTINCT c.id) count_aspirations FROM aspirations a JOIN candidates c ON c.id=a.candidate_id JOIN schools s ON '.$schoolJoin.' WHERE a.admission_period_id=? AND '.$scope.' GROUP BY s.id,s.name ORDER BY count_aspirations DESC,s.id LIMIT 10');$q->execute($params);
    return ['summary'=>$summary,'academic'=>$academic,'schools'=>$q->fetchAll()];
}
