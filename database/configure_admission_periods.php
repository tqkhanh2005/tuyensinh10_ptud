<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/admission_period_configuration.php';
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
$options=getopt('', ['backup:']);$file=$options['backup'] ?? '';
$meta=json_decode((string)file_get_contents($file.'.json'),true,512,JSON_THROW_ON_ERROR);$pdo=db();
if(empty($meta['restore_verified']) || !hash_equals($meta['sha256'],hash_file('sha256',$file)) || $meta['fingerprints']!==cleanup_fingerprints(cleanup_snapshot($pdo))) throw new RuntimeException('A current restored-and-verified backup is required.');
$pdo->exec((string)file_get_contents(__DIR__.'/015_admission_period_configuration.sql'));
$existing=$pdo->query('SELECT admission_period_id FROM admission_period_configs LIMIT 1')->fetchColumn();
if(!$existing) {
    $actor=(int)$pdo->query('SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN role_permissions rp ON rp.role_id=ur.role_id JOIN permissions p ON p.id=rp.permission_id WHERE p.code="admission.manage" AND u.active=1 ORDER BY u.id LIMIT 1')->fetchColumn();
    $existing=period_configuration_save($pdo,0,['name'=>'[MÔ PHỎNG] Tuyển sinh lớp 10 thường TP.HCM 2026-2027','academic_year'=>'2026-2027','mode'=>'simulation','plan_content'=>'Bản nháp phục vụ dự án với 720 học sinh giả lập. Thời hạn và chỉ tiêu chưa thiết lập; không phải kỳ tuyển sinh chính thức.','source_url'=>admission_policy()['source_urls'][0]],$actor);
}
echo 'COMPLETE: configuration schema ready; draft period ID ',(int)$existing,'. No dates or quotas invented.',PHP_EOL;
