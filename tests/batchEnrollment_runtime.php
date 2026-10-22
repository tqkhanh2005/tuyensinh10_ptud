<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli' || !preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/',getenv('DB_NAME')?:''))exit(2);
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/enrollment_schema_setup.php';
enrollment_schema_apply(db());
require __DIR__.'/batchSelection_runtime.php';
require_once __DIR__.'/../includes/enrollment_export.php';
$pick=$pdo->query('SELECT ar.*,c.user_id FROM admission_results ar JOIN candidates c ON c.id=ar.candidate_id WHERE ar.admission_run_id='.(int)$run.' AND ar.result_status="admitted" ORDER BY ar.id LIMIT 1')->fetch();
$cid=(int)$pick['candidate_id'];$uid=(int)$pick['user_id'];
$window=static function(string $start,string $end)use($pdo,$period):void{
    $cfg=period_configuration($pdo,$period);$d=$cfg['settings'];$d['enrollment_start']=$start;$d['enrollment_end']=$end;
    $pdo->prepare('UPDATE admission_period_configs SET settings=? WHERE admission_period_id=?')->execute([selection_json($d),$period]);
    $pdo->prepare('UPDATE admission_periods SET enrollment_start=?,enrollment_end=? WHERE id=?')->execute([$start,$end,$period]);
};
$tomorrow=date('Y-m-d H:i:s',time()+86400);$later=date('Y-m-d H:i:s',time()+172800);
$window($tomorrow,$later);
$reject(fn()=>enrollment_confirm_managed($pdo,$cid,$period,$uid),'Enrollment rejects submissions before the configured window');
$start=date('Y-m-d H:i:s',time()-3600);$end=date('Y-m-d H:i:s',time()+3600);$window($start,$end);
$assert(enrollment_context($pdo,$cid,$period)['can_confirm'],'Published admitted candidate is eligible within the configured window');
$reject(fn()=>enrollment_confirm_managed($pdo,$cid,$period,$actor),'Administrator cannot confirm on behalf of a candidate');
$reject(fn()=>enrollment_confirm_managed($pdo,(int)$a['id'],$period,(int)$a['user_id']),'Excluded candidate cannot confirm another aspiration');
$pdo->prepare('UPDATE candidates SET eligible=0 WHERE id=?')->execute([$cid]);
$reject(fn()=>enrollment_confirm_managed($pdo,$cid,$period,$uid),'An ineligible candidate cannot confirm');
$pdo->prepare('UPDATE candidates SET eligible=1 WHERE id=?')->execute([$cid]);
$pdo->prepare('UPDATE users SET active=0 WHERE id=?')->execute([$uid]);
$reject(fn()=>enrollment_confirm_managed($pdo,$cid,$period,$uid),'Inactive account cannot confirm');
$pdo->prepare('UPDATE users SET active=1 WHERE id=?')->execute([$uid]);
$pdo->prepare('UPDATE admission_results SET enrollment_status="confirmed" WHERE id=?')->execute([$pick['id']]);
$reject(fn()=>enrollment_confirm_managed($pdo,$cid,$period,$uid),'Confirmed flag without receipt is detected');
$pdo->prepare('UPDATE admission_results SET enrollment_status="pending",admission_score=admission_score+1 WHERE id=?')->execute([$pick['id']]);
$reject(fn()=>enrollment_confirm_managed($pdo,$cid,$period,$uid),'Tampered published result cannot be confirmed');
$pdo->prepare('UPDATE admission_results SET admission_score=? WHERE id=?')->execute([$pick['admission_score'],$pick['id']]);
$saved=selection_snapshot($pdo,$run);$pdo->prepare('UPDATE admission_run_snapshots SET reviewed_at=NULL WHERE admission_run_id=?')->execute([$run]);
$reject(fn()=>enrollment_confirm_managed($pdo,$cid,$period,$uid),'An unreviewed result cannot be confirmed');
$pdo->prepare('UPDATE admission_run_snapshots SET reviewed_at=? WHERE admission_run_id=?')->execute([$saved['reviewed_at'],$run]);
$assert((int)$pdo->query('SELECT COUNT(*) FROM enrollment_confirmations')->fetchColumn()===0,'All rejected requests leave no partial confirmation');
$assert(enrollment_csv_cell('=HYPERLINK("x")')==='\'=HYPERLINK("x")' && enrollment_csv_cell('001234')==='001234','Roster CSV guards formula cells and preserves identifier text');
$enrollmentProfiles=$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll();

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

    $ownerSession=$session($uid);$path='/enrollment/confirm.php?period_id='.$period;
    $page=$http($path,$ownerSession);
    $assert($page['status']===200 && str_contains($page['body'],'MÔ PHỎNG') && str_contains($page['body'],'acknowledge') && !preg_match('/Warning:|Fatal error/',$page['body']),'Own confirmation screen renders eligibility and simulation notice');
    $assert($http($path,$ownerSession,['period_id'=>$period,'csrf_token'=>'bad','acknowledge'=>'1'])['status']===400,'Enrollment rejects invalid CSRF');
    $assert($http($path.'&candidate_id='.$a['id'],$ownerSession)['status']===403,'Confirmation rejects another candidate ID');
    $assert($http('/enrollment/receipt.php?period_id='.$period,$ownerSession)['status']===404,'No receipt is fabricated before confirmation');
    $assert($http($path,$teacher)['status']===403,'School teacher cannot confirm enrollment');
    $assert($http('/admitted/index.php?period_id='.$period,$ownerSession)['status']===403,'Candidate cannot read the staff roster');
    $assert($http('/admitted/index.php?period_id='.$period.'&download=csv',$teacher)['status']===403,'School teacher cannot bypass school scope through the global roster export');
    $page=$http($path,$ownerSession,['period_id'=>$period,'csrf_token'=>$ownerSession['csrf']]);
    $assert($page['status']===200 && str_contains($page['body'],'đánh dấu'),'Confirmation requires explicit acknowledgment');
    $notifications=(int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
    $events=(int)$pdo->query('SELECT COUNT(*) FROM admission_period_events')->fetchColumn();
    $page=$http($path,$ownerSession,['period_id'=>$period,'csrf_token'=>$ownerSession['csrf'],'acknowledge'=>'1']);
    $assert($page['status']===303,'Successful confirmation redirects to avoid resubmission');
    $first=enrollment_context($pdo,$cid,$period)['confirmation'];
    $assert($first && strlen($first['receipt_code'])===32 && (int)$first['actor_user_id']===$uid,'Confirmation records owner, time and unique receipt');
    $again=enrollment_confirm_managed($pdo,$cid,$period,$uid);
    $assert($again===$first && (int)$pdo->query('SELECT COUNT(*) FROM enrollment_confirmations')->fetchColumn()===1,'Repeated confirmation returns the original record');
    $assert((int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn()===$notifications+1 && (int)$pdo->query('SELECT COUNT(*) FROM admission_period_events')->fetchColumn()===$events+1,'Repeated submission creates only one notification and audit entry');
    $page=$http('/enrollment/receipt.php?period_id='.$period,$ownerSession);
    $assert($page['status']===200 && str_contains($page['body'],$first['receipt_code']) && str_contains($page['body'],'window.print()') && str_contains($page['body'],'MÔ PHỎNG'),'Owner can print the correct simulation receipt');
    $assert($http('/enrollment/receipt.php?period_id='.$period.'&candidate_id='.$cid,$student)['status']===403,'Another candidate cannot retrieve the receipt');
    $page=$http('/admitted/index.php?period_id='.$period,$admin);
    $assert($page['status']===200 && str_contains($page['body'],'Trang 1 / 15') && substr_count($page['body'],'<tr>')===51 && !preg_match('/Warning:|Fatal error/',$page['body']),'Staff roster paginates 717 admissions into 50-row pages');
    $page=$http('/admitted/index.php?period_id='.$period.'&enrollment_status=confirmed',$admin);
    $assert($page['status']===200 && str_contains($page['body'],'1 học sinh theo bộ lọc') && str_contains($page['body'],$first['confirmed_at']),'Roster filters the confirmed student and shows timestamp');
    $page=$http('/admitted/index.php?period_id='.$period.'&download=csv',$admin);
    $lines=array_values(array_filter(explode("\n",trim($page['body']))));
    $assert($page['status']===200 && count($lines)===718 && str_contains($page['body'],'MÔ PHỎNG') && str_contains($page['body'],$first['receipt_code']),'CSV exports all filtered rows, including receipt and simulation context');
    $page=$http('/admitted/index.php?period_id='.$period.'&enrollment_status=confirmed&download=xlsx',$admin);
    $assert($page['status']===200 && str_starts_with($page['body'],'PK'),'Confirmed roster exports a valid XLSX archive');
    file_put_contents($temp.'/roster.xlsx',$page['body']);$parsed=excel_parse_xlsx($temp.'/roster.xlsx');
    $assert(count($parsed)===2 && $parsed[1][7]===$first['receipt_code'] && $parsed[1][8]==='MÔ PHỎNG','XLSX round trip preserves the one filtered receipt and simulation label');
    $page=$http('/teacher/admitted.php?period_id='.$period,$teacher);
    $assert($page['status']===200 && str_contains($page['body'],'Mỗi trang tối đa 50 dòng') && !preg_match('/Warning:|Fatal error/',$page['body']),'School roster supports pagination without PHP errors');
    $foreign=$pdo->prepare('SELECT c.national_id FROM candidates c JOIN admission_results ar ON ar.candidate_id=c.id WHERE ar.admission_run_id=? AND ar.result_status="admitted" AND c.middle_school_id<>? LIMIT 1');$foreign->execute([$run,$a['middle_school_id']]);$foreign=(string)$foreign->fetchColumn();
    $page=$http('/teacher/admitted.php?period_id='.$period.'&q='.rawurlencode($foreign),$teacher);
    $assert($page['status']===200 && !str_contains($page['body'],'<div class="text-muted font-monospace small">'.$foreign.'</div>'),'School search cannot return an admitted candidate of another school');
    $window(date('Y-m-d H:i:s',time()-7200),date('Y-m-d H:i:s',time()-3600));
    $other=$pdo->query('SELECT ar.candidate_id,c.user_id FROM admission_results ar JOIN candidates c ON c.id=ar.candidate_id WHERE ar.admission_run_id='.(int)$run.' AND ar.result_status="admitted" AND ar.candidate_id<>'.$cid.' LIMIT 1')->fetch();
    $reject(fn()=>enrollment_confirm_managed($pdo,(int)$other['candidate_id'],$period,(int)$other['user_id']),'New confirmation is rejected after deadline');
    $assert(enrollment_confirm_managed($pdo,$cid,$period,$uid)===$first,'Original confirmation remains idempotent after deadline');
    $assert($http('/enrollment/receipt.php?period_id='.$period,$ownerSession)['status']===200,'Receipt remains accessible after deadline');
    $page=$http('/admitted/index.php?period_id='.$period.'&enrollment_status=expired',$admin);
    $assert($page['status']===200 && str_contains($page['body'],'716 học sinh theo bộ lọc'),'Expired filter derives pending count without revoking admissions');
    $assert(count(admitted_list($pdo,$period))===717,'Deadline does not delete or redistribute admissions');
    $pdo->prepare('UPDATE admission_periods SET status="closed" WHERE id=?')->execute([$period]);
    $assert($http('/enrollment/receipt.php?period_id='.$period,$ownerSession)['status']===200,'Historical receipt remains accessible after the period closes');
    // Historical/new-period fixture: identical person, separate approved result and receipt.
    $copy=static function(string $table,array $row)use($pdo):int{unset($row['id']);$keys=array_keys($row);$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).')')->execute(array_values($row));return (int)$pdo->lastInsertId();};
    $oldPeriod=$pdo->query('SELECT * FROM admission_periods WHERE id='.(int)$period)->fetch();$oldPeriod['name']='Kỳ mô phỏng tiếp theo';$oldPeriod['status']='published';$oldPeriod['enrollment_start']=$start;$oldPeriod['enrollment_end']=$end;$second=$copy('admission_periods',$oldPeriod);
    $oldCfg=$pdo->query('SELECT * FROM admission_period_configs WHERE admission_period_id='.(int)$period)->fetch();$oldCfg['admission_period_id']=$second;$settings=json_decode($oldCfg['settings'],true);$settings['enrollment_start']=$start;$settings['enrollment_end']=$end;$oldCfg['settings']=selection_json($settings);$copy('admission_period_configs',$oldCfg);
    $oldAsp=$pdo->query('SELECT * FROM aspirations WHERE id='.(int)$pick['aspiration_id'])->fetch();$oldAsp['admission_period_id']=$second;$secondAsp=$copy('aspirations',$oldAsp);
    $oldRun=$pdo->query('SELECT * FROM admission_runs WHERE id='.(int)$run)->fetch();$oldRun['admission_period_id']=$second;$secondRun=$copy('admission_runs',$oldRun);
    $resultCopy=$pick;unset($resultCopy['user_id']);$resultCopy['admission_period_id']=$second;$resultCopy['admission_run_id']=$secondRun;$resultCopy['aspiration_id']=$secondAsp;$copy('admission_results',$resultCopy);
    $pub=$pdo->query('SELECT * FROM publication_events WHERE admission_period_id='.(int)$period.' AND publication_type="admission_results"')->fetch();$pub['admission_period_id']=$second;$copy('publication_events',$pub);
    $inputs=$saved['inputs'];foreach($inputs['aspirations'] as &$asp)if((int)$asp['id']===(int)$pick['aspiration_id']){$asp['id']=$secondAsp;$asp['admission_period_id']=$second;}unset($asp);
    $report=$saved['report'];$report['assigned'][$cid]=$secondAsp;
    $pdo->prepare('INSERT INTO admission_run_snapshots(admission_run_id,input_hash,inputs,report,reviewed_by,reviewed_at) VALUES(?,?,?,?,?,NOW())')->execute([$secondRun,selection_input_hash($inputs),selection_json($inputs),selection_json($report),$actor]);
    $next=enrollment_confirm_managed($pdo,$cid,$second,$uid);
    $assert((int)$next['admission_period_id']===$second && $next['receipt_code']!==$first['receipt_code'],'Same student can confirm a different approved period without global uniqueness conflict');
    $assert((int)$pdo->query('SELECT COUNT(*) FROM enrollment_confirmations WHERE candidate_id='.$cid)->fetchColumn()===2,'Two periods retain two independent confirmations');
    $assert($enrollmentProfiles===$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll(),'Enrollment simulation preserves global candidate records');
    echo 'COMPLETE: ',$checks,' checks including enrollment passed.',PHP_EOL;
}finally{
    if(is_resource($server)){proc_terminate($server);proc_close($server);}foreach(glob($temp.'/*')?:[] as $file)if(is_file($file))unlink($file);if(is_dir($temp))rmdir($temp);
}
