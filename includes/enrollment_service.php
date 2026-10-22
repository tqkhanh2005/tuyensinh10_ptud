<?php
declare(strict_types=1);
require_once __DIR__.'/admission_selection_service.php';

function enrollment_schema(PDO $pdo): bool {return (bool)$pdo->query("SHOW COLUMNS FROM enrollment_confirmations LIKE 'receipt_code'")->fetch();}
function enrollment_state_label(string $status,?string $end=null): string {
    if($status==='confirmed')return 'Đã xác nhận';
    return $end && $end<date('Y-m-d H:i:s')?'Quá hạn chưa xác nhận':'Chưa xác nhận';
}
function enrollment_periods_for_candidate(PDO $pdo,int $candidate): array {
    $q=$pdo->prepare('SELECT DISTINCT p.id,p.name,p.academic_year FROM admission_periods p JOIN admission_results ar ON ar.admission_period_id=p.id WHERE ar.candidate_id=? AND EXISTS(SELECT 1 FROM publication_events pe WHERE pe.admission_period_id=p.id AND pe.publication_type="admission_results" AND pe.status="published") ORDER BY p.id DESC');$q->execute([$candidate]);return $q->fetchAll();
}
/** Same source for eligibility screen, submission and printable receipt. */
function enrollment_context(PDO $pdo,int $candidate,int $period): array {
    $q=$pdo->prepare('SELECT * FROM admission_periods WHERE id=?');$q->execute([$period]);$p=$q->fetch() ?: null;$cfg=period_configuration($pdo,$period);$error='';$result=null;$confirmation=null;
    if(!$p)$error='Chưa có kỳ tuyển sinh phù hợp.';
    else {
        $q=$pdo->prepare('SELECT 1 FROM publication_events WHERE admission_period_id=? AND publication_type="admission_results" AND status="published"');$q->execute([$period]);
        if(!$q->fetchColumn())$error='Kết quả tuyển sinh chưa được công bố.';
        else {
            $q=$pdo->prepare('SELECT ar.*,s.name school_name,s.address school_address,s.active school_active,s.level school_level,c.full_name,c.date_of_birth,c.status candidate_status,c.eligible,c.user_id,u.active user_active,n.exam_number FROM admission_results ar JOIN admission_runs r ON r.id=ar.admission_run_id AND r.status="finalized" JOIN candidates c ON c.id=ar.candidate_id JOIN users u ON u.id=c.user_id JOIN schools s ON s.id=ar.school_id LEFT JOIN exam_candidate_numbers n ON n.candidate_id=c.id AND n.admission_period_id=ar.admission_period_id WHERE ar.admission_period_id=? AND ar.candidate_id=? AND ar.result_status="admitted" AND r.id=(SELECT MAX(r2.id) FROM admission_runs r2 WHERE r2.admission_period_id=ar.admission_period_id AND r2.status="finalized")');$q->execute([$period,$candidate]);$rows=$q->fetchAll();
            if(count($rows)!==1)$error=count($rows)>1?'Kết quả có nhiều nguyện vọng trúng tuyển; cần liên hệ quản trị.':'Không có kết quả trúng tuyển đã công bố để xác nhận.';
            else {
                $result=$rows[0];
                if(enrollment_schema($pdo)){$q=$pdo->prepare('SELECT * FROM enrollment_confirmations WHERE admission_period_id=? AND candidate_id=?');$q->execute([$period,$candidate]);$confirmation=$q->fetch() ?: null;}
                if($confirmation && ((int)$confirmation['admission_result_id']!==(int)$result['id'] || (int)$confirmation['school_id']!==(int)$result['school_id'] || $result['enrollment_status']!=='confirmed'))$error='Thông tin xác nhận không khớp kết quả đã công bố; cần liên hệ quản trị.';
                elseif(!$confirmation && $result['enrollment_status']==='confirmed')$error='Thiếu bản ghi xác nhận; cần liên hệ quản trị.';
                elseif(!$confirmation){
                    if($p['status']!=='published')$error='Kỳ tuyển sinh đã đóng hoặc chưa mở.';
                    elseif(!$result['user_active'] || !$result['eligible'] || $result['candidate_status']!=='active')$error='Hồ sơ hoặc tài khoản không còn đủ điều kiện xác nhận.';
                    elseif(!$result['school_active'] || $result['school_level']!=='THPT')$error='Trường trúng tuyển không còn hoạt động; cần liên hệ quản trị.';
                    elseif(empty($p['enrollment_start']) || empty($p['enrollment_end']))$error='Chưa thiết lập thời hạn xác nhận nhập học.';
                    elseif($cfg && (($cfg['settings']['enrollment_start'] ?? null)!==$p['enrollment_start'] || ($cfg['settings']['enrollment_end'] ?? null)!==$p['enrollment_end']))$error='Thời hạn chưa khớp kế hoạch của kỳ; cần liên hệ quản trị.';
                    elseif(date('Y-m-d H:i:s')<$p['enrollment_start'])$error='Chưa đến thời gian xác nhận nhập học.';
                    elseif(date('Y-m-d H:i:s')>$p['enrollment_end'])$error='Đã hết thời gian xác nhận nhập học.';
                    elseif(!enrollment_schema($pdo))$error='Chưa cập nhật cấu trúc xác nhận nhập học.';
                }
            }
        }
    }
    return ['period'=>$p,'configuration'=>$cfg,'result'=>$result,'confirmation'=>$confirmation,'error'=>$error,'can_confirm'=>$result && !$confirmation && $error===''];
}
function enrollment_confirm_managed(PDO $pdo,int $candidate,int $period,int $actor): array {
    $pdo->beginTransaction();try{
        $p=$pdo->prepare('SELECT id FROM admission_periods WHERE id=? FOR UPDATE');$p->execute([$period]);if(!$p->fetchColumn())throw new RuntimeException('Kỳ tuyển sinh không tồn tại.');
        $q=$pdo->prepare('SELECT c.user_id,c.status,u.active FROM candidates c JOIN users u ON u.id=c.user_id WHERE c.id=? FOR UPDATE');$q->execute([$candidate]);$owner=$q->fetch();
        if(!$owner || (int)$owner['user_id']!==$actor || !(int)$owner['active'])throw new RuntimeException('Bạn không có quyền xác nhận cho hồ sơ này.');
        $q=$pdo->prepare('SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id AND r.active=1 JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id WHERE ur.user_id=? AND p.code="enrollment.confirm" LIMIT 1');$q->execute([$actor]);if(!$q->fetchColumn())throw new RuntimeException('Tài khoản không có quyền xác nhận nhập học.');
        $q=$pdo->prepare('SELECT ar.id FROM admission_results ar WHERE ar.candidate_id=? AND ar.admission_period_id=? FOR UPDATE');$q->execute([$candidate,$period]);$q->fetchAll();
        $ctx=enrollment_context($pdo,$candidate,$period);if($ctx['error'])throw new RuntimeException($ctx['error']);
        if($ctx['confirmation']){$pdo->commit();return $ctx['confirmation'];}
        $r=$ctx['result'];$snap=selection_snapshot($pdo,(int)$r['admission_run_id']);
        $asp=$snap?array_column($snap['inputs']['aspirations'],null,'id'):[];$selected=$asp[$r['aspiration_id']] ?? null;
        $saved=$snap['report']['candidates'][$candidate] ?? null;
        if(!$snap || empty($snap['reviewed_at']) || !hash_equals($snap['input_hash'],selection_input_hash($snap['inputs'])) || ($snap['report']['assigned'][$candidate] ?? 0)!==(int)$r['aspiration_id'] || !$selected || !$saved || (int)$selected['candidate_id']!==$candidate || (int)$selected['school_id']!==(int)$r['school_id'] || (int)$selected['priority_order']!==(int)$r['priority_order'] || abs((float)$r['admission_score']-(float)$saved['admission_score'])>0.00001 || abs((float)$r['score_total']-(float)$saved['score_total'])>0.00001 || abs((float)$r['priority_points']-(float)$saved['priority_points'])>0.00001)throw new RuntimeException('Kết quả không khớp lượt xét tuyển đã duyệt; cần liên hệ quản trị.');
        $receipt=strtoupper(bin2hex(random_bytes(16)));
        $pdo->prepare('INSERT INTO enrollment_confirmations(admission_period_id,admission_result_id,candidate_id,school_id,actor_user_id,receipt_code) VALUES(?,?,?,?,?,?)')->execute([$period,$r['id'],$candidate,$r['school_id'],$actor,$receipt]);$id=(int)$pdo->lastInsertId();
        $q=$pdo->prepare('UPDATE admission_results SET enrollment_status="confirmed" WHERE id=? AND enrollment_status="pending"');$q->execute([$r['id']]);if($q->rowCount()!==1)throw new RuntimeException('Trạng thái đã thay đổi; vui lòng tải lại trang.');
        period_event($pdo,$period,$actor,'enrollment_confirmed',['candidate'=>$candidate,'result'=>(int)$r['id'],'school'=>(int)$r['school_id'],'receipt'=>$receipt]);
        $mode=$ctx['configuration']['settings']['mode'] ?? 'simulation';
        $pdo->prepare('INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)')->execute([$actor,'Xác nhận nhập học',($mode==='simulation'?'[MÔ PHỎNG] ':'').'Đã ghi nhận xác nhận nhập học tại '.$r['school_name'].'. Mã biên nhận: '.$receipt.'.']);
        $q=$pdo->prepare('SELECT * FROM enrollment_confirmations WHERE id=?');$q->execute([$id]);$confirmation=$q->fetch();$pdo->commit();return $confirmation;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
