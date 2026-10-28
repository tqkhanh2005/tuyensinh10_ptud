<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const COUNCIL_TYPES = ['invigilation', 'grading', 'appeal', 'question'];

function council_type_label(string $type): string
{
    return [
        'invigilation' => 'Hội đồng coi thi',
        'grading' => 'Hội đồng chấm thi',
        'appeal' => 'Hội đồng chấm thi phúc khảo',
        'question' => 'Hội đồng ra đề',
    ][$type] ?? $type;
}

function council_list(PDO $pdo, int $periodId, ?string $type = null): array
{
    $sql = 'SELECT c.*, es.name AS site_name, GROUP_CONCAT(DISTINCT s.name ORDER BY s.id SEPARATOR \' | \') AS subjects
            FROM councils c
            LEFT JOIN exam_sites es ON es.id=c.exam_site_id
            LEFT JOIN council_subjects cs ON cs.council_id=c.id
            LEFT JOIN exam_subjects s ON s.id=cs.exam_subject_id
            WHERE c.admission_period_id=:period';
    $params = ['period' => $periodId];
    if ($type !== null && in_array($type, COUNCIL_TYPES, true)) {
        $sql .= ' AND c.council_type=:type';
        $params['type'] = $type;
    }
    $sql .= ' GROUP BY c.id ORDER BY c.id DESC';
    $q = $pdo->prepare($sql);
    $q->execute($params);
    return $q->fetchAll();
}

function council_staff(PDO $pdo): array
{
    return $pdo->query('SELECT id,full_name,workplace,staff_role,active FROM staff ORDER BY active DESC,full_name,id')->fetchAll();
}

function council_subjects(PDO $pdo, int $periodId): array
{
    $q = $pdo->prepare('SELECT id,subject_code,name,exam_date,start_time,end_time FROM exam_subjects WHERE admission_period_id=:p ORDER BY exam_date,start_time,id');
    $q->execute(['p'=>$periodId]);
    return $q->fetchAll();
}

function council_sites(PDO $pdo, int $periodId): array
{
    $q = $pdo->prepare('SELECT id,site_code,name FROM exam_sites WHERE admission_period_id=:p ORDER BY name,id');
    $q->execute(['p'=>$periodId]);
    return $q->fetchAll();
}

function council_validate_members(PDO $pdo, array $members, string $type): array
{
    if (!$members) {
        throw new InvalidArgumentException('Vui lòng thêm ít nhất một thành viên hội đồng.');
    }
    $ids=[]; $normalized=[];
    foreach ($members as $member) {
        $staffId=(int)($member['staff_id'] ?? 0);
        if ($staffId<1) throw new InvalidArgumentException('Cán bộ - giáo viên không hợp lệ.');
        if (isset($ids[$staffId])) throw new InvalidArgumentException('Không được trùng thành viên trong cùng hội đồng.');
        $ids[$staffId]=true;
        $role=trim((string)($member['role_name'] ?? ''));
        if ($type==='question') $role=mb_strtolower($role, 'UTF-8');
        $task=trim((string)($member['task_name'] ?? ''));
        if (mb_strlen($role)>100 || mb_strlen($task)>255) throw new InvalidArgumentException('Vai trò tối đa 100 ký tự; nhiệm vụ tối đa 255 ký tự.');
        if ($type==='question' && $role==='') {
            throw new InvalidArgumentException('Hội đồng ra đề phải phân công vai trò ra đề, phản biện hoặc thẩm định cho từng thành viên.');
        }
        if ($type==='question' && !in_array($role,['ra đề','phản biện','thẩm định'],true)) {
            throw new InvalidArgumentException('Vai trò Hội đồng ra đề không hợp lệ.');
        }
        $normalized[]=['staff_id'=>$staffId,'role_name'=>$role!==''?$role:null,'task_name'=>$task!==''?$task:null];
    }
    $placeholders=implode(',',array_fill(0,count($ids),'?'));
    $q=$pdo->prepare("SELECT id,active FROM staff WHERE id IN ($placeholders) ORDER BY id FOR UPDATE");
    $q->execute(array_keys($ids));
    $found=[];
    foreach($q as $row){
        $found[(int)$row['id']] = (int)$row['active'];
    }
    foreach(array_keys($ids) as $id){
        if(!array_key_exists($id,$found)) throw new InvalidArgumentException('Cán bộ - giáo viên không tồn tại.');
        if($found[$id]!==1) throw new InvalidArgumentException('Cán bộ - giáo viên không hoạt động, không thể tham gia hội đồng.');
    }
    return $normalized;
}

require_once __DIR__.'/exam_organization_service.php';

