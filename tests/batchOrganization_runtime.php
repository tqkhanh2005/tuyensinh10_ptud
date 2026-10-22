<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli' || !preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/',getenv('DB_NAME') ?: ''))exit(2);
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__.'/../includes/exam_service.php';
require_once __DIR__.'/../includes/council_service.php';
require_once __DIR__.'/../includes/school_admission_service.php';
$pdo=db();$pdo->exec((string)file_get_contents(__DIR__.'/../database/016_exam_organization.sql'));
$checks=0;$assert=static function(bool $ok,string $label)use(&$checks):void{if(!$ok)throw new RuntimeException($label);echo 'PASS TEST ',++$checks,': ',$label,PHP_EOL;};
$reject=static function(callable $fn,string $label)use($assert):void{$caught=false;try{$fn();}catch(Throwable $e){$caught=true;}$assert($caught,$label);};
$actor=(int)$pdo->query('SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE r.code="so_gdtd" ORDER BY u.id LIMIT 1')->fetchColumn();
$students=$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll();$a=$students[0];$b=$students[1];
$profiles=$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll();
$schools=$pdo->query('SELECT * FROM schools WHERE school_code IN ("THPT_NGUYEN_THI_MINH_KHAI","THPT_TRAN_PHU","THPT_MARIE_CURIE") ORDER BY id')->fetchAll();
$data=['name'=>'[MÔ PHỎNG] Tổ chức thi TP.HCM','academic_year'=>'2026-2027','mode'=>'simulation'];$now=new DateTimeImmutable('now');
foreach(['registration'=>[-96,-72],'adjustment'=>[-48,-24],'appeal'=>[120,144],'enrollment'=>[168,192]] as $stage=>[$start,$end])foreach(['start'=>$start,'end'=>$end] as $edge=>$hour)$data[$stage.'_'.$edge]=$now->modify(($hour>=0?'+':'').$hour.' hours')->format('Y-m-d H:i:s');
$period=period_configuration_save($pdo,0,$data,$actor);quota_save_all($pdo,$period,[$schools[0]['id']=>240,$schools[1]['id']=>240,$schools[2]['id']=>240]);
$insert=$pdo->prepare('INSERT INTO aspirations(candidate_id,admission_period_id,school_id,priority_order) VALUES(?,?,?,?)');foreach($students as $student)foreach($schools as $i=>$school)$insert->execute([$student['id'],$period,$school['id'],$i+1]);
period_configuration_transition($pdo,$period,'published',$actor,period_configuration($pdo,$period)['revision']);
$assert(count($students)===720,'The complete 720-student cohort is used');
$reject(fn()=>exam_cards_release($pdo,$period,$actor),'Incomplete organization cannot release cards');
$siteIds=[];$roomIds=[];
foreach(array_slice($schools,0,2) as $i=>$school){
    $siteIds[]=create_exam_site($pdo,['period_id'=>$period,'school_id'=>$school['id'],'site_code'=>'HCM'.($i+1),'name'=>$school['name'],'address'=>$school['address'],'capacity'=>360]);
    for($j=1;$j<=9;$j++)$roomIds[]=create_exam_room($pdo,['site_id'=>$siteIds[$i],'room_code'=>sprintf('P%02d',$j),'name'=>'Phòng '.$j,'capacity'=>40]);
}
$assert(count($roomIds)===18,'Two HCM sites have eighteen rooms, capacity forty each');
$reject(fn()=>create_exam_room($pdo,['site_id'=>$siteIds[0],'room_code'=>'EXTRA','name'=>'Thừa','capacity'=>1]),'Room capacity cannot exceed site capacity');
$reject(fn()=>create_exam_site($pdo,['period_id'=>$period,'site_code'=>'BAD','name'=>'Không hợp lệ','capacity'=>'12.5']),'Fractional capacity is rejected');
$pdo->prepare('UPDATE exam_rooms SET capacity=39 WHERE id=?')->execute([$roomIds[17]]);
$reject(fn()=>exam_organization_allocate_all($pdo,$period),'Insufficient room capacity rejects the whole automatic assignment');
$assert((int)$pdo->query('SELECT COUNT(*) FROM exam_allocations WHERE admission_period_id='.$period)->fetchColumn()===0,'Failure rolls back even site assignments made earlier in the same operation');
$pdo->prepare('UPDATE exam_rooms SET capacity=40 WHERE id=?')->execute([$roomIds[17]]);
exam_organization_allocate_all($pdo,$period);exam_organization_allocate_all($pdo,$period);
$assert(count(exam_roster($pdo,$period))===720,'Automatic assignment seats all 720 and can be repeated without duplicates');
$assert((int)$pdo->query('SELECT MAX(n) FROM (SELECT COUNT(*) n FROM room_assignments WHERE admission_period_id='.$period.' GROUP BY exam_room_id) x')->fetchColumn()===40,'Every room stays within capacity');
$assert(exam_number_generate($pdo,$period,'SIM26')===720,'All 720 students receive a period-specific number');
$assert(exam_number_generate($pdo,$period,'SIM26')===0,'Repeated number generation preserves issued numbers');
$reject(fn()=>exam_number_generate($pdo,$period,'OTHER'),'Issued prefix cannot be changed');
$assert(count(array_unique(array_column(exam_roster($pdo,$period),'exam_number')))===720,'Every exam number in the period is unique');
$assert(exam_card_for_user($pdo,(int)$a['user_id'],$period)===null,'Unreleased card is hidden from its owner');
$date=$now->modify('+1 day')->format('Y-m-d');$date2=$now->modify('+2 days')->format('Y-m-d');
$subjects=[];
$reject(fn()=>exam_schedule_save($pdo,$period,['subject_code'=>'VAN','exam_date'=>$now->modify('-3 days')->format('Y-m-d'),'start_time'=>'08:00'],$actor),'Exam before adjustment deadline is rejected');
$reject(fn()=>exam_schedule_save($pdo,$period,['subject_code'=>'VAN','exam_date'=>$now->modify('+6 days')->format('Y-m-d'),'start_time'=>'08:00'],$actor),'Exam during appeal phase is rejected');
$subjects[]=exam_schedule_save($pdo,$period,['subject_code'=>'VAN','exam_date'=>$date,'start_time'=>'08:00'],$actor);
$reject(fn()=>exam_schedule_save($pdo,$period,['subject_code'=>'NN','exam_date'=>$date,'start_time'=>'09:00'],$actor),'Overlapping subject schedules are rejected');
$reject(fn()=>exam_schedule_save($pdo,$period,['subject_code'=>'NN','exam_date'=>'2026-02-30','start_time'=>'08:00'],$actor),'Invalid calendar dates are rejected');
$reject(fn()=>exam_schedule_save($pdo,$period,['subject_code'=>'NN','exam_date'=>$date,'start_time'=>'23:30'],$actor),'An exam cannot roll over midnight');
$subjects[]=exam_schedule_save($pdo,$period,['subject_code'=>'NN','exam_date'=>$date,'start_time'=>'14:00'],$actor);
$subjects[]=exam_schedule_save($pdo,$period,['subject_code'=>'TOAN','exam_date'=>$date2,'start_time'=>'08:00'],$actor);
$staff=[];$insert=$pdo->prepare('INSERT INTO staff(full_name,workplace,staff_role,active) VALUES(?,?,"Giám thị",1)');
for($i=0;$i<36;$i++){$insert->execute(['Giám thị mô phỏng '.($i+1),'TP.HCM']);$staff[]=(int)$pdo->lastInsertId();}
foreach($siteIds as $i=>$site){$members=array_map(static fn($id)=>['staff_id'=>$id,'role_name'=>'Thành viên','task_name'=>'Coi thi'],array_slice($staff,$i*18,18));council_create($pdo,['admission_period_id'=>$period,'council_type'=>'invigilation','name'=>'Hội đồng mô phỏng '.($i+1),'exam_site_id'=>$site,'members'=>$members]);}
invigilator_assign($pdo,$roomIds[0],$subjects[0],$staff[0],$staff[1]);
$reject(fn()=>invigilator_assign($pdo,$roomIds[1],$subjects[0],$staff[0],$staff[2]),'A teacher cannot invigilate two rooms at the same time');
$reject(fn()=>invigilator_assign($pdo,$roomIds[1],$subjects[0],$staff[2],$staff[2]),'A room needs two distinct teachers');
$reject(fn()=>exam_schedule_save($pdo,$period,['subject_code'=>'VAN','exam_date'=>$date2,'start_time'=>'14:00'],$actor),'A schedule with assigned teachers cannot be silently changed');
$reject(fn()=>exam_cards_release($pdo,$period,$actor),'Missing invigilators prevent release');
foreach($roomIds as $i=>$room)foreach($subjects as $j=>$subject){if($i===0 && $j===0)continue;invigilator_assign($pdo,$room,$subject,$staff[$i*2],$staff[$i*2+1]);}
$assert((int)$pdo->query('SELECT COUNT(*) FROM exam_invigilator_assignments')->fetchColumn()===108,'Eighteen rooms times three subjects have 108 invigilator positions');
$pdo->prepare('UPDATE staff SET active=0 WHERE id=?')->execute([$staff[0]]);
$assert((bool)exam_organization_readiness($pdo,$period)['errors'],'Inactive invigilator prevents readiness');
$pdo->prepare('UPDATE staff SET active=1 WHERE id=?')->execute([$staff[0]]);
$membership=$pdo->prepare('SELECT * FROM council_members WHERE staff_id=? LIMIT 1');$membership->execute([$staff[0]]);$membership=$membership->fetch();
$pdo->prepare('DELETE FROM council_members WHERE id=?')->execute([$membership['id']]);
$assert((bool)exam_organization_readiness($pdo,$period)['errors'],'An invigilator outside the site council prevents readiness');
$pdo->prepare('INSERT INTO council_members(council_id,staff_id,role_name,task_name) VALUES(?,?,?,?)')->execute([$membership['council_id'],$staff[0],$membership['role_name'],$membership['task_name']]);
$ready=exam_organization_readiness($pdo,$period);$assert(!$ready['errors'],'All schedule, room, number and council checks pass: '.implode(' ',$ready['errors']));
exam_cards_release($pdo,$period,$actor);
$assert(!empty(exam_organization_state($pdo,$period)['released_at']),'Release records its timestamp');
$card=exam_card_for_user($pdo,(int)$a['user_id'],$period);
$assert($card && $card['id']==$a['id'] && count($card['schedule'])===3 && $card['exam_site_address'],'Owner receives the correct complete card for this period');
$assert(exam_card_for_user($pdo,0,$period)===null,'Unknown users receive no student card');
$reject(fn()=>exam_organization_allocate_all($pdo,$period),'Release freezes automatic allocation');
$reject(fn()=>save_allocations($pdo,$period,[]),'Release freezes the existing manual allocation writer');
$reject(fn()=>save_room_assignments($pdo,$period,$siteIds[0],[]),'Release freezes the existing manual room writer');
$reject(fn()=>exam_number_generate($pdo,$period,'SIM26'),'Release freezes exam numbers');
$reject(fn()=>exam_schedule_save($pdo,$period,['subject_code'=>'NN','exam_date'=>$date,'start_time'=>'14:00'],$actor),'Release freezes subject schedule changes');
$reject(fn()=>create_exam_site($pdo,['period_id'=>$period,'site_code'=>'NEW','name'=>'Điểm mới','capacity'=>40]),'Release freezes site creation');
$reject(fn()=>create_exam_room($pdo,['site_id'=>$siteIds[0],'room_code'=>'NEW','name'=>'Phòng mới','capacity'=>1]),'Release freezes room creation');
$assignment=(int)$pdo->query('SELECT MIN(id) FROM exam_invigilator_assignments')->fetchColumn();
$reject(fn()=>invigilator_replace($pdo,$assignment,$staff[3]),'Release freezes teacher replacements');
$assert($profiles===$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll(),'Organization leaves all candidate profile fields unchanged');

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
    $page=$http('/exam/organization.php?period_id='.$period,$admin);
    $assert($page['status']===200 && !preg_match('/Warning:|Fatal error/',$page['body']) && str_contains($page['body'],'MÔ PHỎNG'),'Organization page renders the full released simulation without PHP errors');
    $assert(substr_count($page['body'],'SIM26')===51 && str_contains($page['body'],'Trang 1 / 15'),'Roster pagination limits the first page to fifty students');
    $assert($http('/exam/organization.php?period_id='.$period,$student)['status']===403,'Students cannot manage organization');
    $assert($http('/exam/roster.php?period_id='.$period,$student)['status']===403,'Students cannot download the full roster');
    $page=$http('/exam/card.php?period_id='.$period,$student);
    $assert($page['status']===200 && str_contains($page['body'],$card['exam_number']) && str_contains($page['body'],'MÔ PHỎNG') && str_contains($page['body'],'window.print()'),'Student can print their released simulation card');
    $page=$http('/',$student);
    $assert($page['status']===200 && str_contains($page['body'],$card['exam_number']) && str_contains($page['body'],'MÔ PHỎNG'),'Candidate homepage shows the released period number and simulation label');
    $assert($http('/exam/card.php?period_id='.$period.'&candidate_id='.$b['id'],$student)['status']===403,'Card endpoint rejects another candidate identifier');
    $assert($http('/exam/card.php?period_id=375',$student)['status']===404,'Card endpoint cannot mix the released period with a different draft');
    $page=$http('/exam/roster.php?period_id='.$period,$admin);
    $lines=array_values(array_filter(explode("\n",trim($page['body']))));
    $assert($page['status']===200 && count($lines)===721 && str_contains($page['headers'],'text/csv'),'Admin CSV has one header and all 720 students');
    $assert(str_contains($page['body'],'MÔ PHỎNG'),'CSV explicitly identifies simulated data');
    $assert($http('/exam/organization.php?period_id='.$period,$admin,['csrf_token'=>'bad','action'=>'number'])['status']===400,'Organization endpoint rejects bad CSRF');
    $page=$http('/exam/organization.php?period_id=375',$admin,['csrf_token'=>$admin['csrf'],'action'=>'schedule','subject_code'=>'VAN','exam_date'=>$date,'start_time'=>'08:00']);
    $assert($page['status']===302,'HTTP saves a valid draft schedule and redirects');
    $page=$http('/exam/organization.php?period_id='.$period,$admin,['csrf_token'=>$admin['csrf'],'action'=>'number','prefix'=>'SIM26']);
    $assert($page['status']===200 && str_contains($page['body'],'được khóa'),'HTTP writer explains the release lock');
    $page=$http('/admission/manage.php?id='.$period,$admin);
    $assert($page['status']===200 && !preg_match('/Warning:|Fatal error/',$page['body']),'New exam audit events render in the period history');
    echo 'COMPLETE: ',$checks,' organization checks passed.',PHP_EOL;
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}foreach(glob($temp.'/*') ?: [] as $file)if(is_file($file))unlink($file);if(is_dir($temp))rmdir($temp);
}
