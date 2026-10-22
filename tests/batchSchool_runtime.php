<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli' || !preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/',getenv('DB_NAME') ?: '')) { http_response_code(404); exit(2); }
require_once __DIR__.'/../includes/hcm_school_workflow_setup.php';
require_once __DIR__.'/../includes/candidate_service.php';
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
$pdo=db(); $checks=0;
$assert=static function(bool $ok,string $label) use(&$checks): void {
    if (!$ok) throw new RuntimeException($label);
    echo 'PASS TEST ',++$checks,': ',$label,PHP_EOL;
};
$reject=static function(callable $operation,string $label) use($assert): void {
    $caught=false; try { $operation(); } catch (Throwable $exception) { $caught=true; }
    $assert($caught,$label);
};
$root=dirname(__DIR__); $temporary=sys_get_temp_dir().'/ts10_school_'.bin2hex(random_bytes(8));
mkdir($temporary,0700,true); $server=null;
try {
    $candidateBefore=$pdo->query('SELECT * FROM candidates ORDER BY id')->fetchAll();
    $assert(count($candidateBefore)===720,'Use all 720 imported student profiles');
    $schools=hcm_high_schools_setup($pdo); $teacherCredentials=[];
    $teachers=hcm_teacher_setup($pdo,static function(array $account) use(&$teacherCredentials): void { $teacherCredentials[$account['username']]=$account; });
    $assert(count($teachers)===2 && count($schools)===3,'Prepare two teachers and three verified HCM high schools');
    $assert(hcm_teacher_setup($pdo,static function(): void { throw new RuntimeException('Should not reset credentials'); })[0]['created']===false,'Repeated setup preserves existing teacher passwords');
    $students=[]; $teacherUsers=[];
    foreach (['THCS_NGUYEN_DU','THCS_LE_QUY_DON'] as $code) {
        $find=$pdo->prepare('SELECT c.* FROM candidates c JOIN schools s ON s.id=c.middle_school_id WHERE s.school_code=? ORDER BY c.id'); $find->execute([$code]); $students[$code]=$find->fetchAll();
        $find=$pdo->prepare('SELECT u.* FROM users u JOIN staff st ON st.user_id=u.id JOIN schools s ON s.id=st.school_id WHERE s.school_code=?'); $find->execute([$code]); $teacherUsers[$code]=$find->fetch();
        $assert(count($students[$code])===360,'School '.$code.' has 360 students');
    }
    $pdo->exec('INSERT INTO admission_periods(name,academic_year,status,registration_start,registration_end) VALUES("Mô phỏng nguyện vọng TP.HCM — CSDL riêng","SIMULATION","published",DATE_SUB(NOW(),INTERVAL 1 DAY),DATE_ADD(NOW(),INTERVAL 1 DAY))');
    $period=(int)$pdo->lastInsertId(); $schoolIds=array_column($schools,'id');
    $a=$students['THCS_NGUYEN_DU'][0]; $b=$students['THCS_LE_QUY_DON'][0];
    // Exercise the real save service for every imported profile, in varied priority orders.
    foreach ($candidateBefore as $index=>$candidate) {
        $ordered=$index%2 ? array_reverse($schoolIds) : $schoolIds;
        aspiration_save((int)$candidate['id'],$period,$ordered,(int)$candidate['user_id']);
        if (array_map('intval',array_column(aspiration_get((int)$candidate['id'],$period),'school_id'))!==$ordered) throw new RuntimeException('Saved order differs from submitted order');
    }
    $assert((int)$pdo->query('SELECT COUNT(*) FROM aspirations')->fetchColumn()===2160,'All 720 students retain the submitted order across 2160 aspirations');
    $pdo->exec('INSERT INTO admission_periods(name,academic_year,status) VALUES("Lịch sử mô phỏng riêng","SIMULATION-HISTORY","closed")');
    $history=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO aspirations(candidate_id,admission_period_id,school_id,priority_order) VALUES(?,?,?,1)')->execute([$a['id'],$history,$schoolIds[0]]);
    $teacherA=$teacherUsers['THCS_NGUYEN_DU']; $teacherB=$teacherUsers['THCS_LE_QUY_DON'];
    $new=$a; $new['address']='Địa chỉ đính chính, TP.HCM';
    $requestId=adjustment_submit((int)$a['id'],(int)$teacherA['id'],$a,$new,'Đính chính địa chỉ',null);
    $assert(candidate_find((int)$a['id'])['address']===$a['address'],'Teacher request waits for approval before changing profile');
    $reject(fn()=>adjustment_submit((int)$a['id'],(int)$teacherB['id'],$a,$new,'Cross school',null),'Other school teacher cannot submit a correction');
    $reject(fn()=>aspiration_save((int)$b['id'],$period,$schoolIds,(int)$a['user_id']),'Student cannot change another student aspirations');
    $reject(fn()=>aspiration_save((int)$a['id'],$period,$schoolIds,(int)$teacherA['id']),'Teacher cannot impersonate student to edit aspirations');
    $beforeNv=aspiration_get((int)$a['id'],$period);
    $reject(fn()=>aspiration_save((int)$a['id'],$period,[$schoolIds[0],$schoolIds[0]],(int)$a['user_id']),'Duplicate school is rejected');
    $reject(fn()=>aspiration_save((int)$a['id'],$period,[$schoolIds[0],999999999],(int)$a['user_id']),'Invalid later school rolls back replacement');
    $assert(aspiration_get((int)$a['id'],$period)===$beforeNv,'Failed replacement preserves every existing aspiration');
    $reject(fn()=>aspiration_save((int)$a['id'],$period,[$schoolIds[0],'invalid'],(int)$a['user_id']),'Malformed IDs cannot silently remove a priority');
    foreach (['draft','closed'] as $status) {
        $pdo->prepare('UPDATE admission_periods SET status=? WHERE id=?')->execute([$status,$period]);
        $reject(fn()=>aspiration_save((int)$a['id'],$period,$schoolIds,(int)$a['user_id']),'Block '.$status.' admission period');
    }
    $pdo->prepare('UPDATE admission_periods SET status="published",registration_start=DATE_ADD(NOW(),INTERVAL 2 DAY),registration_end=DATE_ADD(NOW(),INTERVAL 3 DAY) WHERE id=?')->execute([$period]);
    $reject(fn()=>aspiration_save((int)$a['id'],$period,$schoolIds,(int)$a['user_id']),'Block registration before opening');
    $pdo->prepare('UPDATE admission_periods SET registration_start=DATE_SUB(NOW(),INTERVAL 3 DAY),registration_end=DATE_SUB(NOW(),INTERVAL 2 DAY) WHERE id=?')->execute([$period]);
    $reject(fn()=>aspiration_save((int)$a['id'],$period,$schoolIds,(int)$a['user_id']),'Block expired registration');
    $pdo->prepare('UPDATE admission_periods SET registration_start=DATE_SUB(NOW(),INTERVAL 1 DAY),registration_end=DATE_ADD(NOW(),INTERVAL 1 DAY) WHERE id=?')->execute([$period]);
    $pdo->prepare('UPDATE candidates SET eligible=0 WHERE id=?')->execute([$a['id']]);
    $reject(fn()=>aspiration_save((int)$a['id'],$period,$schoolIds,(int)$a['user_id']),'Block ineligible student');
    $pdo->prepare('UPDATE candidates SET eligible=1 WHERE id=?')->execute([$a['id']]);
    $bad=$a; $bad['date_of_birth']='2011-02-30';
    $reject(fn()=>adjustment_submit((int)$a['id'],(int)$teacherA['id'],$a,$bad,'Invalid date',null),'Reject impossible birthday in correction request');

    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error); if (!$socket) throw new RuntimeException($error);
    $address=stream_socket_get_name($socket,false); fclose($socket); $base='http://'.$address;
    $server=proc_open([PHP_BINARY,'-d','session.save_path='.$temporary,'-S',$address,'router.php'],[0=>['pipe','r'],1=>['file',$temporary.'/server.log','w'],2=>['redirect',1]],$pipes,$root,getenv());
    if (!is_resource($server)) throw new RuntimeException('Cannot start isolated HTTP server'); fclose($pipes[0]);
    $sessions=[];
    $session=static function(int $userId) use($pdo,$temporary,&$sessions): array {
        $find=$pdo->prepare('SELECT * FROM users WHERE id=?'); $find->execute([$userId]); $user=$find->fetch();
        $find=$pdo->prepare('SELECT r.code FROM user_roles ur JOIN roles r ON r.id=ur.role_id AND r.active=1 WHERE ur.user_id=?'); $find->execute([$userId]); $roles=$find->fetchAll(PDO::FETCH_COLUMN);
        $id=bin2hex(random_bytes(20)); $csrf=bin2hex(random_bytes(20));
        $data=['auth'=>['id'=>$userId,'user_id'=>$userId,'username'=>$user['username'],'full_name'=>$user['full_name'],'roles'=>$roles,'role'=>$roles[0]],'csrf_token'=>$csrf];
        $text=''; foreach ($data as $key=>$value) $text.=$key.'|'.serialize($value);
        file_put_contents($temporary.'/sess_'.$id,$text); return ['id'=>$id,'csrf'=>$csrf];
    };
    $http=static function(string $path,array $session,?array $post=null) use($base): array {
        $curl=curl_init($base.$path); curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIE=>'PHPSESSID='.$session['id']]);
        if ($post!==null) { curl_setopt($curl,CURLOPT_POST,true); curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($post)); }
        $raw=curl_exec($curl); if ($raw===false) throw new RuntimeException(curl_error($curl));
        $code=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE); $size=(int)curl_getinfo($curl,CURLINFO_HEADER_SIZE); curl_close($curl);
        return ['status'=>$code,'body'=>substr($raw,$size)];
    };
    for ($attempt=0;$attempt<30;$attempt++) { $ready=@fsockopen('127.0.0.1',(int)substr(strrchr($address,':'),1)); if ($ready) { fclose($ready); break; } usleep(100000); }
    $sa=$session((int)$teacherA['id']); $sb=$session((int)$teacherB['id']); $sc=$session((int)$a['user_id']);
    $admin=(int)$pdo->query('SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE r.code="so_gdtd" ORDER BY u.id LIMIT 1')->fetchColumn(); $sd=$session($admin);
    foreach ([['THCS_NGUYEN_DU',$sa],['THCS_LE_QUY_DON',$sb]] as [$code,$teacherSession]) {
        $seen=[];
        for ($page=1;$page<=8;$page++) {
            $response=$http('/teacher/students.php?page='.$page,$teacherSession);
            if ($response['status']!==200 || preg_match('/Warning:|Fatal error/',$response['body'])) throw new RuntimeException('Student listing failed');
            preg_match_all('/data-candidate-id="([0-9]+)"/',$response['body'],$match); $seen=array_merge($seen,array_map('intval',$match[1]));
        }
        $expected=array_map('intval',array_column($students[$code],'id')); sort($seen); sort($expected);
        $assert($seen===$expected,'Eight HTTP pages expose exactly the 360 own-school students: '.$code);
    }
    $response=$http('/teacher/students.php?q='.urlencode($a['national_id']),$sa);
    $assert($response['status']===200 && str_contains($response['body'],'data-candidate-id="'.$a['id'].'"'),'Teacher search finds own student');
    $response=$http('/teacher/students.php?q='.urlencode($b['national_id']),$sa);
    $assert($response['status']===200 && !str_contains($response['body'],'data-candidate-id="'.$b['id'].'"'),'Teacher search never reveals other school student');
    $assert($http('/teacher/adjustments.php?candidate_id='.$b['id'],$sa)['status']===403,'Cross-school correction form is forbidden');
    $assert($http('/teacher/students.php',$sc)['status']===403,'Student cannot enter teacher listing');
    $assert($http('/candidates/edit.php?id='.$a['id'],$sa)['status']===403,'Teacher cannot bypass approval through admin edit');
    $response=$http('/teacher/aspirations.php?period_id='.$period.'&q='.urlencode($a['national_id']),$sa);
    $assert($response['status']===200 && str_contains($response['body'],$a['national_id']) && !str_contains($response['body'],$b['national_id']),'Teacher sees only own-school aspirations with search');
    preg_match_all('/data-aspiration-id="([0-9]+)"/',$response['body'],$rows);
    $assert(count($rows[1])===3,'Current period list does not combine historical aspirations');
    $response=$http('/teacher/aspirations.php?period_id='.$history.'&q='.urlencode($a['national_id']),$sa);
    preg_match_all('/data-aspiration-id="([0-9]+)"/',$response['body'],$rows);
    $assert(count($rows[1])===1,'Historical period has its own isolated list');
    $response=$http('/aspirations/index.php',$sc,['csrf_token'=>$sc['csrf'],'period_id'=>$period,'school_ids'=>array_reverse($schoolIds)]);
    $assert($response['status']===200 && str_contains($response['body'],'Đăng ký nguyện vọng thành công.'),'Student saves reordered priorities through the actual HTTP form');
    $assert(array_map('intval',array_column(aspiration_get((int)$a['id'],$period),'school_id'))===array_reverse($schoolIds),'HTTP submission stores the exact NV1 NV2 NV3 order');
    $beforeNv=aspiration_get((int)$a['id'],$period);
    $response=$http('/aspirations/index.php',$sc,['csrf_token'=>'wrong','period_id'=>$period,'school_ids'=>$schoolIds]);
    $assert($response['status']===400 && aspiration_get((int)$a['id'],$period)===$beforeNv,'Invalid CSRF leaves priorities unchanged');
    $response=$http('/aspirations/index.php',$sc,['csrf_token'=>$sc['csrf'],'period_id'=>$period,'school_ids'=>['',$schoolIds[0]]]);
    $assert(str_contains($response['body'],'Vui lòng chọn nguyện vọng liên tiếp') && aspiration_get((int)$a['id'],$period)===$beforeNv,'Blank NV1 cannot shift NV2 silently');
    $assert($http('/aspirations/index.php?candidate_id='.$b['id'],$sc)['status']===403,'HTTP blocks student ID tampering');
    $response=$http('/teacher/adjustments.php?action=create',$sa,['csrf_token'=>$sa['csrf'],'candidate_id'=>$a['id'],'reason'=>'Sửa số điện thoại','phone'=>'0901234567','full_name'=>'','national_id'=>'','gender'=>'','submit_adjustment'=>1]);
    $assert($response['status']===200 && str_contains($response['body'],'Đã gửi yêu cầu điều chỉnh'),'Teacher submits correction through HTTP');
    $assert(str_contains($response['body'],'Số điện thoại:</strong>') && !str_contains($response['body'],'>phone:</strong>'),'Correction history uses a Vietnamese field label');
    $reviewPage=$http('/adjustments/index.php',$sd);
    $assert($reviewPage['status']===200 && str_contains($reviewPage['body'],'Địa chỉ cư trú:') && str_contains($reviewPage['body'],'Lọc yêu cầu') && !str_contains($reviewPage['body'],'<pre>'),'Administrator compares changed fields without raw JSON');
    $response=$http('/adjustments/review.php',$sd,['csrf_token'=>$sd['csrf'],'id'=>$requestId,'action'=>'approve']);
    $assert($response['status']===302 && candidate_find((int)$a['id'])['address']===$new['address'],'Admin approval updates the requested profile through HTTP');
    $reviewPage=$http('/adjustments/index.php?status=approved',$sd);
    $assert($reviewPage['status']===200 && str_contains($reviewPage['body'],'Đã duyệt') && !str_contains($reviewPage['body'],'Duyệt yêu cầu #'.$requestId.'<'),'Approved correction remains in read-only history');
    $notification=$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=?'); $notification->execute([$teacherA['id']]);
    $assert((int)$notification->fetchColumn()>0,'Teacher receives correction review notification');
    $pdo->prepare('UPDATE users SET active=0 WHERE id=?')->execute([$teacherA['id']]);
    $assert($http('/teacher/students.php',$sa)['status']===403,'Existing session is denied after account deactivation');
    $pdo->prepare('UPDATE users SET active=1 WHERE id=?')->execute([$teacherA['id']]);
    $pdo->prepare('UPDATE staff SET school_id=NULL WHERE user_id=?')->execute([$teacherA['id']]);
    $assert($http('/teacher/students.php',$sa)['status']===403,'Unlinked teacher cannot gain access through a workplace-name guess');
    $pdo->prepare('UPDATE staff SET school_id=? WHERE user_id=?')->execute([$a['middle_school_id'],$teacherA['id']]);
    $pdo->prepare('DELETE ur FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.code="teacher"')->execute([$teacherA['id']]);
    $assert($http('/teacher/students.php',$sa)['status']===403,'Existing session is denied after teacher role revocation');
    $pdo->prepare('UPDATE users SET active=0 WHERE id=?')->execute([$a['user_id']]);
    $assert($http('/aspirations/index.php',$sc)['status']===403,'Inactive student account cannot use a previously authenticated session');
    echo 'COMPLETE: ',$checks,' school workflow checks passed on disposable data.',PHP_EOL;
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    foreach (glob($temporary.'/*') ?: [] as $file) if (is_file($file)) unlink($file);
    if (is_dir($temporary)) rmdir($temporary);
}
