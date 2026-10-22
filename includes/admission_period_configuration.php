<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';

function admission_policy(): array {
    return json_decode((string)file_get_contents(__DIR__.'/../data/admission_policy_hcm_2026.json'),true,512,JSON_THROW_ON_ERROR);
}
/** Reference score only; eligibility evidence and official selection require separate review. */
function admission_policy_score(array $scores,array $priorityGroups=[],array $awards=[]): array {
    $policy=admission_policy();$total=0.0;$eligible=true;
    foreach($policy['subjects'] as $subject=>$rule) {
        if(!array_key_exists($subject,$scores)) return ['eligible'=>false,'score'=>null,'reason'=>'Thiếu môn thi.'];
        $value=$scores[$subject];
        if(!is_scalar($value) || !is_numeric($value) || !is_finite((float)$value) || (float)$value<0 || (float)$value>10 || abs((float)$value*4-round((float)$value*4))>0.00001) throw new InvalidArgumentException('Điểm môn phải trong thang 10 và theo bước 0,25.');
        $total+=(float)$value*$rule['weight']; if((float)$value<=0)$eligible=false;
    }
    $bonus=static function(array $keys,array $map):float {
        $values=[0.0];foreach($keys as $key){if(!is_string($key) || !array_key_exists($key,$map))throw new InvalidArgumentException('Nhóm ưu tiên hoặc giải không hợp lệ.');$values[]=(float)$map[$key];}return max($values);
    };
    $extra=min($policy['bonus_cap'],$bonus($priorityGroups,$policy['priority_groups'])+$bonus($awards,$policy['encouragement_awards']));
    return ['eligible'=>$eligible,'score'=>round($total+$extra,2),'bonus'=>$extra];
}
function period_configuration(PDO $pdo,int $id): ?array {
    static $available=[];
    $schema=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!array_key_exists($schema,$available)) {
        $q=$pdo->query("SHOW TABLES LIKE 'admission_period_configs'"); $available[$schema]=(bool)$q->fetchColumn();
    }
    if (!$available[$schema]) return null;
    $q=$pdo->prepare('SELECT * FROM admission_period_configs WHERE admission_period_id=?'); $q->execute([$id]); $row=$q->fetch();
    if (!$row) return null;
    return ['settings'=>json_decode($row['settings'],true,512,JSON_THROW_ON_ERROR),'revision'=>(int)$row['revision']];
}
function period_datetime(string $value): ?string {
    if ($value==='') return null;
    foreach (['!Y-m-d\TH:i','!Y-m-d H:i:s'] as $format) {
        $d=DateTimeImmutable::createFromFormat($format,$value,new DateTimeZone('Asia/Ho_Chi_Minh'));
        if ($d && $d->format(str_replace('!','',$format))===$value) return $d->format('Y-m-d H:i:s');
    }
    throw new InvalidArgumentException('Ngày giờ không hợp lệ: '.$value);
}
function period_configuration_validate(array $input): array {
    $out=[];
    foreach (['name','academic_year','plan_content','mode','source_url','schedule_source_url','quota_source_url','appeal_source_url','enrollment_source_url'] as $field) {
        if (isset($input[$field]) && !is_scalar($input[$field])) throw new InvalidArgumentException('Dữ liệu trường không hợp lệ.');
        $out[$field]=trim((string)($input[$field] ?? ''));
    }
    if ($out['name']==='' || mb_strlen($out['name'])>150) throw new InvalidArgumentException('Tên kỳ bắt buộc và tối đa 150 ký tự.');
    if (!preg_match('/^(20[0-9]{2})-(20[0-9]{2})$/',$out['academic_year'],$m) || (int)$m[2] !== (int)$m[1]+1) throw new InvalidArgumentException('Năm học phải gồm hai năm liên tiếp, ví dụ 2026-2027.');
    if (!in_array($out['mode'],['simulation','official'],true)) throw new InvalidArgumentException('Chọn chế độ mô phỏng hoặc chính thức.');
    foreach (['source_url','schedule_source_url','quota_source_url','appeal_source_url','enrollment_source_url'] as $field) {
        if ($out[$field]!=='' && (!filter_var($out[$field],FILTER_VALIDATE_URL) || parse_url($out[$field],PHP_URL_SCHEME)!=='https' || parse_url($out[$field],PHP_URL_USER)!==null)) throw new InvalidArgumentException('Nguồn văn bản phải là đường dẫn HTTPS hợp lệ.');
    }
    $stageLabels=['registration'=>'đăng ký ban đầu','adjustment'=>'điều chỉnh nguyện vọng','appeal'=>'phúc khảo','enrollment'=>'nhập học'];
    foreach ($stageLabels as $stage=>$stageLabel) {
        foreach (['start','end'] as $edge) {
            $field=$stage.'_'.$edge;
            if (isset($input[$field]) && !is_scalar($input[$field])) throw new InvalidArgumentException('Ngày giờ không hợp lệ.');
            $out[$field]=period_datetime(trim((string)($input[$field] ?? '')));
        }
        if (($out[$stage.'_start']===null)!==($out[$stage.'_end']===null)) throw new InvalidArgumentException('Cần đủ thời gian bắt đầu và kết thúc: '.$stageLabel);
        if ($out[$stage.'_start'] && $out[$stage.'_start'] >= $out[$stage.'_end']) throw new InvalidArgumentException('Thời gian kết thúc phải sau bắt đầu: '.$stageLabel);
    }
    foreach (['adjustment','appeal','enrollment'] as $stage) if ($out[$stage.'_start'] && (!$out['registration_end'] || $out[$stage.'_start'] <= $out['registration_end'])) throw new InvalidArgumentException('Giai đoạn '.$stageLabels[$stage].' phải sau thời hạn đăng ký ban đầu.');
    $out['policy_id']=admission_policy()['id'];
    $out['reviewed']=in_array($input['reviewed'] ?? false,[true,1,'1'],true);
    return $out;
}
function period_manager(PDO $pdo,int $actor): void {
    $q=$pdo->prepare('SELECT 1 FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id AND r.active=1 JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id WHERE u.id=? AND u.active=1 AND p.code="admission.manage" LIMIT 1');
    $q->execute([$actor]); if (!$q->fetchColumn()) throw new RuntimeException('Không có quyền thiết lập kỳ tuyển sinh.');
}
function period_event(PDO $pdo,int $id,int $actor,string $action,array $detail): void {
    $pdo->prepare('INSERT INTO admission_period_events(admission_period_id,actor_user_id,action,detail) VALUES(?,?,?,?)')->execute([$id,$actor,$action,json_encode($detail,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
}
function period_configuration_save(PDO $pdo,int $id,array $input,int $actor,int $revision=0): int {
    period_manager($pdo,$actor); $data=period_configuration_validate($input); $pdo->beginTransaction();
    try {
        if ($id) {
            $q=$pdo->prepare('SELECT status FROM admission_periods WHERE id=? FOR UPDATE'); $q->execute([$id]);
            if ($q->fetchColumn()!=='draft') throw new RuntimeException('Chỉ sửa được kỳ ở trạng thái bản nháp.');
            $cfg=period_configuration($pdo,$id);
            if (!$cfg || $cfg['revision']!==$revision) throw new RuntimeException('Dữ liệu đã thay đổi. Vui lòng tải lại trước khi lưu.');
        }
        $values=[$data['name'],$data['academic_year'],'Thi tuyển lớp 10 thường',$data['plan_content'],$data['registration_start'],$data['registration_end'],$data['enrollment_start'],$data['enrollment_end']];
        if ($id) $pdo->prepare('UPDATE admission_periods SET name=?,academic_year=?,admission_method=?,plan_content=?,registration_start=?,registration_end=?,enrollment_start=?,enrollment_end=? WHERE id=?')->execute([...$values,$id]);
        else { $pdo->prepare('INSERT INTO admission_periods(name,academic_year,admission_method,plan_content,registration_start,registration_end,enrollment_start,enrollment_end,status) VALUES(?,?,?,?,?,?,?,?,"draft")')->execute($values); $id=(int)$pdo->lastInsertId(); }
        $settings=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $pdo->prepare('INSERT INTO admission_period_configs(admission_period_id,settings,revision,updated_by) VALUES(?,?,1,?) ON DUPLICATE KEY UPDATE settings=VALUES(settings),revision=revision+1,updated_by=VALUES(updated_by)')->execute([$id,$settings,$actor]);
        // Draft window stays closed until publication. Existing payment requirements are preserved.
        if ($data['appeal_start']) $pdo->prepare('INSERT INTO appeal_windows(admission_period_id,start_at,end_at,status) VALUES(?,?,?,"draft") ON DUPLICATE KEY UPDATE start_at=VALUES(start_at),end_at=VALUES(end_at),status="draft"')->execute([$id,$data['appeal_start'],$data['appeal_end']]);
        else $pdo->prepare('DELETE FROM appeal_windows WHERE admission_period_id=?')->execute([$id]);
        period_event($pdo,$id,$actor,'save_draft',['settings'=>$data]); $pdo->commit(); return $id;
    } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
}
function period_publication_errors(PDO $pdo,int $id,array $data): array {
    $errors=[];
    foreach(['registration'=>'đăng ký ban đầu','adjustment'=>'điều chỉnh','appeal'=>'phúc khảo','enrollment'=>'nhập học'] as $stage=>$label) if (empty($data[$stage.'_start']) || empty($data[$stage.'_end'])) $errors[]='Chưa đủ thời hạn '.$label.'.';
    $q=$pdo->prepare('SELECT COUNT(*) FROM admission_quotas aq JOIN schools s ON s.id=aq.school_id AND s.active=1 AND s.level="THPT" WHERE aq.admission_period_id=? AND aq.status="set" AND aq.quota>0'); $q->execute([$id]);
    if (!(int)$q->fetchColumn()) $errors[]='Chưa có trường THPT được thiết lập chỉ tiêu.';
    if ($data['mode']==='official') {
        if ($data['academic_year']!==admission_policy()['academic_year']) $errors[]='Bộ quy tắc đã đối chiếu chỉ áp dụng năm 2026-2027.';
        foreach(['source_url'=>'kế hoạch','schedule_source_url'=>'lịch đăng ký','quota_source_url'=>'chỉ tiêu','appeal_source_url'=>'phúc khảo','enrollment_source_url'=>'nhập học'] as $field=>$label) if (empty($data[$field])) $errors[]='Thiếu nguồn văn bản: '.$label.'.';
        if (empty($data['reviewed'])) $errors[]='Chưa xác nhận đối chiếu văn bản và chỉ tiêu chính thức.';
    }
    return $errors;
}
function period_configuration_transition(PDO $pdo,int $id,string $target,int $actor,int $revision): void {
    period_manager($pdo,$actor);
    $lockName='ts10_period_state_'.substr(hash('sha256',(string)$pdo->query('SELECT DATABASE()')->fetchColumn()),0,32);
    $lock=$pdo->prepare('SELECT GET_LOCK(?,5)');$lock->execute([$lockName]);
    if((int)$lock->fetchColumn()!==1) throw new RuntimeException('Kỳ đang được cập nhật. Vui lòng thử lại.');
    $pdo->beginTransaction();
    try {
        $q=$pdo->prepare('SELECT * FROM admission_periods WHERE id=? FOR UPDATE'); $q->execute([$id]); $period=$q->fetch();
        $cfg=period_configuration($pdo,$id);
        if (!$period || !$cfg || $cfg['revision']!==$revision) throw new RuntimeException('Kỳ không tồn tại hoặc dữ liệu đã thay đổi. Vui lòng tải lại.');
        if (!(($period['status']==='draft' && $target==='published') || ($period['status']==='published' && $target==='closed'))) throw new RuntimeException('Chỉ chuyển Bản nháp → Công bố → Đóng.');
        if ($target==='published') {
            $errors=period_publication_errors($pdo,$id,$cfg['settings']);
            if ($errors) throw new InvalidArgumentException(implode(' ',$errors));
            $q=$pdo->prepare('SELECT id FROM admission_periods WHERE status="published" AND id<>? FOR UPDATE'); $q->execute([$id]);
            if ($q->fetchColumn()) throw new RuntimeException('Cần đóng kỳ đang công bố trước khi mở kỳ khác.');
        }
        $pdo->prepare('UPDATE admission_periods SET status=? WHERE id=?')->execute([$target,$id]);
        $pdo->prepare('UPDATE admission_period_configs SET revision=revision+1,updated_by=? WHERE admission_period_id=?')->execute([$actor,$id]);
        $pdo->prepare('UPDATE appeal_windows SET status=? WHERE admission_period_id=?')->execute([$target==='published'?'open':'closed',$id]);
        period_event($pdo,$id,$actor,$target,['previous'=>$period['status']]); $pdo->commit();
    } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
    finally { $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]); }
}
function period_registration_error(PDO $pdo,int $id,int $candidateId,?DateTimeImmutable $now=null): ?string {
    $cfg=period_configuration($pdo,$id); if(!$cfg) return null;
    $now=$now ?? new DateTimeImmutable('now'); $d=$cfg['settings'];
    $q=$pdo->prepare('SELECT status FROM admission_periods WHERE id=?');$q->execute([$id]);
    if($q->fetchColumn()!=='published') return 'Kỳ tuyển sinh chưa mở hoặc đã đóng.';
    $in=static fn(string $stage):bool => !empty($d[$stage.'_start']) && !empty($d[$stage.'_end']) && $now>=new DateTimeImmutable($d[$stage.'_start']) && $now<=new DateTimeImmutable($d[$stage.'_end']);
    if($in('registration')) return null;
    if($in('adjustment')) {
        $q=$pdo->prepare('SELECT 1 FROM aspirations WHERE admission_period_id=? AND candidate_id=? LIMIT 1');$q->execute([$id,$candidateId]);
        return $q->fetchColumn()?null:'Giai đoạn điều chỉnh chỉ dành cho học sinh đã đăng ký, không tiếp nhận đăng ký mới.';
    }
    return 'Ngoài thời hạn đăng ký hoặc điều chỉnh nguyện vọng.';
}
function period_publicly_visible(PDO $pdo,int $id): bool {
    $cfg=period_configuration($pdo,$id);
    if(!$cfg || $cfg['settings']['mode']!=='official') return false;
    $q=$pdo->prepare('SELECT status FROM admission_periods WHERE id=?'); $q->execute([$id]);
    return in_array($q->fetchColumn(),['published','closed'],true);
}