function council_datetime(string $value): ?string {
    $value=trim($value); if($value==='')return null;
    foreach(['Y-m-d\TH:i','Y-m-d\TH:i:s','Y-m-d H:i','Y-m-d H:i:s'] as $format){
        $date=DateTimeImmutable::createFromFormat('!'.$format,$value,new DateTimeZone('Asia/Ho_Chi_Minh'));
        if($date && $date->format($format)===$value && (int)$date->format('Y')>=1000)return $date->format('Y-m-d H:i:s');
    }
    throw new InvalidArgumentException('Ngày giờ không hợp lệ. Vui lòng chọn lại thời gian.');
}

function council_create(PDO $pdo, array $data): int
{
    $periodId=(int)($data['admission_period_id']??0);
    $type=trim((string)($data['council_type']??''));
    $name=trim((string)($data['name']??''));
    $siteId=(int)($data['exam_site_id']??0);
    $subjectIds=array_values(array_unique(array_map('intval',(array)($data['exam_subject_ids']??[]))));

    if($periodId<1 || !in_array($type,COUNCIL_TYPES,true) || $name==='') throw new InvalidArgumentException('Thông tin hội đồng không hợp lệ.');
    if(mb_strlen($name)>200 || mb_strlen(trim((string)($data['location']??'')))>255) throw new InvalidArgumentException('Tên hội đồng tối đa 200 ký tự; địa điểm tối đa 255 ký tự.');
    $start=council_datetime((string)($data['start_at']??'')); $end=council_datetime((string)($data['end_at']??''));
    if(($start===null)!==($end===null)) throw new InvalidArgumentException('Vui lòng nhập cả thời gian bắt đầu và kết thúc, hoặc để trống cả hai.');
    if($start!==null && $end<=$start) throw new InvalidArgumentException('Thời gian kết thúc phải sau thời gian bắt đầu.');

    $pdo->beginTransaction();
    try{
        if($type==='invigilation')exam_organization_editable($pdo,$periodId);
        $p=$pdo->prepare('SELECT id FROM admission_periods WHERE id=:p FOR UPDATE');$p->execute(['p'=>$periodId]);
        if(!$p->fetchColumn()) throw new InvalidArgumentException('Kỳ tuyển sinh không tồn tại.');
        $members=council_validate_members($pdo,(array)($data['members']??[]),$type);
        $duplicate=$pdo->prepare('SELECT id FROM councils WHERE admission_period_id=? AND council_type=? AND name=? LIMIT 1');
        $duplicate->execute([$periodId,$type,$name]);
        if($duplicate->fetchColumn()) throw new InvalidArgumentException('Tên hội đồng đã tồn tại trong kỳ tuyển sinh này. Vui lòng chọn tên khác.');
        if($type==='invigilation'){
            if($siteId<1) throw new InvalidArgumentException('Hội đồng coi thi phải gắn với điểm thi.');
            $s=$pdo->prepare('SELECT id FROM exam_sites WHERE id=:s AND admission_period_id=:p FOR UPDATE');$s->execute(['s'=>$siteId,'p'=>$periodId]);
            if(!$s->fetchColumn()) throw new InvalidArgumentException('Điểm thi không thuộc kỳ tuyển sinh hiện tại.');
            if($subjectIds) throw new InvalidArgumentException('Hội đồng coi thi không được gắn môn thi theo đặc tả hiện tại.');
        } else {
            if(!$subjectIds) throw new InvalidArgumentException('Hội đồng phải được gắn với môn thi phụ trách.');
            if(in_array($type,['appeal','question'],true) && count($subjectIds)!==1) throw new InvalidArgumentException('Hội đồng này phải gắn với đúng một môn thi phụ trách theo đặc tả.');
            if($siteId>0) throw new InvalidArgumentException('Chỉ hội đồng coi thi được gắn điểm thi.');
            $placeholders=implode(',',array_fill(0,count($subjectIds),'?'));
            $s=$pdo->prepare("SELECT id FROM exam_subjects WHERE admission_period_id=? AND id IN ($placeholders) FOR UPDATE");
            $s->execute(array_merge([$periodId],$subjectIds));
            $valid=array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN));
            sort($valid); $expected=$subjectIds; sort($expected);
            if($valid!==$expected) throw new InvalidArgumentException('Có môn thi không thuộc kỳ tuyển sinh hiện tại.');
        }

        $q=$pdo->prepare('INSERT INTO councils(admission_period_id,council_type,name,exam_site_id,start_at,end_at,location,status) VALUES(:p,:t,:n,:site,:start,:end,:location,"established")');
        $q->execute(['p'=>$periodId,'t'=>$type,'n'=>$name,'site'=>$siteId>0?$siteId:null,'start'=>$start,'end'=>$end,'location'=>trim((string)($data['location']??''))?:null]);
        $councilId=(int)$pdo->lastInsertId();
        if($subjectIds){
            $ins=$pdo->prepare('INSERT INTO council_subjects(council_id,exam_subject_id) VALUES(:c,:s)');
            foreach($subjectIds as $sid)$ins->execute(['c'=>$councilId,'s'=>$sid]);
        }
        $ins=$pdo->prepare('INSERT INTO council_members(council_id,staff_id,role_name,task_name) VALUES(:c,:s,:r,:task)');
        foreach($members as $m)$ins->execute(['c'=>$councilId,'s'=>$m['staff_id'],'r'=>$m['role_name'],'task'=>$m['task_name']]);
        $pdo->commit(); return $councilId;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

