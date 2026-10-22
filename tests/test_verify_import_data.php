<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/candidate_import_service.php';
$pdo=db(); $identifiers=[];
foreach(json_decode((string)file_get_contents(__DIR__.'/../data/import/schools_hcm.json'),true,512,JSON_THROW_ON_ERROR) as $school){
    $rows=candidate_import_parse_csv(__DIR__.'/../data/import/'.$school['slug'].'_360_hoc_sinh.csv');
    if(count($rows)!==360) throw new RuntimeException('Wrong student count');
    $classes=[];
    $find=$pdo->prepare('SELECT c.*,s.school_code,u.username FROM candidates c JOIN schools s ON s.id=c.middle_school_id JOIN users u ON u.id=c.user_id WHERE c.national_id=?');
    foreach($rows as $row){
        if(isset($identifiers[$row['national_id']])) throw new RuntimeException('Duplicate identifier');
        $identifiers[$row['national_id']]=true;
        if($row['middle_school_code']!==$school['school_code'] || !str_starts_with($row['national_id'],'079') || !str_contains($row['address'],'TP.HCM')) throw new RuntimeException('Wrong school or city');
        if(!preg_match('/^Lớp (9A[1-9]),/u',$row['address'],$class)) throw new RuntimeException('Wrong class');
        $classes[$class[1]]=($classes[$class[1]]??0)+1;
        $find->execute([$row['national_id']]); $saved=$find->fetch();
        if(!$saved || $saved['username']!==$row['national_id'] || $saved['school_code']!==$school['school_code']) throw new RuntimeException('Missing saved profile/account');
        foreach(['full_name','date_of_birth','gender','address','phone','email','status'] as $field) if($saved[$field]!==$row[$field]) throw new RuntimeException('Round-trip mismatch: '.$field);
    }
    if(count($classes)!==9 || array_values(array_unique(array_values($classes)))!==[40]) throw new RuntimeException('Wrong class size');
    echo 'PASS: '.$school['school_code'].' matches 360 saved students (9 x 40).',PHP_EOL;
}
if(count($identifiers)!==720) throw new RuntimeException('Wrong total');
echo 'PASS: 720 rows match CSV and database; this verification does not change database records.',PHP_EOL;
