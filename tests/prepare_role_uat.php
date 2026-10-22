<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(2);
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
$opts=getopt('',['backup:','private-dir:','cleanup:']);$pdo=db();
if(isset($opts['cleanup'])){
    $m=json_decode((string)file_get_contents($opts['cleanup']),true,512,JSON_THROW_ON_ERROR);
    if($m['source_database']!==$pdo->query('SELECT DATABASE()')->fetchColumn() || $m['source_fingerprints']!==cleanup_fingerprints(cleanup_snapshot($pdo)))throw new RuntimeException('Source database changed; inspect before cleanup.');
    foreach($m['stages'] as $stage){$name=$stage['database'];if(!preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/',$name))throw new RuntimeException('Invalid disposable database');$pdo->exec("DROP DATABASE IF EXISTS `$name`");}
    echo 'PASS: disposable UAT databases removed; source data unchanged.',PHP_EOL;exit;
}
$dir=realpath($opts['private-dir']??'');$root=dirname(__DIR__);
if(!$dir || str_starts_with(strtolower(str_replace('\\','/',$dir)).'/',strtolower(str_replace('\\','/',$root)).'/'))throw new RuntimeException('Existing private directory outside web required.');
$backup=$opts['backup']??'';$meta=json_decode((string)file_get_contents($backup.'.json'),true,512,JSON_THROW_ON_ERROR);$before=cleanup_fingerprints(cleanup_snapshot($pdo));
if(empty($meta['restore_verified']) || !hash_equals($meta['sha256'],hash_file('sha256',$backup)) || $meta['fingerprints']!==$before)throw new RuntimeException('Current restored backup required.');
$manifest=['source_database'=>$meta['database'],'source_fingerprints'=>$before,'stages'=>[]];$manifestPath=$dir.'/role_uat.json';
foreach(['registration','results'] as $stage){
    $name='ts10_workflow_'.date('YmdHis').'_'.bin2hex(random_bytes(4));$pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $manifest['stages'][$stage]=['database'=>$name];file_put_contents($manifestPath,json_encode($manifest,JSON_PRETTY_PRINT));
    $env=getenv();$env['MYSQL_PWD']=getenv('DB_PASS')?:'';$env['DB_NAME']=$name;
    $restore=proc_open(['C:/xampp/mysql/bin/mysql.exe','--host='.(getenv('DB_HOST')?:'127.0.0.1'),'--port='.(getenv('DB_PORT')?:'3306'),'--user='.(getenv('DB_USER')?:'root'),'--default-character-set=utf8mb4',$name],[0=>['file',$backup,'r'],1=>['file',$dir.'/restore_'.$stage.'.log','w'],2=>['redirect',1]],$pipes,null,$env);
    if(!is_resource($restore) || proc_close($restore)!==0)throw new RuntimeException('Restore failed.');
    if($stage==='results'){
        $process=proc_open([PHP_BINARY,'-d','output_buffering=65536',__DIR__.'/batchEnrollment_runtime.php'],[0=>['pipe','r'],1=>['file',$dir.'/results_fixture.log','w'],2=>['redirect',1]],$pipes,$root,$env);fclose($pipes[0]);if(proc_close($process)!==0)throw new RuntimeException('Result fixture failed.');
    }
    $copy=new PDO('mysql:host='.(getenv('DB_HOST')?:'127.0.0.1').';port='.(getenv('DB_PORT')?:'3306').';dbname='.$name.';charset=utf8mb4',getenv('DB_USER')?:'root',getenv('DB_PASS')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    require_once __DIR__.'/../includes/admission_service.php';require_once __DIR__.'/../includes/school_admission_service.php';
    $admin=(int)$copy->query('SELECT ur.user_id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.code IN ("admin","so_gdtd") ORDER BY ur.user_id LIMIT 1')->fetchColumn();
    if($stage==='registration'){
        $data=['name'=>'[MÔ PHỎNG] Nghiệm thu thao tác TP.HCM','academic_year'=>'2026-2027','mode'=>'simulation'];$now=new DateTimeImmutable();foreach(['registration'=>[-1,2],'adjustment'=>[3,4],'appeal'=>[5,6],'enrollment'=>[7,8]] as $key=>[$begin,$end])foreach(['start'=>$begin,'end'=>$end] as $edge=>$hours)$data[$key.'_'.$edge]=$now->modify(($hours>=0?'+':'').$hours.' hours')->format('Y-m-d H:i:s');
        $period=period_configuration_save($copy,0,$data,$admin);$quotas=[];foreach($copy->query('SELECT id FROM schools WHERE level="THPT" AND active=1') as $s)$quotas[$s['id']]=240;quota_save_all($copy,$period,$quotas);period_configuration_transition($copy,$period,'published',$admin,period_configuration($copy,$period)['revision']);
    }else{
        $period=(int)$copy->query('SELECT ar.admission_period_id FROM admission_results ar GROUP BY ar.admission_period_id ORDER BY COUNT(*) DESC LIMIT 1')->fetchColumn();
        $copy->exec('UPDATE admission_periods SET status="closed" WHERE status="published"');$copy->prepare('UPDATE admission_periods SET status="published",enrollment_start=?,enrollment_end=? WHERE id=?')->execute([date('Y-m-d H:i:s',time()-3600),date('Y-m-d H:i:s',time()+7200),$period]);
        $cfg=period_configuration($copy,$period)['settings'];$cfg['enrollment_start']=date('Y-m-d H:i:s',time()-3600);$cfg['enrollment_end']=date('Y-m-d H:i:s',time()+7200);$copy->prepare('UPDATE admission_period_configs SET settings=? WHERE admission_period_id=?')->execute([selection_json($cfg),$period]);
    }
    $profiles=['admin'=>['user_id'=>$admin,'route'=>'/admin/index.php']];$schools=$copy->query('SELECT DISTINCT s.id,s.name FROM schools s JOIN staff st ON st.school_id=s.id WHERE s.level="THCS" ORDER BY s.id')->fetchAll();
    foreach($schools as $i=>$s){$letter=$i===0?'a':'b';$q=$copy->prepare('SELECT user_id FROM staff WHERE school_id=? AND active=1 LIMIT 1');$q->execute([$s['id']]);$profiles['teacher_'.$letter]=['user_id'=>(int)$q->fetchColumn(),'route'=>'/teacher/students.php','school'=>$s['name']];
        $sql='SELECT c.id,c.user_id,c.full_name,c.national_id FROM candidates c WHERE c.middle_school_id=? AND c.status="active" AND c.eligible=1';if($stage==='results')$sql.=' AND EXISTS(SELECT 1 FROM admission_results ar WHERE ar.candidate_id=c.id AND ar.admission_period_id='.(int)$period.' AND ar.result_status="admitted" AND ar.enrollment_status="pending" AND ar.admission_run_id=(SELECT MAX(r.id) FROM admission_runs r WHERE r.admission_period_id=ar.admission_period_id AND r.status="finalized"))';$q=$copy->prepare($sql.' ORDER BY c.id LIMIT 1');$q->execute([$s['id']]);$c=$q->fetch();$profiles['student_'.$letter]=['user_id'=>(int)$c['user_id'],'candidate_id'=>(int)$c['id'],'name'=>$c['full_name'],'national_id'=>$c['national_id'],'route'=>$stage==='registration'?'/aspirations/index.php?period_id='.$period:'/admission/results.php?period_id='.$period];
    }
    $sessions=$dir.'/sessions_'.$stage;if(!is_dir($sessions))mkdir($sessions,0700);$manifest['stages'][$stage]+=['period'=>$period,'profiles'=>$profiles,'sessions'=>$sessions];file_put_contents($manifestPath,json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    echo 'READY: ',$stage,' period ',$period,' database ',$name,PHP_EOL;
}
if(cleanup_fingerprints(cleanup_snapshot($pdo))!==$before)throw new RuntimeException('Source changed.');
echo 'PASS: source unchanged. Manifest: ',$manifestPath,PHP_EOL;
