<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli' || !preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/',getenv('DB_NAME') ?: '')) exit(2);
require_once __DIR__.'/../includes/school_admission_service.php';
require_once __DIR__.'/../includes/candidate_service.php';
require_once __DIR__.'/../includes/appeal_service.php';
require_once __DIR__.'/../includes/admission_service.php';
$pdo=db();$pdo->exec((string)file_get_contents(__DIR__.'/../database/015_admission_period_configuration.sql'));
$checks=0;$assert=static function(bool $ok,string $label)use(&$checks):void { if(!$ok)throw new RuntimeException($label);echo 'PASS TEST ',++$checks,': ',$label,PHP_EOL; };
$reject=static function(callable $op,string $label)use($assert):void {$caught=false;try{$op();}catch(Throwable $e){$caught=true;}$assert($caught,$label);};
$actor=(int)$pdo->query('SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE r.code="so_gdtd" ORDER BY u.id LIMIT 1')->fetchColumn();
$students=$pdo->query('SELECT * FROM candidates ORDER BY id LIMIT 2')->fetchAll();[$a,$b]=$students;
$ids=array_map('intval',$pdo->query('SELECT id FROM schools WHERE level="THPT" AND active=1 ORDER BY id LIMIT 3')->fetchAll(PDO::FETCH_COLUMN));
$data=['name'=>'Mô phỏng cấu hình kỳ TP.HCM','academic_year'=>'2026-2027','mode'=>'simulation'];
$now=new DateTimeImmutable('now');
$timed=$data;
foreach(['registration'=>[-1,1],'adjustment'=>[2,3],'appeal'=>[4,5],'enrollment'=>[6,7]] as $stage=>[$start,$end]) foreach(['start'=>$start,'end'=>$end] as $edge=>$hour) $timed[$stage.'_'.$edge]=$now->modify(($hour>=0?'+':'').$hour.' hours')->format('Y-m-d H:i:s');
$policy=admission_policy();
$assert($policy['max_aspirations']===3 && $policy['bonus_cap']===3.5 && array_sum(array_column($policy['subjects'],'weight'))===3,'HCM annual policy records 3 wishes, 3 subject weights and 3.5 bonus cap');
$score=admission_policy_score(['Ngữ văn'=>8,'Toán'=>9,'Ngoại ngữ'=>8.5],['group_3','group_1'],['bronze_third','gold_first']);
$assert($score['eligible'] && $score['score']===29.0 && $score['bonus']===3.5,'Reference score uses highest benefit of each category and the bonus cap');
$assert(!admission_policy_score(['Ngữ văn'=>8,'Toán'=>0,'Ngoại ngữ'=>8])['eligible'],'Zero exam score makes reference eligibility false');
$assert(!admission_policy_score(['Ngữ văn'=>8])['eligible'],'Missing subject makes reference eligibility false');
$reject(fn()=>admission_policy_score(['Ngữ văn'=>8.1,'Toán'=>8,'Ngoại ngữ'=>8]),'Reference score rejects invalid quarter-point increments');
$reject(fn()=>period_configuration_save($pdo,0,$data,(int)$a['user_id']),'Candidate cannot configure a period');
foreach(['academic_year'=>'2026-2028','mode'=>'unknown','source_url'=>'javascript:alert(1)','registration_start'=>'2026-02-30T08:00'] as $field=>$bad) {
    $invalid=$data;$invalid[$field]=$bad;$reject(fn()=>period_configuration_validate($invalid),'Reject invalid '.$field);
}
$invalid=$timed;$invalid['registration_end']=$invalid['registration_start'];$reject(fn()=>period_configuration_validate($invalid),'Reject equal window endpoints');
$invalid=$timed;$invalid['adjustment_start']=$invalid['registration_start'];$reject(fn()=>period_configuration_validate($invalid),'Reject adjustment before registration ends');
$id=period_configuration_save($pdo,0,$data,$actor);$cfg=period_configuration($pdo,$id);
$assert(admission_period_find($pdo,$id)['status']==='draft','New managed period is always a draft');
$reject(fn()=>period_configuration_transition($pdo,$id,'published',$actor,$cfg['revision']),'Incomplete period cannot be published');
$revision=$cfg['revision'];period_configuration_save($pdo,$id,$timed,$actor,$revision);
$reject(fn()=>period_configuration_save($pdo,$id,$timed,$actor,$revision),'Stale settings revision cannot overwrite another save');
quota_save_all($pdo,$id,[$ids[0]=>200,$ids[1]=>200,$ids[2]=>200]);
$reject(fn()=>quota_save_all($pdo,$id,[$ids[0]=>50],$revision),'Stale quota form cannot overwrite saved settings');
$schoolThcs=(int)$a['middle_school_id'];$before=quota_rows($pdo,$id);
$reject(fn()=>quota_save_all($pdo,$id,[$ids[0]=>99,$schoolThcs=>20]),'THCS quota is rejected atomically');
$assert(quota_rows($pdo,$id)===$before,'Invalid quota batch preserves earlier values');
$assert(count(quota_rows($pdo,$id,'THPT'))===3,'Quota search works with native prepared statements');
$cfg=period_configuration($pdo,$id);period_configuration_transition($pdo,$id,'published',$actor,$cfg['revision']);
$assert(admission_period_find($pdo,$id)['status']==='published','Complete draft transitions to published');
$reject(fn()=>period_configuration_save($pdo,$id,$timed,$actor,period_configuration($pdo,$id)['revision']),'Published configuration is frozen');
$reject(fn()=>quota_save_all($pdo,$id,[$ids[0]=>999]),'Published quotas are frozen');
$reject(fn()=>quota_publish($pdo,$id),'Simulation quotas cannot be published as official');
$assert(!period_publicly_visible($pdo,$id),'Simulation is excluded from public official data');
aspiration_save((int)$a['id'],$id,$ids,(int)$a['user_id']);
$assert(count(aspiration_get((int)$a['id'],$id))===3,'Managed open registration accepts 3 ordered wishes');
$pdo->exec('INSERT INTO schools(school_code,name,level,active) VALUES("PERIOD_SIM4","THPT mô phỏng riêng","THPT",1)');$fourth=(int)$pdo->lastInsertId();
$reject(fn()=>aspiration_save((int)$a['id'],$id,[...$ids,$fourth],(int)$a['user_id']),'Managed period rejects a fourth distinct wish');
$duringAdjustment=$now->modify('+150 minutes');
$assert(period_registration_error($pdo,$id,(int)$a['id'],$duringAdjustment)===null,'Registered student can adjust during phase 2');
$assert(period_registration_error($pdo,$id,(int)$b['id'],$duringAdjustment)!==null,'Phase 2 blocks new registrations');
$assert(period_registration_error($pdo,$id,(int)$a['id'],$now->modify('+90 minutes'))!==null,'Gap between registration and adjustment is locked');
$assert(period_registration_error($pdo,$id,(int)$a['id'],$now->modify('+4 hours'))!==null,'Wishes are locked after adjustment deadline');
period_configuration_transition($pdo,$id,'closed',$actor,period_configuration($pdo,$id)['revision']);
$reject(fn()=>period_configuration_transition($pdo,$id,'published',$actor,period_configuration($pdo,$id)['revision']),'Closed period cannot reopen');
$reject(fn()=>aspiration_save((int)$a['id'],$id,$ids,(int)$a['user_id']),'Closed period blocks wish submissions');
$reject(fn()=>appeal_window_open($pdo,$id),'Closed period blocks appeals');
$official=$timed;$official['mode']='official';$officialId=period_configuration_save($pdo,0,$official,$actor);quota_save_all($pdo,$officialId,[$ids[0]=>100]);
$reject(fn()=>period_configuration_transition($pdo,$officialId,'published',$actor,period_configuration($pdo,$officialId)['revision']),'Official period requires sources and review');
foreach(['source_url','schedule_source_url','quota_source_url','appeal_source_url','enrollment_source_url'] as $field) $official[$field]=$policy['source_urls'][0];
$official['reviewed']='1';period_configuration_save($pdo,$officialId,$official,$actor,period_configuration($pdo,$officialId)['revision']);
period_configuration_transition($pdo,$officialId,'published',$actor,period_configuration($pdo,$officialId)['revision']);quota_publish($pdo,$officialId);
$assert(period_publicly_visible($pdo,$officialId),'Reviewed official published period may expose quotas');
$assert((int)$pdo->query('SELECT COUNT(*) FROM admission_quotas WHERE admission_period_id='.$officialId.' AND published_at IS NOT NULL')->fetchColumn()===1,'Quota publication is explicit and period-specific');
$assert(count(array_filter(admission_check_prerequisites($pdo,$officialId),fn($e)=>str_contains($e,'chỉ dùng mô phỏng')))>0,'Uncertified calculation cannot run as official selection');
$assert((int)$pdo->query('SELECT COUNT(*) FROM admission_period_events WHERE admission_period_id='.$id)->fetchColumn()>=4,'Configuration and state changes have an audit trail');
period_configuration_transition($pdo,$officialId,'closed',$actor,period_configuration($pdo,$officialId)['revision']);

