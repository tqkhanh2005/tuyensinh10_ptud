<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
$options=getopt('', ['before-metadata:','credentials:']);
$metadata=json_decode((string)file_get_contents($options['before-metadata'] ?? ''),true,512,JSON_THROW_ON_ERROR);
$credentials=json_decode((string)file_get_contents($options['credentials'] ?? ''),true,512,JSON_THROW_ON_ERROR);
$pdo=db(); $snapshot=cleanup_snapshot($pdo); $checks=0;
$assert=static function(bool $ok,string $message) use(&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    echo 'PASS: ',$message,PHP_EOL; $checks++;
};
$assert(cleanup_fingerprints($snapshot)['candidates']===$metadata['fingerprints']['candidates'],'Every imported candidate profile is unchanged');
foreach (['roles','permissions','role_permissions','admission_periods','aspirations','admission_quotas'] as $table) $assert(cleanup_fingerprints($snapshot)[$table]===$metadata['fingerprints'][$table],'Preserve '.$table);
foreach ($credentials as $account) {
    $stmt=$pdo->prepare('SELECT u.password_hash,u.active,st.active staff_active,s.name school,s.level,s.active school_active,(SELECT COUNT(*) FROM candidates c WHERE c.middle_school_id=s.id) students,(SELECT COUNT(*) FROM user_roles ur JOIN roles r ON r.id=ur.role_id AND r.code="teacher" AND r.active=1 WHERE ur.user_id=u.id) teacher_role FROM users u JOIN staff st ON st.user_id=u.id JOIN schools s ON s.id=st.school_id WHERE u.username=?');
    $stmt->execute([$account['username']]); $saved=$stmt->fetch();
    $assert($saved && $saved['school']===$account['school'] && $saved['level']==='THCS' && (int)$saved['students']===360 && $saved['active'] && $saved['staff_active'] && $saved['school_active'] && (int)$saved['teacher_role']===1 && password_verify($account['password'],$saved['password_hash']),'Teacher credential and 360-student school linkage: '.$account['username']);
}
foreach (json_decode((string)file_get_contents(__DIR__.'/../data/import/high_schools_hcm.json'),true) as $school) {
    $stmt=$pdo->prepare('SELECT * FROM schools WHERE school_code=?'); $stmt->execute([$school['school_code']]); $saved=$stmt->fetch();
    $assert($saved && $saved['name']===$school['name'] && $saved['address']===$school['address'] && $saved['level']==='THPT' && (int)$saved['active']===1,'Verified high school directory: '.$school['school_code']);
}
$assert(cleanup_fingerprints(cleanup_snapshot($pdo))===cleanup_fingerprints($snapshot),'Read-only verification makes no database changes');
echo 'COMPLETE: ',$checks,' setup checks passed.',PHP_EOL;
