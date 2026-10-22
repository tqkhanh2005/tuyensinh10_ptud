<?php
declare(strict_types=1);
require_once __DIR__.'/exam_organization_service.php';

function grading_state(PDO $pdo,int $period): ?array {
    if(!$pdo->query("SHOW TABLES LIKE 'exam_grading_states'")->fetchColumn())return null;
    $q=$pdo->prepare('SELECT * FROM exam_grading_states WHERE admission_period_id=?');$q->execute([$period]);return $q->fetch() ?: null;
}
/** Common period lock for grading, review, publication and appeal writers. */
function grading_period_lock(PDO $pdo,int $period): ?array {
    $cfg=period_configuration($pdo,$period);if(!$cfg)return null;
    $q=$pdo->prepare('SELECT status FROM admission_periods WHERE id=? FOR UPDATE');$q->execute([$period]);
    if($q->fetchColumn()!=='published')throw new RuntimeException('Kỳ tuyển sinh chưa công bố hoặc đã đóng.');
    return $cfg;
}
function grading_editable(PDO $pdo,int $period,?int $subject=null,string $phase=''): void {
    if(!grading_period_lock($pdo,$period))return;
    if(empty(exam_organization_state($pdo,$period)['released_at']))throw new RuntimeException('Cần phát hành phiếu dự thi trước khi xử lý bài thi.');
    if(grading_state($pdo,$period))throw new RuntimeException('Điểm đã được rà soát và khóa; không thể sửa dữ liệu chấm thi.');
    $q=$pdo->prepare('SELECT 1 FROM publication_events WHERE admission_period_id=? AND publication_type="exam_results" AND status="published"');$q->execute([$period]);
    if($q->fetchColumn())throw new RuntimeException('Điểm thi đã công bố và bị khóa.');
    if($subject && in_array($phase,['attendance','codes','scores'],true)){
        $q=$pdo->prepare('SELECT exam_date,start_time,end_time FROM exam_subjects WHERE admission_period_id=? AND id=?');$q->execute([$period,$subject]);$s=$q->fetch();
        $edge=$phase==='attendance'?'start_time':'end_time';
        if(!$s || !$s['exam_date'] || !$s[$edge] || $s['exam_date'].' '.$s[$edge]>date('Y-m-d H:i:s'))throw new RuntimeException($phase==='attendance'?'Chưa đến giờ thi để điểm danh.':'Môn thi chưa kết thúc.');
    }
}
function grading_score_valid(PDO $pdo,int $period,float $value): bool {
    return is_finite($value) && (!period_configuration($pdo,$period) || ($value>=0 && $value<=10 && abs($value*4-round($value*4))<0.00001));
}
function grading_assign_initial(PDO $pdo,int $period,int $subject,int $room,int $member,int $actor): int {
    $pdo->beginTransaction();try{
        grading_editable($pdo,$period);
        $q=$pdo->prepare('SELECT cm.id FROM council_members cm JOIN councils c ON c.id=cm.council_id AND c.council_type="grading" AND c.status="established" JOIN council_subjects cs ON cs.council_id=c.id JOIN staff st ON st.id=cm.staff_id AND st.active=1 JOIN users u ON u.id=st.user_id AND u.active=1 WHERE cm.id=? AND c.admission_period_id=? AND cs.exam_subject_id=?');$q->execute([$member,$period,$subject]);
        if(!$q->fetchColumn())throw new InvalidArgumentException('Chọn giáo viên có tài khoản đang hoạt động thuộc hội đồng chấm đúng môn/kỳ.');
        $sql='SELECT p.id,sc.id score_id FROM exam_papers p LEFT JOIN scores sc ON sc.exam_paper_id=p.id WHERE p.admission_period_id=? AND p.exam_subject_id=? AND p.attendance_status IN ("present","present_penalty")';$params=[$period,$subject];if($room){$sql.=' AND p.exam_room_id=?';$params[]=$room;}$sql.=' ORDER BY p.id FOR UPDATE';
        $q=$pdo->prepare($sql);$q->execute($params);$papers=$q->fetchAll();if(!$papers)throw new RuntimeException('Chưa có bài thi có mặt trong phạm vi chọn.');
        $insert=$pdo->prepare('INSERT INTO exam_paper_grader_assignments(exam_paper_id,council_member_id,assigned_by_user_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE council_member_id=VALUES(council_member_id),assigned_by_user_id=VALUES(assigned_by_user_id)');
        foreach($papers as $p){if($p['score_id'])throw new RuntimeException('Đã có điểm; không đổi người chấm ban đầu.');$insert->execute([$p['id'],$member,$actor]);}
        period_event($pdo,$period,$actor,'grading_assign',['subject'=>$subject,'papers'=>count($papers)]);$pdo->commit();return count($papers);
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function grading_readiness(PDO $pdo,int $period): array {
    $q=$pdo->prepare('SELECT s.id,s.name,s.exam_date,s.end_time,COUNT(p.id) papers,SUM(p.attendance_status="absent") absent,SUM(p.attendance_status IN ("present","present_penalty")) present,SUM(p.anonymous_code IS NOT NULL) coded,SUM(sc.id IS NOT NULL) scored,SUM(p.paper_status="matched") matched,SUM(ga.id IS NOT NULL) graders FROM exam_subjects s LEFT JOIN exam_papers p ON p.exam_subject_id=s.id AND p.admission_period_id=s.admission_period_id LEFT JOIN scores sc ON sc.exam_paper_id=p.id LEFT JOIN exam_paper_grader_assignments ga ON ga.exam_paper_id=p.id WHERE s.admission_period_id=? GROUP BY s.id,s.name,s.exam_date,s.end_time ORDER BY s.exam_date,s.start_time');$q->execute([$period]);$subjects=$q->fetchAll();$errors=[];
    $q=$pdo->prepare('SELECT COUNT(*) FROM room_assignments WHERE admission_period_id=?');$q->execute([$period]);$students=(int)$q->fetchColumn();
    if(!$students || count($subjects)!==3)$errors[]='Cần đủ học sinh xếp phòng và ba môn thi.';
    foreach($subjects as $s){
        if(!$s['exam_date'] || !$s['end_time'] || $s['exam_date'].' '.$s['end_time']>date('Y-m-d H:i:s'))$errors[]='Chưa kết thúc tất cả môn thi.';
        if((int)$s['papers']!==$students || (int)$s['present']+(int)$s['absent']!==$students)$errors[]='Chưa đủ danh sách bài thi hoặc điểm danh.';
        if((int)$s['present']!==(int)$s['scored'] || (int)$s['present']!==(int)$s['matched'] || (int)$s['graders']<(int)$s['present'])$errors[]='Bài thi có mặt phải đủ phách, người chấm, điểm và ghép phách.';
    }
    $q=$pdo->prepare('SELECT COUNT(*) FROM exam_papers p LEFT JOIN scores sc ON sc.exam_paper_id=p.id LEFT JOIN exam_paper_grader_assignments ga ON ga.exam_paper_id=p.id LEFT JOIN council_members cm ON cm.id=ga.council_member_id LEFT JOIN councils c ON c.id=cm.council_id LEFT JOIN staff st ON st.id=cm.staff_id LEFT JOIN users u ON u.id=st.user_id WHERE p.admission_period_id=? AND ((p.attendance_status="absent" AND (sc.id IS NOT NULL OR p.anonymous_code IS NOT NULL)) OR (p.attendance_status IN ("present","present_penalty") AND (p.anonymous_code IS NULL OR sc.anonymous_code<>p.anonymous_code OR sc.total_score<0 OR sc.total_score>10 OR ABS(sc.total_score*4-ROUND(sc.total_score*4))>0.00001 OR c.admission_period_id<>p.admission_period_id OR c.council_type<>"grading" OR c.status<>"established" OR st.active<>1 OR u.id IS NULL OR u.active<>1 OR NOT EXISTS(SELECT 1 FROM council_subjects cs WHERE cs.council_id=c.id AND cs.exam_subject_id=p.exam_subject_id))))');$q->execute([$period]);
    if((int)$q->fetchColumn())$errors[]='Có điểm/phách/giáo viên hoặc trạng thái vắng chưa hợp lệ.';
    return ['students'=>$students,'subjects'=>$subjects,'errors'=>array_values(array_unique($errors))];
}
function grading_review(PDO $pdo,int $period,int $actor): void {
    $pdo->beginTransaction();try{
        grading_editable($pdo,$period);$ready=grading_readiness($pdo,$period);if($ready['errors'])throw new RuntimeException(implode(' ',$ready['errors']));
        $pdo->prepare('INSERT INTO exam_grading_states(admission_period_id,reviewed_at,reviewed_by) VALUES(?,NOW(),?)')->execute([$period,$actor]);
        period_event($pdo,$period,$actor,'grading_review',['students'=>$ready['students']]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
/** Completed managed appeals override the immutable first score. Call inside the appeal transaction. */
function grading_appeal_guard(PDO $pdo,int $period,int $user,?int $member=null,?float $score=null): bool {
    $cfg=grading_period_lock($pdo,$period);if(!$cfg)return false;
    $q=$pdo->prepare('SELECT 1 FROM publication_events WHERE admission_period_id=? AND publication_type="exam_results" AND status="published"');$q->execute([$period]);if(!$q->fetchColumn())throw new RuntimeException('Chỉ phúc khảo sau khi công bố điểm thi.');
    $q=$pdo->prepare('SELECT 1 FROM admission_runs WHERE admission_period_id=? LIMIT 1');$q->execute([$period]);if($q->fetchColumn())throw new RuntimeException('Đã có lượt xét tuyển; cần xử lý kết quả xét tuyển trước khi đổi điểm.');
    if($score!==null && !grading_score_valid($pdo,$period,$score))throw new InvalidArgumentException('Điểm phúc khảo phải trong thang 10 và theo bước 0,25.');
    if($member){$q=$pdo->prepare('SELECT 1 FROM council_members cm JOIN staff st ON st.id=cm.staff_id AND st.active=1 JOIN users u ON u.id=st.user_id AND u.active=1 JOIN councils c ON c.id=cm.council_id AND c.status="established" AND c.council_type="appeal" WHERE cm.id=? AND u.id=? AND c.admission_period_id=?');$q->execute([$member,$user,$period]);if(!$q->fetchColumn())throw new RuntimeException('Cán bộ phúc khảo hoặc tài khoản không còn hoạt động.');}
    return true;
}
