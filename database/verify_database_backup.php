<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/test_data_cleanup_service.php';
$file=$argv[1]??'';
$metadata=json_decode((string)file_get_contents($file.'.json'),true,512,JSON_THROW_ON_ERROR);
if(!is_file($file) || !hash_equals($metadata['sha256'],hash_file('sha256',$file))) throw new RuntimeException('Backup checksum mismatch');
$pdo=db(); $live=cleanup_snapshot($pdo);
if($metadata['database']!==$live['database']) throw new RuntimeException('Backup database mismatch');
$clone='ts10_verify_'.date('YmdHis').'_'.bin2hex(random_bytes(4));
$created=false;
try {
    $pdo->exec("CREATE DATABASE `$clone` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created=true;
    $env=getenv(); $env['MYSQL_PWD']=getenv('DB_PASS')?:'';
    $command=['C:/xampp/mysql/bin/mysql.exe','--host='.(getenv('DB_HOST')?:'127.0.0.1'),'--port='.(getenv('DB_PORT')?:'3306'),'--user='.(getenv('DB_USER')?:'root'),'--default-character-set=utf8mb4',$clone];
    $process=proc_open($command,[0=>['file',$file,'r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);
    if(!is_resource($process)) throw new RuntimeException('Could not start restore verification');
    $output=stream_get_contents($pipes[1]); fclose($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[2]);
    if(proc_close($process)!==0) throw new RuntimeException('Restore verification failed: '.$error);
    $copy=new PDO('mysql:host='.(getenv('DB_HOST')?:'127.0.0.1').';port='.(getenv('DB_PORT')?:'3306').';dbname='.$clone.';charset=utf8mb4',getenv('DB_USER')?:'root',getenv('DB_PASS')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $restored=cleanup_snapshot($copy);
    if(cleanup_fingerprints($restored)!==cleanup_fingerprints($live)) throw new RuntimeException('Restored database does not exactly match current database');
    if(cleanup_fingerprints(cleanup_snapshot($pdo))!==cleanup_fingerprints($live)) throw new RuntimeException('Database changed during backup verification');
    $metadata['fingerprints']=cleanup_fingerprints($live);
    $metadata['restore_verified']=true;
    $metadata['verified_restore_at']=date(DATE_ATOM);
    file_put_contents($file.'.json',json_encode($metadata,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    echo 'PASS: restored backup matches all '.count($live['rows']).' tables and every saved row.',PHP_EOL;
    $plan=cleanup_plan($live);
    echo json_encode(['cleanup_plan'=>$plan['counts'],'total'=>array_sum($plan['counts']),'protected_users'=>$plan['protected_users']],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
    if(in_array('--scope-test',$argv,true)) {
        require_once __DIR__ . '/../tests/test_cleanup_scope.php';
        echo 'Cleanup scope verification: ',json_encode(verify_cleanup_scope($copy),JSON_UNESCAPED_UNICODE),PHP_EOL;
    }
} finally {
    $copy=null;
    if($created && preg_match('/^ts10_verify_[0-9]{14}_[a-f0-9]{8}$/',$clone)) $pdo->exec("DROP DATABASE `$clone`");
}
