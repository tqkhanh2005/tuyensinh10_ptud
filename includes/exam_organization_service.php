<?php
declare(strict_types=1);
require_once __DIR__.'/admission_period_configuration.php';

function exam_organization_schema(PDO $pdo): bool {
    return (bool)$pdo->query("SHOW TABLES LIKE 'exam_organization_states'")->fetchColumn();
}
function exam_organization_state(PDO $pdo,int $period): ?array {
    if(!exam_organization_schema($pdo))return null;
    $q=$pdo->prepare('SELECT * FROM exam_organization_states WHERE admission_period_id=?');$q->execute([$period]);return $q->fetch() ?: null;
}
/** Call inside the writer's transaction: serialize against card release on the period row. */
function exam_organization_editable(PDO $pdo,int $period): void {
    if(!period_configuration($pdo,$period))return;
    $q=$pdo->prepare('SELECT status FROM admission_periods WHERE id=? FOR UPDATE');$q->execute([$period]);
    if($q->fetchColumn()==='closed')throw new RuntimeException('Kỳ tuyển sinh đã đóng.');
    if(!empty(exam_organization_state($pdo,$period)['released_at']))throw new RuntimeException('Đã phát hành phiếu dự thi; cấu hình tổ chức thi được khóa.');
    $q=$pdo->prepare('SELECT 1 FROM exam_papers WHERE admission_period_id=? LIMIT 1');$q->execute([$period]);
    if($q->fetchColumn())throw new RuntimeException('Đã lập bài thi; không thể thay đổi tổ chức thi.');
}
function exam_schedule_save(PDO $pdo,int $period,array $input,int $actor): int {
    $subjects=['VAN'=>['Ngữ văn',120],'TOAN'=>['Toán',120],'NN'=>['Ngoại ngữ',90]];
    $code=(string)($input['subject_code'] ?? '');
    if(!isset($subjects[$code]))throw new InvalidArgumentException('Chọn một trong ba môn thi thường.');
    $date=(string)($input['exam_date'] ?? '');$start=(string)($input['start_time'] ?? '');
    $begin=period_datetime($date.'T'.$start);
    if(!$begin)throw new InvalidArgumentException('Cần đủ ngày và giờ thi.');
    $end=(new DateTimeImmutable($begin))->modify('+'.$subjects[$code][1].' minutes');
    if($end->format('Y-m-d')!==$date)throw new InvalidArgumentException('Lịch thi không được vượt qua ngày hôm sau.');
    $pdo->beginTransaction();
    try {
        exam_organization_editable($pdo,$period);
        $cfg=period_configuration($pdo,$period);
        if($cfg && !empty($cfg['settings']['adjustment_end']) && $begin<=$cfg['settings']['adjustment_end'])throw new InvalidArgumentException('Lịch thi phải sau thời hạn điều chỉnh nguyện vọng.');
        if($cfg && !empty($cfg['settings']['appeal_start']) && $end->format('Y-m-d H:i:s')>=$cfg['settings']['appeal_start'])throw new InvalidArgumentException('Lịch thi phải kết thúc trước giai đoạn phúc khảo.');
        $q=$pdo->prepare('SELECT id FROM admission_periods WHERE id=?');$q->execute([$period]);if(!$q->fetchColumn())throw new InvalidArgumentException('Kỳ không tồn tại.');
        $q=$pdo->prepare('SELECT id FROM exam_subjects WHERE admission_period_id=? AND subject_code=? FOR UPDATE');$q->execute([$period,$code]);$id=(int)$q->fetchColumn();
        $q=$pdo->prepare('SELECT 1 FROM exam_subjects WHERE admission_period_id=? AND id<>? AND exam_date=? AND start_time<? AND end_time>? LIMIT 1');
        $q->execute([$period,$id,$date,$end->format('H:i:s'),substr($begin,11)]);if($q->fetchColumn())throw new InvalidArgumentException('Các môn thi bị trùng thời gian.');
        if($id) {
            $q=$pdo->prepare('SELECT 1 FROM exam_invigilator_assignments WHERE exam_subject_id=? LIMIT 1');$q->execute([$id]);if($q->fetchColumn())throw new RuntimeException('Môn đã được phân công giám thị; không đổi lịch trực tiếp.');
            $pdo->prepare('UPDATE exam_subjects SET exam_date=?,start_time=?,end_time=?,status="scheduled" WHERE id=?')->execute([$date,substr($begin,11),$end->format('H:i:s'),$id]);
        } else {
            $pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,component_count,status) VALUES(?,?,?,?,?,?,10,1,"scheduled")')->execute([$period,$code,$subjects[$code][0],$date,substr($begin,11),$end->format('H:i:s')]);$id=(int)$pdo->lastInsertId();
        }
        if(period_configuration($pdo,$period))period_event($pdo,$period,$actor,'exam_schedule',['subject'=>$code,'start'=>$begin,'end'=>$end->format('Y-m-d H:i:s')]);
        $pdo->commit();return $id;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function exam_number_generate(PDO $pdo,int $period,string $prefix): int {
    if(!preg_match('/^[A-Z0-9]{1,10}$/',$prefix))throw new InvalidArgumentException('Tiền tố SBD gồm 1–10 chữ in hoa hoặc chữ số.');
    $pdo->beginTransaction();
    try {
        exam_organization_editable($pdo,$period);
        $state=exam_organization_state($pdo,$period);
        if(!empty($state['number_prefix']) && $state['number_prefix']!==$prefix)throw new RuntimeException('Kỳ đã cấp SBD với tiền tố khác. Không đánh lại số đã cấp.');
        $q=$pdo->prepare('SELECT c.id FROM candidates c JOIN room_assignments ra ON ra.candidate_id=c.id AND ra.admission_period_id=? JOIN exam_rooms r ON r.id=ra.exam_room_id JOIN exam_sites s ON s.id=ra.exam_site_id WHERE c.status="active" AND c.eligible=1 AND EXISTS(SELECT 1 FROM aspirations a WHERE a.candidate_id=c.id AND a.admission_period_id=ra.admission_period_id) ORDER BY s.site_code,r.room_code,c.full_name,c.id');$q->execute([$period]);$ids=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
        if(!$ids)throw new RuntimeException('Chưa có thí sinh hợp lệ được xếp phòng.');
        $q=$pdo->prepare('SELECT candidate_id,sequence_no FROM exam_candidate_numbers WHERE admission_period_id=?');$q->execute([$period]);$known=[];$sequence=0;foreach($q as $row){$known[(int)$row['candidate_id']]=true;$sequence=max($sequence,(int)$row['sequence_no']);}
        $insert=$pdo->prepare('INSERT INTO exam_candidate_numbers(admission_period_id,candidate_id,sequence_no,exam_number) VALUES(?,?,?,?)');$count=0;
        foreach($ids as $id){if(isset($known[$id]))continue;$sequence++;$insert->execute([$period,$id,$sequence,$prefix.str_pad((string)$sequence,6,'0',STR_PAD_LEFT)]);$count++;}
        $pdo->prepare('INSERT INTO exam_organization_states(admission_period_id,number_prefix) VALUES(?,?) ON DUPLICATE KEY UPDATE number_prefix=VALUES(number_prefix)')->execute([$period,$prefix]);
        $pdo->commit();return $count;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function exam_organization_readiness(PDO $pdo,int $period): array {
    $count=static function(string $sql,array $params)use($pdo):int{$q=$pdo->prepare($sql);$q->execute($params);return (int)$q->fetchColumn();};$p=[$period];$errors=[];
    $eligible=$count('SELECT COUNT(DISTINCT c.id) FROM candidates c JOIN aspirations a ON a.candidate_id=c.id WHERE a.admission_period_id=? AND c.eligible=1 AND c.status="active"',$p);
    $sites=$count('SELECT COUNT(*) FROM exam_sites WHERE admission_period_id=?',$p);
    $rooms=$count('SELECT COUNT(*) FROM exam_rooms r JOIN exam_sites s ON s.id=r.exam_site_id WHERE s.admission_period_id=?',$p);
    $allocated=$count('SELECT COUNT(*) FROM exam_allocations WHERE admission_period_id=?',$p);
    $seated=$count('SELECT COUNT(*) FROM room_assignments WHERE admission_period_id=?',$p);
    $numbered=exam_organization_schema($pdo)?$count('SELECT COUNT(*) FROM exam_candidate_numbers WHERE admission_period_id=?',$p):0;
    if(!$eligible)$errors[]='Chưa có học sinh hợp lệ đăng ký trong kỳ.';
    if($allocated!==$eligible || $seated!==$eligible || $numbered!==$eligible)$errors[]='Phải phân điểm, xếp phòng và cấp SBD đủ toàn bộ học sinh hợp lệ.';
    $invalid=$count('SELECT COUNT(*) FROM exam_allocations ea JOIN candidates c ON c.id=ea.candidate_id WHERE ea.admission_period_id=? AND (c.eligible<>1 OR c.status<>"active" OR NOT EXISTS(SELECT 1 FROM aspirations a WHERE a.candidate_id=c.id AND a.admission_period_id=ea.admission_period_id))',$p);
    if($invalid)$errors[]='Có phân bố không còn khớp học sinh đăng ký hợp lệ.';
    if(exam_organization_schema($pdo)) {
        $missing=$count('SELECT COUNT(*) FROM room_assignments r LEFT JOIN exam_candidate_numbers n ON n.admission_period_id=r.admission_period_id AND n.candidate_id=r.candidate_id WHERE r.admission_period_id=? AND n.candidate_id IS NULL',$p);
        $stale=$count('SELECT COUNT(*) FROM exam_candidate_numbers n LEFT JOIN room_assignments r ON r.admission_period_id=n.admission_period_id AND r.candidate_id=n.candidate_id WHERE n.admission_period_id=? AND r.id IS NULL',$p);
        if($missing || $stale)$errors[]='SBD không khớp danh sách xếp phòng của kỳ.';
    }
    $conflicts=$count('SELECT COUNT(*) FROM exam_invigilator_assignments a JOIN exam_subjects x ON x.id=a.exam_subject_id JOIN exam_invigilator_assignments b ON b.staff_id=a.staff_id AND b.id>a.id AND b.status="assigned" JOIN exam_subjects y ON y.id=b.exam_subject_id WHERE a.status="assigned" AND (x.admission_period_id=? OR y.admission_period_id=?) AND x.exam_date=y.exam_date AND x.start_time<y.end_time AND x.end_time>y.start_time',[$period,$period]);
    if($conflicts)$errors[]='Có giám thị bị trùng lịch coi thi.';
    $schedule=$pdo->prepare('SELECT * FROM exam_subjects WHERE admission_period_id=? ORDER BY exam_date,start_time,id');$schedule->execute($p);$schedule=$schedule->fetchAll();
    $names=array_column($schedule,'name');sort($names);$expected=['Ngữ văn','Ngoại ngữ','Toán'];sort($expected);
    if($names!==$expected)$errors[]='Cần đúng ba môn Ngữ văn, Toán, Ngoại ngữ.';
    $last=null;foreach($schedule as $subject){
        if(!$subject['exam_date'] || !$subject['start_time'] || !$subject['end_time']){$errors[]='Lịch thi chưa đủ ngày giờ.';continue;}
        $start=$subject['exam_date'].' '.$subject['start_time'];$end=$subject['exam_date'].' '.$subject['end_time'];
        if($end<=$start || ($last!==null && $start<$last))$errors[]='Lịch các môn bị trùng hoặc không hợp lệ.';$last=$end;
    }
    $occupied=$pdo->prepare('SELECT r.id,r.capacity,s.id site_id,s.address,s.capacity site_capacity,COUNT(ra.id) used FROM room_assignments ra JOIN exam_rooms r ON r.id=ra.exam_room_id JOIN exam_sites s ON s.id=ra.exam_site_id WHERE ra.admission_period_id=? GROUP BY r.id,r.capacity,s.id,s.address,s.capacity');$occupied->execute($p);$occupied=$occupied->fetchAll();$siteUsed=[];
    foreach($occupied as $room){
        $siteUsed[$room['site_id']]=($siteUsed[$room['site_id']] ?? 0)+(int)$room['used'];
        if($room['used']>$room['capacity'])$errors[]='Có phòng vượt sức chứa.';
        if(!trim((string)$room['address']))$errors[]='Điểm thi thiếu địa chỉ.';
        foreach($schedule as $subject){
            $q=$pdo->prepare('SELECT a.staff_id FROM exam_invigilator_assignments a JOIN staff st ON st.id=a.staff_id AND st.active=1 WHERE a.exam_room_id=? AND a.exam_subject_id=? AND a.status="assigned"');$q->execute([$room['id'],$subject['id']]);$staffIds=$q->fetchAll(PDO::FETCH_COLUMN);
            if(count(array_unique($staffIds))!==2)$errors[]='Mỗi phòng/môn cần hai giám thị đang hoạt động.';
            foreach($staffIds as $staffId){
                $q=$pdo->prepare('SELECT 1 FROM council_members cm JOIN councils c ON c.id=cm.council_id WHERE cm.staff_id=? AND c.admission_period_id=? AND c.exam_site_id=? AND c.council_type="invigilation" AND c.status="established" LIMIT 1');$q->execute([$staffId,$period,$room['site_id']]);if(!$q->fetchColumn())$errors[]='Giám thị phải thuộc hội đồng coi thi của điểm thi phụ trách.';
            }
        }
    }
    foreach($occupied as $room)if(($siteUsed[$room['site_id']] ?? 0)>$room['site_capacity'])$errors[]='Có điểm thi vượt sức chứa.';
    $cfg=period_configuration($pdo,$period);
    if($cfg){$q=$pdo->prepare('SELECT status FROM admission_periods WHERE id=?');$q->execute($p);if($q->fetchColumn()!=='published')$errors[]='Kỳ tuyển sinh phải được công bố trước khi phát hành phiếu.';
        foreach($schedule as $subject)if($subject['exam_date'] && $subject['start_time'] && $subject['end_time']){
            $begin=$subject['exam_date'].' '.$subject['start_time'];$end=$subject['exam_date'].' '.$subject['end_time'];
            if($begin<=date('Y-m-d H:i:s'))$errors[]='Cần phát hành phiếu trước khi bắt đầu thi.';
            if(!empty($cfg['settings']['adjustment_end']) && $begin<=$cfg['settings']['adjustment_end'])$errors[]='Lịch thi phải sau thời hạn điều chỉnh nguyện vọng.';
            if(!empty($cfg['settings']['appeal_start']) && $end>=$cfg['settings']['appeal_start'])$errors[]='Lịch thi phải trước giai đoạn phúc khảo.';
        }
        if(empty($cfg['settings']['adjustment_end']) || new DateTimeImmutable($cfg['settings']['adjustment_end'])>=new DateTimeImmutable('now'))$errors[]='Chưa kết thúc thời hạn điều chỉnh nguyện vọng.';}
    return compact('eligible','sites','rooms','allocated','seated','numbered','schedule')+['errors'=>array_values(array_unique($errors))];
}
function exam_cards_release(PDO $pdo,int $period,int $actor): void {
    $pdo->beginTransaction();try{
        exam_organization_editable($pdo,$period);$ready=exam_organization_readiness($pdo,$period);
        if($ready['errors'])throw new RuntimeException(implode(' ',$ready['errors']));
        $pdo->prepare('UPDATE exam_organization_states SET released_at=NOW(),released_by=? WHERE admission_period_id=?')->execute([$actor,$period]);
        if(period_configuration($pdo,$period))period_event($pdo,$period,$actor,'exam_released',['students'=>$ready['eligible']]);
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function exam_roster(PDO $pdo,int $period): array {
    $q=$pdo->prepare('SELECT c.id,c.full_name,c.date_of_birth,s.name exam_site_name,s.address exam_site_address,r.name exam_room_name,n.exam_number FROM room_assignments ra JOIN candidates c ON c.id=ra.candidate_id JOIN exam_sites s ON s.id=ra.exam_site_id JOIN exam_rooms r ON r.id=ra.exam_room_id LEFT JOIN exam_candidate_numbers n ON n.admission_period_id=ra.admission_period_id AND n.candidate_id=c.id WHERE ra.admission_period_id=? ORDER BY s.site_code,r.room_code,n.sequence_no,c.id');$q->execute([$period]);return $q->fetchAll();
}
function exam_organized_card(PDO $pdo,int $user,int $period): ?array {
    $state=exam_organization_state($pdo,$period);if(empty($state['released_at']))return null;
    $q=$pdo->prepare('SELECT c.id,c.full_name,c.date_of_birth,n.exam_number,s.name exam_site_name,s.address exam_site_address,r.name exam_room_name,p.name period_name,p.academic_year FROM candidates c JOIN room_assignments ra ON ra.candidate_id=c.id JOIN exam_sites s ON s.id=ra.exam_site_id JOIN exam_rooms r ON r.id=ra.exam_room_id JOIN exam_candidate_numbers n ON n.admission_period_id=ra.admission_period_id AND n.candidate_id=c.id JOIN admission_periods p ON p.id=ra.admission_period_id WHERE c.user_id=? AND ra.admission_period_id=? AND c.status="active" AND c.eligible=1');$q->execute([$user,$period]);$card=$q->fetch();if(!$card)return null;
    $q=$pdo->prepare('SELECT name,exam_date,start_time,end_time FROM exam_subjects WHERE admission_period_id=? ORDER BY exam_date,start_time');$q->execute([$period]);$card['schedule']=$q->fetchAll();$card['exam_schedule']=implode(' | ',array_map(static fn($s)=>$s['name'].' '.$s['exam_date'].' '.$s['start_time'].'–'.$s['end_time'],$card['schedule']));$card['admission_period_id']=$period;return $card;
}
