<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';
require_once __DIR__.'/auth.php';

function publication_exists(PDO $pdo,int $periodId,string $type):bool {
    $q=$pdo->prepare('SELECT 1 FROM publication_events WHERE admission_period_id=:p AND publication_type=:t AND status="published" LIMIT 1');
    $q->execute(['p'=>$periodId,'t'=>$type]); return (bool)$q->fetchColumn();
}
function publication_periods(PDO $pdo):array {
    return $pdo->query('SELECT id,name,academic_year,status FROM admission_periods ORDER BY id DESC')->fetchAll();
}
function current_admission_period_id(PDO $pdo):int {
    $stmt=$pdo->query('SELECT id FROM admission_periods WHERE status="published" ORDER BY id DESC LIMIT 1');
    $id=$stmt->fetchColumn();
    if($id===false) throw new RuntimeException('[MISSING CURRENT ADMISSION PERIOD]');
    return (int)$id;
}
function publish_exam_results(PDO $pdo,int $periodId,int $userId):void {
    $pdo->beginTransaction();
    try {
        if(publication_exists($pdo,$periodId,'exam_results')) throw new RuntimeException('Điểm thi đã được công bố.');
        $q=$pdo->prepare('SELECT COUNT(*) FROM scores sc JOIN exam_papers ep ON ep.id=sc.exam_paper_id WHERE ep.admission_period_id=:p');
        $q->execute(['p'=>$periodId]);
        if((int)$q->fetchColumn()===0) throw new RuntimeException('Chưa có dữ liệu điểm thi để công bố.');
        $ins=$pdo->prepare('INSERT INTO publication_events(admission_period_id,publication_type,status,published_by_user_id) VALUES(:p,"exam_results","published",:u)');
        $ins->execute(['p'=>$periodId,'u'=>$userId]);
        $pdo->commit();
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function publish_admission_results(PDO $pdo,int $periodId,int $userId):void {
    $pdo->beginTransaction();
    try {
        if(publication_exists($pdo,$periodId,'admission_results')) throw new RuntimeException('Kết quả tuyển sinh đã được công bố.');
        $run=$pdo->prepare('SELECT id,status FROM admission_runs WHERE admission_period_id=:p ORDER BY id DESC LIMIT 1 FOR UPDATE');
        $run->execute(['p'=>$periodId]); $r=$run->fetch();
        if(!$r || !in_array($r['status'],['calculated','finalized'],true)) throw new RuntimeException('Kết quả xét tuyển chưa được thực hiện.');
        $upd=$pdo->prepare('UPDATE admission_runs SET status="finalized",finalized_at=COALESCE(finalized_at,CURRENT_TIMESTAMP) WHERE id=:id');
        $upd->execute(['id'=>(int)$r['id']]);
        $ins=$pdo->prepare('INSERT INTO publication_events(admission_period_id,publication_type,status,published_by_user_id) VALUES(:p,"admission_results","published",:u)');
        $ins->execute(['p'=>$periodId,'u'=>$userId]);
        $pdo->commit();
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function exam_results_published(PDO $pdo,int $periodId):bool{return publication_exists($pdo,$periodId,'exam_results');}
function admission_results_published(PDO $pdo,int $periodId):bool{return publication_exists($pdo,$periodId,'admission_results');}
function public_exam_results(PDO $pdo,int $periodId,?int $candidateId=null):array {
    if(!exam_results_published($pdo,$periodId)) throw new RuntimeException('Điểm thi chưa được công bố.');
    $sql='SELECT c.id candidate_id,c.candidate_code,c.full_name,es.subject_code,es.name subject_name,sc.total_score
          FROM scores sc JOIN exam_papers ep ON ep.id=sc.exam_paper_id JOIN candidates c ON c.id=ep.candidate_id
          JOIN exam_subjects es ON es.id=ep.exam_subject_id WHERE ep.admission_period_id=:p';
    $params=['p'=>$periodId];
    if($candidateId!==null){$sql.=' AND c.id=:c';$params['c']=$candidateId;}
    $sql.=' ORDER BY c.full_name,es.id'; $q=$pdo->prepare($sql);$q->execute($params);return $q->fetchAll();
}
function public_admission_results(PDO $pdo,int $periodId,?int $candidateId=null):array {
    if(!admission_results_published($pdo,$periodId)) throw new RuntimeException('Kết quả tuyển sinh chưa được công bố.');
    $sql='SELECT ar.*,c.candidate_code,c.full_name,s.school_code,s.name school_name
          FROM admission_results ar JOIN candidates c ON c.id=ar.candidate_id LEFT JOIN schools s ON s.id=ar.school_id
          JOIN admission_runs r ON r.id=ar.admission_run_id AND r.status="finalized"
          WHERE ar.admission_period_id=:p'; $params=['p'=>$periodId];
    if($candidateId!==null){$sql.=' AND ar.candidate_id=:c';$params['c']=$candidateId;}
    $sql.=' ORDER BY c.full_name,ar.priority_order';$q=$pdo->prepare($sql);$q->execute($params);return $q->fetchAll();
}
function published_quota_list(PDO $pdo,int $periodId,?int $schoolId=null,?string $area=null):array {
    $sql='SELECT q.school_id,s.school_code,s.name school_name,s.district,s.admission_area,q.quota,q.status,q.published_at
      FROM admission_quotas q JOIN schools s ON s.id=q.school_id WHERE q.admission_period_id=:p AND q.status="set"';
    $params=['p'=>$periodId];
    if($schoolId!==null){$sql.=' AND q.school_id=:s';$params['s']=$schoolId;}
    if($area!==null && $area!==''){$sql.=' AND s.admission_area=:a';$params['a']=$area;}
    $sql.=' ORDER BY s.name';
    $q=$pdo->prepare($sql);
    $q->execute($params);
    return $q->fetchAll();
}
function admitted_list(PDO $pdo,int $periodId,?int $schoolId=null,?string $enrollmentStatus=null):array {
    if(!admission_results_published($pdo,$periodId)) throw new RuntimeException('Kết quả tuyển sinh chưa được công bố.');
    $sql='SELECT ar.id,ar.candidate_id,c.candidate_code,c.full_name,c.date_of_birth,c.gender,
      s.school_code,s.name school_name,ar.priority_order,ar.score_total,ar.priority_points,ar.admission_score,
      ar.enrollment_status FROM admission_results ar JOIN candidates c ON c.id=ar.candidate_id
      JOIN schools s ON s.id=ar.school_id JOIN admission_runs r ON r.id=ar.admission_run_id AND r.status="finalized"
      WHERE ar.admission_period_id=:p AND ar.result_status="admitted"';$params=['p'=>$periodId];
    if($schoolId!==null){$sql.=' AND ar.school_id=:s';$params['s']=$schoolId;}
    if($enrollmentStatus!==null && in_array($enrollmentStatus,['pending','confirmed'],true)){$sql.=' AND ar.enrollment_status=:e';$params['e']=$enrollmentStatus;}
    $sql.=' ORDER BY s.name,ar.priority_order,ar.admission_score DESC,c.full_name';$q=$pdo->prepare($sql);$q->execute($params);return $q->fetchAll();
}
function statistics(PDO $pdo,int $periodId,string $type):array {
    $allowed=['profile_status','admission_result','admitted']; if(!in_array($type,$allowed,true)) throw new InvalidArgumentException('Loại thống kê không hợp lệ.');
    if($type==='profile_status'){
        $q=$pdo->prepare('SELECT c.eligible, COUNT(DISTINCT c.id) total FROM candidates c JOIN aspirations a ON a.candidate_id=c.id WHERE a.admission_period_id=:p AND c.status="active" GROUP BY c.eligible ORDER BY c.eligible');
        $q->execute(['p'=>$periodId]);
        return $q->fetchAll();
    }
    if($type==='admission_result'){
        if(!admission_results_published($pdo,$periodId)) throw new RuntimeException('Kết quả tuyển sinh chưa được công bố.');
        $q=$pdo->prepare('SELECT ar.result_status,COUNT(*) total FROM admission_results ar JOIN admission_runs r ON r.id=ar.admission_run_id AND r.status="finalized" WHERE ar.admission_period_id=:p GROUP BY ar.result_status ORDER BY ar.result_status');$q->execute(['p'=>$periodId]);return $q->fetchAll();
    }
    if(!admission_results_published($pdo,$periodId)) throw new RuntimeException('Kết quả tuyển sinh chưa được công bố.');
    $q=$pdo->prepare('SELECT ar.enrollment_status,COUNT(*) total FROM admission_results ar JOIN admission_runs r ON r.id=ar.admission_run_id AND r.status="finalized" WHERE ar.admission_period_id=:p AND ar.result_status="admitted" GROUP BY ar.enrollment_status ORDER BY ar.enrollment_status');$q->execute(['p'=>$periodId]);return $q->fetchAll();
}
function csv_download(string $filename,array $rows):never {
    header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
    if($rows){fputcsv($out,array_keys($rows[0]));foreach($rows as $row)fputcsv($out,$row);}
    fclose($out); exit;
}
