<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
$options=getopt('',['backup:']);$file=$options['backup'] ?? '';
$meta=json_decode((string)file_get_contents($file.'.json'),true,512,JSON_THROW_ON_ERROR);$pdo=db();$before=cleanup_fingerprints(cleanup_snapshot($pdo));
if(empty($meta['restore_verified']) || !hash_equals($meta['sha256'],hash_file('sha256',$file)) || $meta['fingerprints']!==$before)throw new RuntimeException('A current restored-and-verified backup is required.');
$pdo->exec((string)file_get_contents(__DIR__.'/016_exam_organization.sql'));
$after=cleanup_fingerprints(cleanup_snapshot($pdo));
foreach($before as $table=>$fingerprint)if(($after[$table] ?? null)!==$fingerprint)throw new RuntimeException('Existing data changed in '.$table);
echo 'COMPLETE: organization schema ready; every existing table unchanged. No schedules, staff or exam fixtures inserted.',PHP_EOL;
