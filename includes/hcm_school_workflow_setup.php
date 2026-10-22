<?php
declare(strict_types=1);
require_once __DIR__.'/staff_service.php';

function hcm_high_schools_setup(PDO $pdo): array
{
    $schools=json_decode((string)file_get_contents(__DIR__.'/../data/import/high_schools_hcm.json'),true,512,JSON_THROW_ON_ERROR);
    $result=[]; $pdo->beginTransaction();
    try {
        foreach ($schools as $school) {
            $find=$pdo->prepare('SELECT * FROM schools WHERE school_code=? OR name=? FOR UPDATE');
            $find->execute([$school['school_code'],$school['name']]); $rows=$find->fetchAll();
            if (count($rows)>1) throw new RuntimeException('Duplicate school identity.');
            if ($rows) {
                $row=$rows[0];
                if ($row['school_code']!==$school['school_code'] || $row['name']!==$school['name'] || $row['level']!=='THPT' || (int)$row['active']!==1) throw new RuntimeException('School identity mismatch.');
                $id=(int)$row['id'];
            } else {
                $insert=$pdo->prepare('INSERT INTO schools(school_code,name,level,address,district,phone,email,active) VALUES(?,?,"THPT",?,?,?,?,1)');
                $insert->execute([$school['school_code'],$school['name'],$school['address'],$school['district'],$school['phone'],$school['email']]);
                $id=(int)$pdo->lastInsertId();
            }
            $result[]=['id'=>$id,'name'=>$school['name'],'school_code'=>$school['school_code'],'source'=>$school['source']];
        }
        $pdo->commit(); return $result;
    } catch (Throwable $exception) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $exception; }
}

/** Synthetic staff for the two already-imported schools; never reset an existing account. */
function hcm_teacher_setup(PDO $pdo, callable $saveCredential): array
{
    $result=[];
    foreach ([['THCS_NGUYEN_DU','gv.thcs.nguyendu','Nguyễn Minh Tuấn'],['THCS_LE_QUY_DON','gv.thcs.lequydon','Trần Thị Thanh Mai']] as [$code,$username,$name]) {
        $find=$pdo->prepare('SELECT id,name FROM schools WHERE school_code=? AND level="THCS" AND active=1');
        $find->execute([$code]); $school=$find->fetch();
        if (!$school) throw new RuntimeException('Missing active source school: '.$code);
        $find=$pdo->prepare('SELECT u.id,u.active,st.id staff_id,st.school_id,st.active staff_active FROM users u LEFT JOIN staff st ON st.user_id=u.id WHERE u.username=?');
        $find->execute([$username]); $existing=$find->fetch();
        if ($existing) {
            $role=$pdo->prepare('SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id AND r.code="teacher" AND r.active=1 WHERE ur.user_id=?');
            $role->execute([$existing['id']]);
            if ((int)$existing['school_id']!==(int)$school['id'] || !$existing['active'] || !$existing['staff_active'] || !$role->fetchColumn()) throw new RuntimeException('Existing teacher account has an unexpected linkage: '.$username);
            $result[]=['username'=>$username,'school'=>$school['name'],'created'=>false]; continue;
        }
        $password='Gv!'.bin2hex(random_bytes(9));
        // Persist the intended credentials privately before creation, for recovery on interruption.
        $saveCredential(['username'=>$username,'password'=>$password,'school'=>$school['name'],'synthetic_staff'=>true]);
        $staffId=staff_create_with_account(['full_name'=>$name,'school_id'=>(int)$school['id'],'workplace'=>$school['name'],'phone'=>'','email'=>'','staff_role'=>'Giáo viên phụ trách lớp 9 (mô phỏng)'],['username'=>$username,'password'=>$password]);
        $result[]=['username'=>$username,'school'=>$school['name'],'staff_id'=>$staffId,'created'=>true];
    }
    return $result;
}