function council_find(PDO $pdo,int $id):?array
{
    $q=$pdo->prepare('SELECT * FROM councils WHERE id=:id LIMIT 1');$q->execute(['id'=>$id]);$c=$q->fetch(); if(!$c)return null;
    $q=$pdo->prepare('SELECT cm.*,s.full_name,s.workplace,s.staff_role FROM council_members cm JOIN staff s ON s.id=cm.staff_id WHERE cm.council_id=:c ORDER BY cm.id');$q->execute(['c'=>$id]);$c['members']=$q->fetchAll();
    $q=$pdo->prepare('SELECT exam_subject_id FROM council_subjects WHERE council_id=:c ORDER BY exam_subject_id');$q->execute(['c'=>$id]);$c['subject_ids']=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
    return $c;
}

function invigilator_context(PDO $pdo,int $roomId,int $subjectId):array
{
    $q=$pdo->prepare('SELECT r.id AS room_id,r.exam_site_id,s.admission_period_id,es.id AS subject_id,es.exam_date,es.start_time,es.end_time FROM exam_rooms r JOIN exam_sites s ON s.id=r.exam_site_id JOIN exam_subjects es ON es.admission_period_id=s.admission_period_id WHERE r.id=:r AND es.id=:subject FOR UPDATE');
    $q->execute(['r'=>$roomId,'subject'=>$subjectId]);$row=$q->fetch();
    if(!$row)throw new InvalidArgumentException('Phòng thi và môn thi không thuộc cùng kỳ tuyển sinh.');
    return $row;
}

/** Cancel without deleting members or subject history; serialize with assignment writers. */
function council_cancel(PDO $pdo,int $id): int {
    $pdo->beginTransaction();
    try {
        $q=$pdo->prepare('SELECT admission_period_id FROM councils WHERE id=?');$q->execute([$id]);$period=$q->fetchColumn();
        if(!$period)throw new InvalidArgumentException('Không tìm thấy hội đồng.');
        $q=$pdo->prepare('SELECT id FROM admission_periods WHERE id=? FOR UPDATE');$q->execute([$period]);
        if(!$q->fetchColumn())throw new InvalidArgumentException('Kỳ tuyển sinh không tồn tại.');
        $q=$pdo->prepare('SELECT * FROM councils WHERE id=? FOR UPDATE');$q->execute([$id]);$c=$q->fetch();
        if(!$c)throw new InvalidArgumentException('Không tìm thấy hội đồng.');
        if($c['status']==='cancelled')throw new InvalidArgumentException('Hội đồng đã được hủy trước đó.');
        foreach(['exam_paper_grader_assignments','appeal_grader_assignments'] as $table){
            $q=$pdo->prepare("SELECT a.id FROM $table a JOIN council_members cm ON cm.id=a.council_member_id WHERE cm.council_id=? LIMIT 1 FOR UPDATE");$q->execute([$id]);
            if($q->fetchColumn())throw new InvalidArgumentException('Hội đồng đang có phân công chấm thi hoặc phúc khảo. Cần xử lý phân công trước khi hủy.');
        }
        if($c['council_type']==='invigilation'){
            $q=$pdo->prepare('SELECT a.id FROM exam_invigilator_assignments a JOIN exam_rooms r ON r.id=a.exam_room_id JOIN exam_sites es ON es.id=r.exam_site_id JOIN council_members cm ON cm.staff_id=a.staff_id WHERE cm.council_id=? AND es.admission_period_id=? AND es.id=? LIMIT 1 FOR UPDATE');
            $q->execute([$id,$period,$c['exam_site_id']]);
            if($q->fetchColumn())throw new InvalidArgumentException('Thành viên hội đồng đang được phân công coi thi tại điểm thi này. Cần xử lý phân công trước khi hủy.');
        }
        $pdo->prepare('UPDATE councils SET status="cancelled" WHERE id=?')->execute([$id]);
        $pdo->commit();return (int)$period;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
