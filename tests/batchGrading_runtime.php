<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli' || !preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/',getenv('DB_NAME') ?: ''))exit(2);
// Reuse the verified, released 720-student fixture; all of it lives in the disposable DB.
require __DIR__.'/batchOrganization_runtime.php';
require_once __DIR__.'/../includes/appeal_service.php';
require_once __DIR__.'/../includes/publication_service.php';
require_once __DIR__.'/../includes/admission_service.php';
$pdo->exec((string)file_get_contents(__DIR__.'/../database/017_exam_grading.sql'));
// Advance the fixture to the post-exam/appeal phase without changing the real clock or live DB.
$pdo->prepare('UPDATE exam_subjects SET exam_date=DATE_SUB(exam_date,INTERVAL 7 DAY) WHERE admission_period_id=?')->execute([$period]);
$cfg=period_configuration($pdo,$period);$d=$cfg['settings'];
foreach(['registration'=>[-264,-240],'adjustment'=>[-216,-192],'appeal'=>[-1,1],'enrollment'=>[48,72]] as $stage=>[$start,$end])foreach(['start'=>$start,'end'=>$end] as $edge=>$hour)$d[$stage.'_'.$edge]=$now->modify(($hour>=0?'+':'').$hour.' hours')->format('Y-m-d H:i:s');
$pdo->prepare('UPDATE admission_period_configs SET settings=? WHERE admission_period_id=?')->execute([json_encode($d,JSON_UNESCAPED_UNICODE),$period]);
$pdo->prepare('UPDATE appeal_windows SET start_at=?,end_at=?,status="open" WHERE admission_period_id=?')->execute([$d['appeal_start'],$d['appeal_end'],$period]);
$teachers=$pdo->query('SELECT id,user_id FROM staff WHERE user_id IS NOT NULL AND school_id IS NOT NULL AND active=1 ORDER BY id')->fetchAll();$initial=$teachers[0];$reviewer=$teachers[1];
$initialMembers=[];$appealMembers=[];
foreach($subjects as $subject){
    $council=council_create($pdo,['admission_period_id'=>$period,'council_type'=>'grading','name'=>'Hội đồng chấm mô phỏng '.$subject,'exam_subject_ids'=>[$subject],'members'=>[['staff_id'=>$initial['id'],'role_name'=>'Người chấm']]]);
    $initialMembers[$subject]=(int)$pdo->query('SELECT id FROM council_members WHERE council_id='.$council)->fetchColumn();
    $council=council_create($pdo,['admission_period_id'=>$period,'council_type'=>'appeal','name'=>'Hội đồng phúc khảo mô phỏng '.$subject,'exam_subject_ids'=>[$subject],'members'=>[['staff_id'=>$initial['id']],['staff_id'=>$reviewer['id']]]]);
    $appealMembers[$subject]=(int)$pdo->query('SELECT id FROM council_members WHERE council_id='.$council.' AND staff_id='.(int)$reviewer['id'])->fetchColumn();
}
$reject(fn()=>publish_exam_results($pdo,$period,$actor),'Unreviewed managed exam scores cannot be published');
$reject(fn()=>grading_review($pdo,$period,$actor),'Incomplete marking cannot be reviewed');
foreach($subjects as $subject)foreach($roomIds as $room)generate_exam_papers($pdo,$period,$subject,$room);
$assert((int)$pdo->query('SELECT COUNT(*) FROM exam_papers WHERE admission_period_id='.$period)->fetchColumn()===2160,'All 2160 exam papers are generated for the three-subject cohort');
$reject(fn()=>generate_codes($pdo,$period,$subjects[0],$roomIds[0]),'Anonymous coding requires completed attendance');
foreach($subjects as $subject)foreach($roomIds as $room){$attendance=[];foreach(paper_rows($pdo,$period,$subject,$room) as $p)$attendance[(int)$p['id']]=['status'=>'present'];save_attendance($pdo,$period,$subject,$room,$attendance);}
$firstRows=paper_rows($pdo,$period,$subjects[0],$roomIds[0]);
$rosterNumbers=array_column($firstRows,'candidate_code');$sortedNumbers=$rosterNumbers;sort($sortedNumbers,SORT_STRING);
$assert($rosterNumbers===$sortedNumbers,'Attendance roster has a stable order by this period SBD even without legacy candidate codes');
$assert(str_starts_with($firstRows[0]['candidate_code'],'SIM26'),'Attendance roster uses the period SBD');
// Fixture identity must not depend on which name/SBD sorts first in the room.
usort($firstRows,static fn($left,$right)=>(int)$left['candidate_id']<=>(int)$right['candidate_id']);
$assert((int)$firstRows[0]['candidate_id']===(int)$a['id'],'The absent fixture is the designated first candidate, separate from penalty cases');
$attendance=[];foreach($firstRows as $p)$attendance[(int)$p['id']]=['status'=>'present'];$absentPaper=(int)$firstRows[0]['id'];$attendance[$absentPaper]=['status'=>'absent'];save_attendance($pdo,$period,$subjects[0],$roomIds[0],$attendance);
$reject(fn()=>preview_random_codes($pdo,$period,$subjects[0],$roomIds[0],1),'Insufficient anonymous-code namespace fails without looping');
foreach($subjects as $subject)foreach($roomIds as $room)generate_codes($pdo,$period,$subject,$room);
$before=$pdo->query('SELECT id,anonymous_code FROM exam_papers ORDER BY id')->fetchAll();generate_codes($pdo,$period,$subjects[0],$roomIds[1]);
$assert($before===$pdo->query('SELECT id,anonymous_code FROM exam_papers ORDER BY id')->fetchAll(),'Generating codes again preserves all existing mappings');
$reject(fn()=>save_attendance($pdo,$period,$subjects[0],$roomIds[0],array_replace($attendance,[(int)$firstRows[1]['id']=>['status'=>'absent']])),'Attendance cannot be changed after coding');
$codes=[];foreach(paper_rows($pdo,$period,$subjects[0],$roomIds[0]) as $p)if($p['attendance_status']!=='absent')$codes[$p['id']]=$p['anonymous_code'];$codes[array_key_first($codes)]='RENUMBER';
$reject(fn()=>save_codes($pdo,$period,$subjects[0],$roomIds[0],$codes),'Existing anonymous codes cannot be reassigned');
$q=$pdo->prepare('SELECT anonymous_code FROM exam_papers WHERE admission_period_id=? AND exam_subject_id=? AND anonymous_code IS NOT NULL ORDER BY id LIMIT 1');$q->execute([$period,$subjects[0]]);$code=(string)$q->fetchColumn();
$assert(!validate_score_rows($pdo,$period,$subjects[0],[$code=>8.1])['valid'],'Managed scores reject off-quarter increments');
$assert(!validate_score_rows($pdo,$period,$subjects[0],[$code=>['score_1'=>8,'total_score'=>9]])['valid'],'Single-component score must equal total');
$valid=validate_score_rows($pdo,$period,$subjects[0],[$code=>8])['valid'];$reject(fn()=>save_scores($pdo,$actor,$period,$subjects[0],$valid),'Score commit requires a recorded initial grader');
foreach($subjects as $subject)$assert(grading_assign_initial($pdo,$period,$subject,0,$initialMembers[$subject],$actor)>0,'Initial grader assigned for subject '.$subject);
$parsed=[['anonymous_code','score_1','score_2','total_score']];$q=$pdo->prepare('SELECT anonymous_code FROM exam_papers WHERE admission_period_id=? AND exam_subject_id=? AND anonymous_code IS NOT NULL ORDER BY id');$q->execute([$period,$subjects[0]]);foreach($q as $r)$parsed[]=[$r['anonymous_code'],'','','8'];
$xlsx=tempnam(sys_get_temp_dir(),'ts10_score_');try{file_put_contents($xlsx,excel_xlsx_bytes($parsed[0],array_slice($parsed,1)));$roundtrip=excel_parse_upload($xlsx,'diem-ngu-van.xlsx');$assert(count($roundtrip)===720 && (string)$roundtrip[1][0]===(string)$parsed[1][0],'Real XLSX parsing preserves all 719 anonymous identifiers');$parsed=$roundtrip;}finally{if(is_file($xlsx))unlink($xlsx);}
$batch=create_score_import_batch($pdo,$actor,$period,$subjects[0],'diem-ngu-van.xlsx',$parsed);
$assert((int)$pdo->query('SELECT valid_rows FROM exam_score_import_batches WHERE id='.$batch)->fetchColumn()===719,'Import preview validates every present paper without writing scores');
$assert((int)$pdo->query('SELECT COUNT(*) FROM scores')->fetchColumn()===0,'Preview leaves official first scores empty');
$changedPaper=(int)$firstRows[1]['id'];$pdo->prepare('UPDATE exam_papers SET attendance_status="absent" WHERE id=?')->execute([$changedPaper]);
$reject(fn()=>save_score_import_batch($pdo,$batch,$actor),'Import confirmation rechecks attendance after preview and rolls back all rows');
$assert((int)$pdo->query('SELECT COUNT(*) FROM scores')->fetchColumn()===0,'Rejected stale import writes no partial scores');
$pdo->prepare('UPDATE exam_papers SET attendance_status="present" WHERE id=?')->execute([$changedPaper]);
save_score_import_batch($pdo,$batch,$actor);
$reject(fn()=>save_score_import_batch($pdo,$batch,$actor),'An import confirmation cannot be replayed');
foreach(array_slice($subjects,1) as $subject){$input=[];$q->execute([$period,$subject]);foreach($q as $r)$input[$r['anonymous_code']]=8;$result=validate_score_rows($pdo,$period,$subject,$input);$assert(count($result['valid'])===720,'All 720 manual scores validate for subject '.$subject);save_scores($pdo,$actor,$period,$subject,$result['valid']);}
$assert((int)$pdo->query('SELECT COUNT(*) FROM scores')->fetchColumn()===2159,'The absent paper has no score; 2159 present papers are scored');
$reject(fn()=>grading_assign_initial($pdo,$period,$subjects[0],0,$initialMembers[$subjects[0]],$actor),'A scored paper cannot be reassigned to a different initial grader');
$ready=grading_readiness($pdo,$period);$assert(!$ready['errors'],'Review verifies complete attendance, coding, graders and matching: '.implode(' ',$ready['errors']));
grading_review($pdo,$period,$actor);
$assert((bool)grading_state($pdo,$period),'Review records the actor and locks the first marking');
$reject(fn()=>save_codes($pdo,$period,$subjects[0],$roomIds[0],$codes),'Review freezes anonymous coding');
$reject(fn()=>save_scores($pdo,$actor,$period,$subjects[0],$valid),'Review freezes direct score entry');
publish_exam_results($pdo,$period,$actor);
$assert(count(public_exam_results($pdo,$period,(int)$a['id']))>=2,'Published results are available to the student');
$paper=$pdo->query('SELECT p.* FROM exam_papers p WHERE p.admission_period_id='.$period.' AND p.exam_subject_id='.$subjects[1].' AND p.candidate_id='.(int)$a['id'])->fetch();
$appeal=create_appeal($pdo,(int)$a['id'],$period,$subjects[1],'Đề nghị kiểm tra bài thi');
$reject(fn()=>create_appeal($pdo,(int)$a['id'],$period,$subjects[1],'Trùng đơn'),'A paper cannot receive a duplicate appeal');
$initialAppealMember=(int)$pdo->query('SELECT cm.id FROM council_members cm JOIN councils c ON c.id=cm.council_id JOIN council_subjects cs ON cs.council_id=c.id WHERE c.admission_period_id='.$period.' AND c.council_type="appeal" AND cs.exam_subject_id='.$subjects[1].' AND cm.staff_id='.(int)$initial['id'])->fetchColumn();
$reject(fn()=>assign_appeal_grader($pdo,$appeal,$initialAppealMember,$actor),'Initial grader cannot review their own paper');
assign_appeal_grader($pdo,$appeal,$appealMembers[$subjects[1]],$actor);
set_appeal_grading($pdo,$appeal,(int)$reviewer['user_id']);
$reject(fn()=>complete_appeal($pdo,$appeal,8.1,'Sai bước điểm',(int)$reviewer['user_id']),'Appeal commit enforces quarter-point increments');
$reject(fn()=>complete_appeal($pdo,$appeal,8.5,'Sai người chấm',(int)$initial['user_id']),'Appeal commit checks the assigned grader');
complete_appeal($pdo,$appeal,8.5,'Biên bản kiểm tra lại bài thi',(int)$reviewer['user_id']);
$assert((float)$pdo->query('SELECT total_score FROM scores WHERE exam_paper_id='.(int)$paper['id'])->fetchColumn()===8.0,'Published first score remains immutable after appeal');
$effective=public_exam_results($pdo,$period,(int)$a['id']);$changed=array_values(array_filter($effective,static fn($r)=>$r['subject_code']==='NN'));
$assert(count($changed)===1 && (float)$changed[0]['total_score']===8.5 && (float)$changed[0]['original_score']===8.0,'Student result uses completed appeal while retaining original score');
$assert((int)$pdo->query('SELECT COUNT(*) FROM score_change_logs WHERE appeal_request_id='.$appeal)->fetchColumn()===1,'Appeal change has an audit record');
$assert((int)$pdo->query('SELECT COUNT(*) FROM notifications WHERE user_id='.(int)$a['user_id'])->fetchColumn()===1,'Student receives one appeal-result notification');
$reject(fn()=>complete_appeal($pdo,$appeal,9,'Không được ghi đè',(int)$reviewer['user_id']),'Completed appeal cannot be overwritten');
$reject(fn()=>$pdo->prepare('UPDATE scores SET total_score=9 WHERE exam_paper_id=?')->execute([$paper['id']]),'Database still rejects direct updates of published first scores');
$rows=admission_candidate_rows($pdo,$period);$studentRows=array_values(array_filter($rows,static fn($r)=>(int)$r['candidate_id']===(int)$a['id']));
$assert($studentRows && in_array((float)$studentRows[0]['score_total'],[16.5,24.5],true),'Selection input consumes the reviewed appeal score');

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
    $admin=$session($actor);$student=$session((int)$a['user_id']);$teacher=$session((int)$reviewer['user_id']);$otherTeacher=$session((int)$initial['user_id']);
    $page=$http('/exam/grading.php?period_id='.$period,$admin);
    $assert($page['status']===200 && str_contains($page['body'],'MÔ PHỎNG') && !preg_match('/Warning:|Fatal error/',$page['body']),'Grading hub renders without errors and marks simulation');
    $assert($http('/exam/grading.php?period_id='.$period,$student)['status']===403,'Student cannot manage grading');
    $assert($http('/exam/grading.php?period_id='.$period,$admin,['csrf_token'=>'bad','action'=>'review'])['status']===400,'Grading review rejects bad CSRF');
    $page=$http('/appeals/update_result.php?period_id='.$period.'&subject_id='.$subjects[1],$teacher);
    $assert($page['status']===200 && str_contains($page['body'],$a['full_name']) && !preg_match('/Warning:|Fatal error/',$page['body']),'Assigned teacher can access their appeal through teacher layout');
    $page=$http('/appeals/update_result.php?period_id='.$period.'&subject_id='.$subjects[1],$otherTeacher);
    $assert($page['status']===200 && !str_contains($page['body'],$card['exam_number']),'Other teacher cannot see the assigned appeal candidate SBD');
    $page=$http('/results/exam.php?period_id='.$period,$student);
    $assert($page['status']===200 && str_contains($page['body'],'8.5'),'Student HTTP result shows completed appeal score');
    $third=create_appeal($pdo,(int)$a['id'],$period,$subjects[2],'Kiểm tra môn Toán');assign_appeal_grader($pdo,$third,$appealMembers[$subjects[2]],$actor);
    $post=['csrf_token'=>$teacher['csrf'],'period_id'=>$period,'subject_id'=>$subjects[2],'appeal_id'=>$third,'action'=>'start_grading'];
    $page=$http('/appeals/update_result.php?period_id='.$period.'&subject_id='.$subjects[2],$teacher,$post);
    $assert($page['status']===200 && str_contains($page['body'],'Đã chuyển đơn'),'Assigned teacher starts appeal grading via HTTP');
    $page=$http('/appeals/update_result.php?period_id='.$period.'&subject_id='.$subjects[2],$teacher,array_merge($post,['action'=>'complete','new_score'=>'8.75','reason'=>'Biên bản phúc khảo môn Toán']));
    $assert($page['status']===200 && str_contains($page['body'],'Đã cập nhật kết quả phúc khảo'),'Assigned teacher completes an appeal via HTTP after publication');
    $schoolTeacher=$pdo->prepare('SELECT user_id FROM staff WHERE school_id=? AND user_id IS NOT NULL AND active=1 LIMIT 1');$schoolTeacher->execute([$a['middle_school_id']]);$schoolTeacher=$session((int)$schoolTeacher->fetchColumn());
    $page=$http('/teacher/scores.php?period_id='.$period.'&q='.rawurlencode($a['national_id']),$schoolTeacher);
    $assert($page['status']===200 && str_contains($page['body'],'8.5') && !preg_match('/Warning:|Fatal error/',$page['body']),'School teacher score search uses the correct period and reviewed appeal grade');
    $newAppeal=create_appeal($pdo,(int)$b['id'],$period,$subjects[1],'Kiểm tra môn ngoại ngữ');assign_appeal_grader($pdo,$newAppeal,$appealMembers[$subjects[1]],$actor);set_appeal_grading($pdo,$newAppeal,(int)$reviewer['user_id']);
    complete_appeal_batch($pdo,[['appeal_id'=>$newAppeal,'new_score'=>8.25,'reason'=>'Biên bản theo danh sách']],(int)$reviewer['user_id']);
    $assert((float)$pdo->query('SELECT new_score FROM appeal_results WHERE appeal_request_id='.$newAppeal)->fetchColumn()===8.25,'Batch appeal completes through the immutable-score workflow');
    $pdo->prepare('UPDATE exam_papers SET paper_status="coded" WHERE admission_period_id=? AND exam_subject_id=?')->execute([$period,$subjects[1]]);
    $page=$http('/exam/scores.php?period_id='.$period.'&subject_id='.$subjects[1],$admin);
    $assert($page['status']===200 && substr_count($page['body'],'name="score[')===150 && str_contains($page['body'],'Trang 1 / 15'),'Manual score form renders at most fifty rows and stays below input limits');
    $pdo->prepare('UPDATE exam_papers SET paper_status="matched" WHERE admission_period_id=? AND exam_subject_id=?')->execute([$period,$subjects[1]]);
    $assert((int)$pdo->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME IN ('trg_scores_lock_after_exam_publication','trg_scores_update_lock_after_exam_publication','trg_scores_delete_lock_after_exam_publication')")->fetchColumn()===3,'All three score-publication protection triggers remain present');
    echo 'COMPLETE: ',$checks,' organization and grading checks passed.',PHP_EOL;
}finally{
    if(is_resource($server)){proc_terminate($server);proc_close($server);}foreach(glob($temp.'/*') ?: [] as $file)if(is_file($file))unlink($file);if(is_dir($temp))rmdir($temp);
}
