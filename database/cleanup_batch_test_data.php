<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/test_data_cleanup_service.php';

// Default is read-only. Execute requires a checksum-verified and restore-tested full backup.
$options=getopt('', ['execute','backup:','report:']);
$pdo=db();
if(!isset($options['execute'])) {
    $plan=cleanup_plan(cleanup_snapshot($pdo));
    echo json_encode(['mode'=>'dry-run','delete'=>$plan['counts'],'total'=>array_sum($plan['counts']),'protected_user_ids'=>$plan['protected_users']],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
    exit;
}
$file=$options['backup']??'';
if(!$file || !is_file($file) || !is_file($file.'.json')) throw new RuntimeException('A verified backup is required (--backup=path.sql).');
$metadata=json_decode((string)file_get_contents($file.'.json'),true,512,JSON_THROW_ON_ERROR);
if(empty($metadata['restore_verified']) || !hash_equals($metadata['sha256'],hash_file('sha256',$file))) throw new RuntimeException('Backup has not passed restore/checksum verification.');
$result=cleanup_execute($pdo,$metadata);
$result['backup_file']=$file; $result['completed_at']=date(DATE_ATOM);
if(isset($options['report'])) file_put_contents($options['report'],json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
