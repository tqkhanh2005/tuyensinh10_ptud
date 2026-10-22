<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../includes/hcm_school_workflow_setup.php';
$options=getopt('', ['private-dir:']); $directory=realpath($options['private-dir'] ?? '') ?: '';
$root=strtolower(str_replace('\\','/',realpath(__DIR__.'/..')).'/');
if (!$directory || !is_writable($directory) || str_starts_with(strtolower(str_replace('\\','/',$directory).'/'),$root)) throw new RuntimeException('Supply a writable private directory outside the web project.');
$file=$directory.'/teacher_accounts.json';
$credentials=is_file($file) ? json_decode((string)file_get_contents($file),true,512,JSON_THROW_ON_ERROR) : [];
$persist=static function(array $account) use(&$credentials,$file): void {
    $credentials[$account['username']]=$account;
    if (file_put_contents($file,json_encode($credentials,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX)===false) throw new RuntimeException('Cannot save private credentials.');
};
$pdo=db();
$schools=hcm_high_schools_setup($pdo);
$teachers=hcm_teacher_setup($pdo,$persist);
echo json_encode(['schools'=>$schools,'teachers'=>$teachers,'private_credentials_file'=>$file],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
