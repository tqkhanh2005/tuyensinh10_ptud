<?php
declare(strict_types=1);

function import_school_setup(PDO $pdo): array
{
    $schools=json_decode((string)file_get_contents(__DIR__.'/../data/import/schools_hcm.json'),true,512,JSON_THROW_ON_ERROR);
    $result=[];
    $pdo->beginTransaction();
    try {
        foreach($schools as $school) {
            $find=$pdo->prepare('SELECT id,school_code,name,level,active FROM schools WHERE school_code=? OR name=? FOR UPDATE');
            $find->execute([$school['school_code'],$school['name']]);
            $existing=$find->fetchAll();
            if(count($existing)>1) throw new RuntimeException('Duplicate school identity: '.$school['name']);
            if($existing) {
                $row=$existing[0];
                if($row['school_code']!==$school['school_code'] || $row['name']!==$school['name'] || $row['level']!=='THCS' || (int)$row['active']!==1) throw new RuntimeException('School identity or activity mismatch: '.$school['name']);
                $id=(int)$row['id'];
            } else {
                $insert=$pdo->prepare('INSERT INTO schools (school_code,name,level,address,district,phone,email,active) VALUES (?,?,"THCS",?,?,?,?,1)');
                $insert->execute([$school['school_code'],$school['name'],$school['address'],'Phường '.$school['ward'],$school['phone'],$school['email']]);
                $id=(int)$pdo->lastInsertId();
            }
            $result[]=['id'=>$id,'school_code'=>$school['school_code'],'name'=>$school['name']];
        }
        $pdo->commit(); return $result;
    } catch(Throwable $e) { if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
