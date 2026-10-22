<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli' || !preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/',getenv('DB_NAME')?:''))exit(2);
require __DIR__.'/batchEnrollment_runtime.php';
require_once __DIR__.'/../includes/teacher_reporting_service.php';

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

    $ownerSession=$session($uid);
    $r=teacher_period_report($pdo,(int)$a['middle_school_id'],'THCS',$period);
    $expected=0;$confirmed=0;$candidateSchools=array_column($students,'middle_school_id','id');
    foreach(admitted_list($pdo,$period) as $row)if((int)$candidateSchools[$row['candidate_id']]===(int)$a['middle_school_id']){$expected++;if($row['enrollment_status']==='confirmed')$confirmed++;}
    $assert($r['summary']['total_candidates']===360 && $r['summary']['total_admitted']===$expected && $r['summary']['total_confirmed']===$confirmed,'School report agrees with published roster without counting historical runs');
    $r2=teacher_period_report($pdo,(int)$a['middle_school_id'],'THCS',$second);
    $assert($r2['summary']['total_candidates']<=1 && $r2['summary']['total_admitted']<=1,'Second period statistics do not combine the first period');
    $counts=array_column(statistics($pdo,$period,'admission_result'),'total','result_status');
    $assert(array_sum(array_map('intval',$counts))===720 && (int)$counts['admitted']===717,'Global statistics count 720 people instead of 2160 aspiration rows');
    $page=$http('/teacher/statistics.php?period_id='.$period,$teacher);
    $assert($page['status']===200 && str_contains($page['body'],'MÔ PHỎNG') && !preg_match('/Warning:|Fatal error/',$page['body']),'Teacher period report renders with simulation context');
    $assert($http('/statistics/index.php?period_id='.$period,$teacher)['status']===403,'Teacher cannot read the global statistics endpoint');
    $page=$http('/admin/audit.php?period_id='.$period.'&action=enrollment_confirmed',$admin);
    $assert($page['status']===200 && str_contains($page['body'],$first['receipt_code']) && str_contains($page['body'],'1 bản ghi theo bộ lọc'),'Audit filter finds the original enrollment event');
    $assert($http('/admin/audit.php',$teacher)['status']===403 && $http('/admin/audit.php',$student)['status']===403,'Audit is restricted to management');
    $page=$http('/admin/audit.php?period_id='.$second.'&action=enrollment_confirmed',$admin);
    $assert($page['status']===200 && str_contains($page['body'],$next['receipt_code']) && !str_contains($page['body'],$first['receipt_code']),'Audit keeps different periods separate');
    $page=$http('/enrollment/receipt.php?period_id='.$period,$ownerSession);
    $assert(str_contains(strtolower($page['headers']),'x-content-type-options: nosniff') && str_contains(strtolower($page['headers']),'cache-control: private, no-store'),'Private receipt returns protective response headers');
    $pdo->prepare('UPDATE users SET active=0 WHERE id=?')->execute([$actor]);
    $assert($http('/admin/index.php',$admin)['status']===403 && $http('/admin/audit.php',$admin)['status']===403,'Inactive administrator cannot use an existing session');
    $pdo->prepare('UPDATE users SET active=1 WHERE id=?')->execute([$actor]);
    $roles=$pdo->prepare('SELECT * FROM user_roles WHERE user_id=?');$roles->execute([$actor]);$roles=$roles->fetchAll();
    $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$actor]);
    $assert($http('/admin/index.php',$admin)['status']===403,'Revoking administrator roles blocks the existing session');
    foreach($roles as $role)$pdo->prepare('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$actor,$role['role_id']]);
    $assert($http('/admin/index.php',$admin)['status']===200,'Restored administrator role permits a valid session again');
    $teacherId=$pdo->prepare('SELECT user_id FROM staff WHERE school_id=? AND user_id IS NOT NULL LIMIT 1');$teacherId->execute([$a['middle_school_id']]);$teacherId=(int)$teacherId->fetchColumn();
    $pdo->prepare('UPDATE staff SET school_id=? WHERE user_id=?')->execute([$pick['school_id'],$teacherId]);
    $page=$http('/teacher/index.php?period_id='.$second,$teacher);
    $candidateMap=array_column($students,null,'id');
    $assert($page['status']===200 && str_contains($page['body'],$candidateMap[$cid]['national_id']) && !str_contains($page['body'],$a['national_id']),'THPT dashboard shows only candidates registered to its school in the selected period');
    $pdo->prepare('UPDATE staff SET school_id=? WHERE user_id=?')->execute([$a['middle_school_id'],$teacherId]);
    echo 'COMPLETE: ',$checks,' checks including handover passed.',PHP_EOL;
}finally{
    if(is_resource($server)){proc_terminate($server);proc_close($server);}foreach(glob($temp.'/*')?:[] as $file)if(is_file($file))unlink($file);if(is_dir($temp))rmdir($temp);
}
