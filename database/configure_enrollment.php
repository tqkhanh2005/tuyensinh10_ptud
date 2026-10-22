<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
require_once __DIR__.'/../includes/enrollment_schema_setup.php';
$options=getopt('',['backup:']);$file=$options['backup']??'';
$meta=json_decode((string)file_get_contents($file.'.json'),true,512,JSON_THROW_ON_ERROR);$pdo=db();$before=cleanup_fingerprints(cleanup_snapshot($pdo));
if(empty($meta['restore_verified']) || !hash_equals($meta['sha256'],hash_file('sha256',$file)) || $meta['fingerprints']!==$before)throw new RuntimeException('A current restored-and-verified backup is required.');
$old=$pdo->query('SELECT id,admission_result_id,candidate_id,school_id,confirmed_at FROM enrollment_confirmations ORDER BY id')->fetchAll();
enrollment_schema_apply($pdo);
$after=cleanup_fingerprints(cleanup_snapshot($pdo));foreach($before as $table=>$fingerprint)if($table!=='enrollment_confirmations' && ($after[$table]??null)!==$fingerprint)throw new RuntimeException('Existing data changed in '.$table);
if($old!==$pdo->query('SELECT id,admission_result_id,candidate_id,school_id,confirmed_at FROM enrollment_confirmations ORDER BY id')->fetchAll())throw new RuntimeException('Existing confirmations changed.');
echo 'COMPLETE: enrollment schema ready; existing records preserved. No confirmations or results inserted.',PHP_EOL;