// Actual controller checks on a local server configured exclusively for this restored database.
$temp=sys_get_temp_dir().'/ts10_period_'.bin2hex(random_bytes(8));mkdir($temp,0700,true);$server=null;
try {
    $socket=stream_socket_server('tcp://127.0.0.1:0',$err,$message);$address=stream_socket_get_name($socket,false);fclose($socket);
    $server=proc_open([PHP_BINARY,'-d','session.save_path='.$temp,'-S',$address,'router.php'],[0=>['pipe','r'],1=>['file',$temp.'/server.log','w'],2=>['redirect',1]],$pipes,dirname(__DIR__),getenv());fclose($pipes[0]);
    $session=static function(int $uid)use($pdo,$temp):array {
        $q=$pdo->prepare('SELECT * FROM users WHERE id=?');$q->execute([$uid]);$u=$q->fetch();
        $q=$pdo->prepare('SELECT r.code FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=? AND r.active=1');$q->execute([$uid]);$roles=$q->fetchAll(PDO::FETCH_COLUMN);
        $sid=bin2hex(random_bytes(20));$csrf=bin2hex(random_bytes(20));$data=['auth'=>['id'=>$uid,'user_id'=>$uid,'username'=>$u['username'],'full_name'=>$u['full_name'],'roles'=>$roles,'role'=>$roles[0]],'csrf_token'=>$csrf];$serialized='';foreach($data as $key=>$value)$serialized.=$key.'|'.serialize($value);file_put_contents($temp.'/sess_'.$sid,$serialized);return ['id'=>$sid,'csrf'=>$csrf];
    };
    $http=static function(string $path,?array $session=null,?array $post=null)use($address):array {
        $ch=curl_init('http://'.$address.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>15]);if($session)curl_setopt($ch,CURLOPT_COOKIE,'PHPSESSID='.$session['id']);
        if($post!==null){curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($post));}
        $raw=curl_exec($ch);if($raw===false)throw new RuntimeException(curl_error($ch));$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$size=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);curl_close($ch);return ['status'=>$code,'body'=>substr($raw,$size),'headers'=>substr($raw,0,$size)];
    };
    for($i=0;$i<30;$i++){ $ready=@fsockopen('127.0.0.1',(int)substr(strrchr($address,':'),1));if($ready){fclose($ready);break;}usleep(100000); }
    $admin=$session($actor);$student=$session((int)$a['user_id']);
    $page=$http('/admission/create.php',$admin);$assert($page['status']===200 && str_contains($page['body'],'adjustment_start') && str_contains($page['body'],'enrollment_end'),'HTTP create form exposes all four configured phases');
    $assert($http('/admission/manage.php?id='.$id,$student)['status']===403,'Student cannot access period management');
    $page=$http('/admission/create.php',$admin,['csrf_token'=>'bad',...$data]);$assert($page['status']===400,'HTTP rejects missing or invalid CSRF');
    $page=$http('/admission/create.php',$admin,['csrf_token'=>$admin['csrf'],'action'=>'save',...$data]);
    $assert($page['status']===302 && preg_match('/id=([0-9]+)/',$page['headers'],$m)===1,'HTTP creates a draft and redirects to its configuration');$httpId=(int)$m[1];
    $page=$http('/admission/manage.php?id='.$httpId,$admin);
    $assert($page['status']===200 && !preg_match('/Warning:|Fatal error/',$page['body']) && str_contains($page['body'],'MÔ PHỎNG'),'Saved HTTP draft shows simulation label without runtime errors');
    $page=$http('/admission/manage.php?id='.$httpId,$admin,['csrf_token'=>$admin['csrf'],'action'=>'published','revision'=>period_configuration($pdo,$httpId)['revision']]);
    $assert($page['status']===200 && str_contains($page['body'],'Chưa đủ thời hạn'),'HTTP publish shows prerequisite errors');
    $page=$http('/admission/manage.php?id='.$httpId,$admin,['csrf_token'=>$admin['csrf'],'action'=>'save','revision'=>period_configuration($pdo,$httpId)['revision'],...$timed]);
    $assert($page['status']===302,'HTTP saves complete draft windows');
    $page=$http('/quotas/index.php?period_id='.$httpId,$admin,['csrf_token'=>$admin['csrf'],'quota'=>[$ids[0]=>100],'configuration_revision'=>period_configuration($pdo,$httpId)['revision']]);
    $assert($page['status']===200 && str_contains($page['body'],'Lưu chỉ tiêu tuyển sinh thành công.'),'HTTP quota form saves a school quota with its revision');
    $page=$http('/admission/manage.php?id='.$httpId,$admin,['csrf_token'=>$admin['csrf'],'action'=>'published','revision'=>period_configuration($pdo,$httpId)['revision']]);
    $assert($page['status']===302 && admission_period_find($pdo,$httpId)['status']==='published','HTTP publishes the complete simulation period');
    $page=$http('/quotas/index.php?period_id='.$httpId,$admin,['csrf_token'=>$admin['csrf'],'quota'=>[$ids[0]=>999],'configuration_revision'=>period_configuration($pdo,$httpId)['revision']]);
    $assert(str_contains($page['body'],'Chỉ sửa chỉ tiêu khi kỳ còn là bản nháp.'),'HTTP refuses quota edits after publication');
    $page=$http('/admission/manage.php?id='.$httpId,$admin,['csrf_token'=>$admin['csrf'],'action'=>'closed','revision'=>period_configuration($pdo,$httpId)['revision']]);
    $assert($page['status']===302 && admission_period_find($pdo,$httpId)['status']==='closed','HTTP closes a published period');
    $pdo->prepare('UPDATE admission_quotas SET published_at=NOW() WHERE admission_period_id=?')->execute([$httpId]);
    $publicItems=require __DIR__.'/../data/quotas_published.php';
    $assert(count($publicItems)===1 && str_contains($publicItems[0]['detail_url'],'period_id='.$officialId),'Homepage excludes simulation even if a publish timestamp is set');
    $page=$http('/quotas/public.php?period_id='.$id);
    $assert(!str_contains($page['body'],'THPT_NGUYEN_THI_MINH_KHAI'),'Public quota route hides simulation values');
    $page=$http('/quotas/public.php?period_id='.$officialId);
    $assert($page['status']===200 && str_contains($page['body'],'THPT_NGUYEN_THI_MINH_KHAI'),'Public quota route exposes only explicitly published official values');
    $page=$http('/admission/rules.php');$assert($page['status']===200 && str_contains($page['body'],'1700/QĐ-UBND'),'Public policy reference includes primary-source attribution');
    echo 'COMPLETE: ',$checks,' annual policy and period configuration checks passed.',PHP_EOL;
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}foreach(glob($temp.'/*') ?: [] as $file)if(is_file($file))unlink($file);if(is_dir($temp))rmdir($temp);
}
