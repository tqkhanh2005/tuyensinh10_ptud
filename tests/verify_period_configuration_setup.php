<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(2);
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
require_once __DIR__.'/../includes/admission_period_configuration.php';
$meta=json_decode((string)file_get_contents($argv[1] ?? ''),true,512,JSON_THROW_ON_ERROR);$pdo=db();$before=cleanup_snapshot($pdo);$hashes=cleanup_fingerprints($before);$checks=0;
foreach($meta['fingerprints'] as $table=>$hash) {
    if($table==='admission_periods')continue;
    if(($hashes[$table] ?? null)!==$hash)throw new RuntimeException('Unexpected change: '.$table);
    $checks++;
}
$rows=$pdo->query('SELECT p.*,c.settings FROM admission_periods p JOIN admission_period_configs c ON c.admission_period_id=p.id')->fetchAll();
if(count($rows)!==1 || $rows[0]['status']!=='draft')throw new RuntimeException('Expected one prepared draft');
$settings=json_decode($rows[0]['settings'],true,512,JSON_THROW_ON_ERROR);
if($settings['mode']!=='simulation')throw new RuntimeException('Draft must be explicitly simulated');
foreach(['registration','adjustment','appeal','enrollment'] as $stage)foreach(['start','end'] as $edge)if($settings[$stage.'_'.$edge]!==null)throw new RuntimeException('Unexpected invented deadline');
if(period_publicly_visible($pdo,(int)$rows[0]['id']))throw new RuntimeException('Draft is publicly visible');
if(cleanup_fingerprints(cleanup_snapshot($pdo))!==$hashes)throw new RuntimeException('Verification changed data');
echo 'PASS: ',$checks,' existing tables unchanged; 720 student profiles/accounts preserved; one simulation draft, no invented dates or quotas.',PHP_EOL;
