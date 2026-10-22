<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli' || !preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/',getenv('DB_NAME') ?: ''))exit(2);
require __DIR__.'/batchGrading_runtime.php';
$pdo->exec((string)file_get_contents(__DIR__.'/../database/018_admission_selection.sql'));
// Independent small case: one school receives both NV1 and NV2 candidates.
$small=['policy'=>admission_policy(),'subjects'=>[['id'=>1,'name'=>'Ngữ văn'],['id'=>2,'name'=>'Toán'],['id'=>3,'name'=>'Ngoại ngữ']],'bonus_reviews'=>[],'quotas'=>[['school_id'=>1,'quota'=>2,'status'=>'set','active'=>1,'level'=>'THPT','name'=>'Trường A'],['school_id'=>2,'quota'=>2,'status'=>'set','active'=>1,'level'=>'THPT','name'=>'Trường B']],'candidates'=>[],'papers'=>[],'aspirations'=>[]];
$marks=[[6.5,6.5,7],[8.5,8.5,9],[8,8.5,8.5],[8,8,8],[7.5,7.5,8]];
foreach($marks as $i=>$scores){$id=$i+1;$small['candidates'][]=['id'=>$id,'full_name'=>'Mô phỏng '.$id,'exam_number'=>'S'.$id,'status'=>'active','eligible'=>1,'user_active'=>1,'legacy_bonus'=>0];foreach($scores as $j=>$score)$small['papers'][]=['candidate_id'=>$id,'exam_subject_id'=>$j+1,'attendance_status'=>'present','penalty_percent'=>null,'paper_status'=>'matched','appeal_status'=>null,'original_score'=>$score];foreach([$id===1?2:1,$id===1?1:2] as $j=>$school)$small['aspirations'][]=['id'=>$id*10+$j,'candidate_id'=>$id,'school_id'=>$school,'priority_order'=>$j+1];}
$mini=selection_plan($small);$aspById=array_column($small['aspirations'],null,'id');$chosenSchools=[];foreach($mini['assigned'] as $candidate=>$aspId)$chosenSchools[$candidate]=(int)$aspById[$aspId]['school_id'];ksort($chosenSchools);
$assert(!$mini['errors'] && !$mini['tie'] && $chosenSchools===[1=>2,2=>1,3=>1,4=>2],'Independent expected outcome preserves NV1 and gives exactly the remaining NV2 seat');
$assert($mini['used']===[1=>2,2=>2] && !isset($mini['assigned'][5]),'A school cannot reuse its full quota in a later wish round');
$same=$small;foreach($same['papers'] as &$p)if((int)$p['candidate_id']===5)$p['original_score']=8;unset($p);$tied=selection_plan($same);
$assert($tied['tie'] && $tied['tie']['priority']===2 && $tied['tie']['seats']===1 && array_column($tied['tie']['candidates'],'id')===[4,5],'Tie is calculated after excluding previously admitted candidates and consuming earlier seats');
$resolved=selection_plan($same,[$tied['tie']['key']=>[5]]);
$assert(!$resolved['tie'] && isset($resolved['assigned'][5]) && !isset($resolved['assigned'][4]),'Explicit reviewed choice can select the higher ID; IDs never break quota ties');
$inactive=$small;$inactive['candidates'][4]['user_active']=0;$inactive['candidates'][4]['exam_number']=null;$inactive['papers']=array_values(array_filter($inactive['papers'],static fn($p)=>(int)$p['candidate_id']!==5));$inactivePlan=selection_plan($inactive);
$assert(!$inactivePlan['errors'] && !$inactivePlan['candidates'][5]['eligible_selection'],'Inactive profiles without exam allocations are excluded without blocking the cohort');
$missing=$small;$missing['papers'][9]['original_score']=null;
$assert((bool)selection_plan($missing)['errors'],'A missing score for an otherwise eligible candidate blocks calculation');
$reject(fn()=>admission_calculate($pdo,$period,$actor),'Open appeal phase prevents selection');
// Fixture-only post-appeal phase; no official dates are copied into the real period.
$settings=period_configuration($pdo,$period)['settings'];$settings['appeal_end']=(new DateTimeImmutable('now'))->modify('-1 minute')->format('Y-m-d H:i:s');$settings['appeal_start']=(new DateTimeImmutable('now'))->modify('-2 hours')->format('Y-m-d H:i:s');
$pdo->prepare('UPDATE admission_period_configs SET settings=? WHERE admission_period_id=?')->execute([selection_json($settings),$period]);
$pdo->prepare('UPDATE appeal_windows SET start_at=?,end_at=?,status="closed" WHERE admission_period_id=?')->execute([$settings['appeal_start'],$settings['appeal_end'],$period]);
$assert(!selection_prerequisites($pdo,$period),'Published, reviewed, post-appeal cohort is ready');
$connection=new PDO('mysql:host='.(getenv('DB_HOST') ?: '127.0.0.1').';port='.(getenv('DB_PORT') ?: '3306').';dbname='.getenv('DB_NAME').';charset=utf8mb4',getenv('DB_USER') ?: 'root',getenv('DB_PASS') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$connection->exec('SET innodb_lock_wait_timeout=1');
$pdo->beginTransaction();try{selection_lock($pdo,$period);selection_inputs($pdo,$period);$blocked=false;try{$connection->prepare('UPDATE candidates SET eligible=eligible WHERE id=?')->execute([$a['id']]);}catch(PDOException $e){$blocked=(int)($e->errorInfo[1] ?? 0)===1205;}$assert($blocked,'A concurrent candidate writer cannot change the locked selection input');}finally{$pdo->rollBack();}
$profiles=$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll();
$ids=array_map('intval',array_column($students,'id'));$zero=$ids[10];$disqualified=$ids[11];$penalized=$ids[12];$bonus=$ids[13];
// Vary the disposable published fixture while preserving the production score triggers.
$pdo->prepare('DELETE FROM publication_events WHERE admission_period_id=? AND publication_type="exam_results"')->execute([$period]);
$pdo->prepare('UPDATE scores sc JOIN exam_papers ep ON ep.id=sc.exam_paper_id SET sc.total_score=0,sc.score_1=0 WHERE ep.admission_period_id=? AND ep.candidate_id=? AND ep.exam_subject_id=?')->execute([$period,$zero,$subjects[0]]);
$pdo->prepare('UPDATE exam_papers SET attendance_status="present_penalty",penalty_percent=100 WHERE admission_period_id=? AND candidate_id=? AND exam_subject_id=?')->execute([$period,$disqualified,$subjects[0]]);
$pdo->prepare('UPDATE exam_papers SET attendance_status="present_penalty",penalty_percent=25 WHERE admission_period_id=? AND candidate_id=? AND exam_subject_id=?')->execute([$period,$penalized,$subjects[0]]);
publish_exam_results($pdo,$period,$actor);
$pdo->prepare('INSERT INTO candidate_priority_points(admission_period_id,candidate_id,priority_points) VALUES(?,?,2)')->execute([$period,$bonus]);
$reject(fn()=>admission_calculate($pdo,$period,$actor),'Legacy bonus without reviewed evidence blocks calculation');
$reject(fn()=>selection_bonus_save($pdo,$period,$bonus,'group_1','gold_first','ngắn',$actor),'Bonus review needs sufficient evidence');
selection_bonus_save($pdo,$period,$bonus,'group_1','gold_first','Minh chứng mô phỏng đã đối chiếu',$actor);
$input=selection_inputs($pdo,$period);$plan=selection_plan($input);
$assert($plan['candidates'][$bonus]['priority_points']===3.5,'Reviewed priority and award total is capped at 3.5');
$assert(!$plan['candidates'][$zero]['eligible_selection'] && !$plan['candidates'][$disqualified]['eligible_selection'],'A zero score and 100 percent penalty exclude the candidates');
$assert($plan['candidates'][$penalized]['score_total']===22.0,'The recorded 25 percent penalty is applied exactly once');
$assert(count(array_filter($plan['candidates'],static fn($c)=>!$c['eligible_selection']))===3,'One absent and two zero-score candidates have explicit exclusion reasons');
$run=admission_calculate($pdo,$period,$actor);$snapshot=selection_snapshot($pdo,$run);
$assert(latest_admission_run($pdo,$period)['status']==='blocked' && $snapshot['report']['tie'],'Quota-boundary ties produce a persisted blocked run');
$assert(!admission_results($pdo,$run),'A blocked run writes no partial admission results');
$reject(fn()=>selection_review($pdo,$period,$run,$actor),'A blocked run cannot be approved');
$reject(fn()=>publish_admission_results($pdo,$period,$actor),'A blocked run cannot be published');
$tie=$snapshot['report']['tie'];
$reject(fn()=>selection_tie_resolve($pdo,$period,$run,$tie['key'],[$zero],'Biên bản mô phỏng không hợp lệ',$actor),'Tie resolution rejects candidates outside the exact tied group');
for($iteration=0;$iteration<8;$iteration++){
    $snapshot=selection_snapshot($pdo,$run);$tie=$snapshot['report']['tie'];if(!$tie)break;
    $chosen=array_slice(array_map('intval',array_column($tie['candidates'],'id')),0,(int)$tie['seats']);
    selection_tie_resolve($pdo,$period,$run,$tie['key'],$chosen,'Biên bản quyết định nhóm đồng điểm trong ca mô phỏng',$actor);
    $run=admission_calculate($pdo,$period,$actor);
}
$snapshot=selection_snapshot($pdo,$run);$report=$snapshot['report'];
$assert(!$report['tie'] && latest_admission_run($pdo,$period)['status']==='calculated','Recorded simulation decisions resolve ties across wish rounds');
$results=admission_results($pdo,$run);
$assert(count($results)===2160,'All 720 times three aspirations receive a result');
$assert(count($report['assigned'])===717,'All 717 eligible students are assigned within the available quotas');
foreach($report['used'] as $school=>$used)$assert($used<=$report['quotas'][$school],'Shared school quota is respected across all priorities for '.$school);
$admitted=array_values(array_filter($results,static fn($r)=>$r['result_status']==='admitted'));
$assert(count($admitted)===count(array_unique(array_column($admitted,'candidate_id'))),'A candidate is admitted to at most one aspiration');
$assert(count(array_filter($admitted,static fn($r)=>(int)$r['priority_order']===2))>0 && count(array_filter($admitted,static fn($r)=>(int)$r['priority_order']===3))>0,'Lower wishes fill only remaining schools and remaining places');
$assert(!$report['candidates'][$ids[0]]['eligible_selection'] || count(array_filter($admitted,static fn($r)=>(int)$r['candidate_id']===$ids[0]))===1,'Selection accounts for completed appeals without duplicate admissions');
$assert($profiles===$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll(),'Simulation calculations do not alter global candidate admission status');
$assert(!candidate_admission_result($pdo,(int)$a['id'],$period),'Candidate helper hides unapproved and unpublished calculation results');
$reject(fn()=>enrollment_confirm($pdo,$penalized,$period),'Enrollment cannot use an unpublished preview');
$reject(fn()=>publish_admission_results($pdo,$period,$actor),'A calculated run must be reviewed before publication');
selection_review($pdo,$period,$run,$actor);
$savedSnapshot=selection_snapshot($pdo,$run);$tampered=$savedSnapshot['inputs'];$tampered['candidates'][0]['full_name']='Nội dung không khớp dữ liệu gốc';
$pdo->prepare('UPDATE admission_run_snapshots SET inputs=? WHERE admission_run_id=?')->execute([selection_json($tampered),$run]);
$reject(fn()=>selection_review($pdo,$period,$run,$actor),'Approval detects tampering inside the saved input snapshot');
$pdo->prepare('UPDATE admission_run_snapshots SET inputs=? WHERE admission_run_id=?')->execute([selection_json($savedSnapshot['inputs']),$run]);
$assert((bool)selection_snapshot($pdo,$run)['reviewed_at'],'Approval records the reviewer and timestamp');
selection_bonus_save($pdo,$period,$bonus,'group_1','gold_first','Minh chứng mô phỏng cập nhật sau lượt xét',$actor);
$reject(fn()=>publish_admission_results($pdo,$period,$actor),'Changed input invalidates an earlier approval');
$run=admission_calculate($pdo,$period,$actor);
// The changed fingerprint deliberately invalidates all old tie decisions; resolve the current groups again.
for($i=0;$i<8;$i++){$snapshot=selection_snapshot($pdo,$run);if(!$snapshot['report']['tie'])break;$tie=$snapshot['report']['tie'];selection_tie_resolve($pdo,$period,$run,$tie['key'],array_slice(array_column($tie['candidates'],'id'),0,$tie['seats']),'Biên bản mô phỏng theo dữ liệu đã cập nhật',$actor);$run=admission_calculate($pdo,$period,$actor);}
$snapshot=selection_snapshot($pdo,$run);$results=admission_results($pdo,$run);$original=$results[0];
$pdo->prepare('UPDATE admission_results SET admission_score=admission_score+1 WHERE id=?')->execute([$original['id']]);
$reject(fn()=>selection_review($pdo,$period,$run,$actor),'Approval detects a tampered result row');
$pdo->prepare('UPDATE admission_results SET admission_score=? WHERE id=?')->execute([$original['admission_score'],$original['id']]);
selection_review($pdo,$period,$run,$actor);

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
    $admin=$session($actor);$student=$session((int)$a['user_id']);$schoolTeacher=$pdo->prepare('SELECT user_id FROM staff WHERE school_id=? AND user_id IS NOT NULL LIMIT 1');$schoolTeacher->execute([$a['middle_school_id']]);$teacher=$session((int)$schoolTeacher->fetchColumn());
    $page=$http('/admission/calculate.php?period_id='.$period,$admin);
    $assert($page['status']===200 && str_contains($page['body'],'MÔ PHỎNG') && str_contains($page['body'],'Trang 1 / 44') && !preg_match('/Warning:|Fatal error/',$page['body']),'Selection console renders a paginated simulation review without PHP errors');
    $assert($http('/admission/calculate.php?period_id='.$period,$student)['status']===403,'Student cannot run or review selection');
    $assert($http('/admission/export.php?run_id='.$run,$student)['status']===403,'Student cannot download the full selection list');
    $assert($http('/admission/calculate.php?period_id='.$period,$admin,['csrf_token'=>'bad','action'=>'review','run_id'=>$run])['status']===400,'Selection actions reject invalid CSRF');
    $page=$http('/admission/results.php?period_id='.$period,$student);
    $assert($page['status']===200 && !str_contains($page['body'],'Danh sách nguyện vọng & Kết quả'),'Candidate HTTP page hides unpublished calculation results');
    $page=$http('/teacher/admitted.php?period_id='.$period,$teacher);
    $assert($page['status']===200 && !str_contains($page['body'],$a['national_id']),'School teacher page hides all unpublished selection previews');
    $page=$http('/admission/export.php?run_id='.$run,$admin);$lines=array_values(array_filter(explode("\n",trim($page['body']))));
    $assert($page['status']===200 && count($lines)===2161 && str_contains($page['body'],'MÔ PHỎNG'),'CSV contains all 2160 aspiration results and a simulation label');
    $page=$http('/admission/calculate.php?period_id='.$period,$admin,['csrf_token'=>$admin['csrf'],'action'=>'review','run_id'=>$run]);
    $assert($page['status']===302,'Administrator can approve the current run through HTTP');
    $page=$http('/publication/index.php?period_id='.$period,$admin,['csrf'=>$admin['csrf'],'period_id'=>$period,'type'=>'admission']);
    $assert($page['status']===200 && str_contains($page['body'],'Công bố thành công'),'Administrator publishes the approved current run through HTTP');
    $publishedRows=public_admission_results($pdo,$period);$assert(count($publishedRows)===2160,'Published result reader uses only the current finalized run');
    $assert(count(candidate_admission_result($pdo,(int)$a['id'],$period))===3,'Candidate helper returns only the three published aspirations');
    $page=$http('/admission/results.php?period_id='.$period,$student);
    $assert($page['status']===200 && str_contains($page['body'],'MÔ PHỎNG') && str_contains($page['body'],'Danh sách nguyện vọng & Kết quả'),'Candidate sees published results with simulation notice');
    $page=$http('/teacher/admitted.php?period_id='.$period,$teacher);
    $assert($page['status']===200 && str_contains($page['body'],'MÔ PHỎNG') && !preg_match('/Warning:|Fatal error/',$page['body']),'School teacher reads the current published run without duplicate old runs');
    $find=$pdo->prepare('SELECT c.national_id,n.exam_number FROM admission_results ar JOIN candidates c ON c.id=ar.candidate_id JOIN exam_candidate_numbers n ON n.candidate_id=c.id AND n.admission_period_id=ar.admission_period_id WHERE ar.admission_run_id=? AND ar.result_status="admitted" AND c.middle_school_id=? LIMIT 1');$find->execute([$run,$a['middle_school_id']]);$find=$find->fetch();
    $page=$http('/teacher/admitted.php?period_id='.$period.'&q='.rawurlencode($find['exam_number']),$teacher);
    $assert($page['status']===200 && str_contains($page['body'],$find['national_id']),'School admission search uses the SBD of the selected period');
    $reject(fn()=>admission_calculate($pdo,$period,$actor),'Published selection cannot be recalculated');
    $reject(fn()=>selection_bonus_save($pdo,$period,$bonus,'none','none','Không sửa sau công bố mô phỏng',$actor),'Published selection freezes bonus reviews');
    $assert($profiles===$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll(),'Publishing simulation does not rewrite global candidate status');
    echo 'COMPLETE: ',$checks,' organization, grading and selection checks passed.',PHP_EOL;
}finally{
    if(is_resource($server)){proc_terminate($server);proc_close($server);}foreach(glob($temp.'/*') ?: [] as $file)if(is_file($file))unlink($file);if(is_dir($temp))rmdir($temp);
}
