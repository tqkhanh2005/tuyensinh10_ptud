<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/test_data_cleanup_service.php';

/** These fixtures are only allowed inside the disposable restored-backup database. */
function verify_cleanup_scope(PDO $pdo): array
{
    $original=cleanup_snapshot($pdo);
    if(!str_starts_with((string)$original['database'],'ts10_verify_')) throw new RuntimeException('Scope verification only runs on the disposable backup copy.');
    $clone=function(string $table,array $overrides) use($pdo,$original):array {
        $row=$original['rows'][$table][0]??null;
        if(!$row) throw new RuntimeException("Missing sample row for scope fixture $table");
        if(count($original['primary'][$table])===1 && $original['primary'][$table][0]==='id') unset($row['id']);
        foreach($original['foreign'] as $fk) if($fk['child']===$table && $fk['parent']==='users') foreach($fk['columns'] as $column=>$reference) $row[$column]=2;
        $row=array_replace($row,$overrides);
        $columns=array_keys($row);
        $sql="INSERT INTO `$table` (`".implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($columns),'?')).')';
        $pdo->prepare($sql)->execute(array_values($row));
        if(in_array('id',$original['primary'][$table],true)) $row['id']=(int)$pdo->lastInsertId();
        return $row;
    };
    $school=$clone('schools',['school_code'=>'THCS_GIU_LAI','name'=>'Trường THCS Nguyễn Du TP.HCM','address'=>'TP.HCM']);
    $period=$clone('admission_periods',['name'=>'Kỳ tuyển sinh TP.HCM được giữ lại','academic_year'=>'2026-2027']);
    $staff=$clone('staff',['user_id'=>null,'full_name'=>'Nguyễn Văn Minh','workplace'=>$school['name'],'staff_role'=>'Giáo viên']);
    $candidate=$clone('candidates',['user_id'=>null,'national_id'=>'079211990001','full_name'=>'Trần Minh Anh','middle_school_id'=>$school['id']]);
    $site=$clone('exam_sites',['admission_period_id'=>$period['id'],'school_id'=>$school['id'],'site_code'=>'DIEM_GIU_LAI','name'=>'Điểm thi được giữ lại']);
    $subject=$clone('exam_subjects',['admission_period_id'=>$period['id'],'subject_code'=>'MON_GIU_LAI','name'=>'Toán']);
    $council=$clone('councils',['admission_period_id'=>$period['id'],'exam_site_id'=>$site['id'],'name'=>'Hội đồng được giữ lại']);
    $member=$clone('council_members',['council_id'=>$council['id'],'staff_id'=>$staff['id']]);
    $subjectLink=$clone('council_subjects',['council_id'=>$council['id'],'exam_subject_id'=>$subject['id']]);
    $room=$clone('exam_rooms',['exam_site_id'=>$site['id'],'room_code'=>'A101']);
    $paper=$clone('exam_papers',['admission_period_id'=>$period['id'],'exam_subject_id'=>$subject['id'],'exam_room_id'=>$room['id'],'candidate_id'=>$candidate['id'],'anonymous_code'=>'PHACH_GIU_LAI']);
    $assignment=$clone('exam_paper_grader_assignments',['exam_paper_id'=>$paper['id'],'council_member_id'=>$member['id']]);
    $before=cleanup_snapshot($pdo);
    $plan=cleanup_plan($before);
    foreach(['council_subjects'=>$subjectLink,'exam_paper_grader_assignments'=>$assignment,'candidates'=>$candidate,'staff'=>$staff,'schools'=>$school,'admission_periods'=>$period] as $table=>$row) {
        if(isset($plan['selected'][$table][cleanup_key($row,$before['primary'][$table])])) throw new RuntimeException("Genuine fixture selected in $table");
    }
    $result=cleanup_execute($pdo,['database'=>$before['database'],'fingerprints'=>cleanup_fingerprints($before)]);
    $after=cleanup_snapshot($pdo);
    foreach(['council_subjects'=>$subjectLink,'exam_paper_grader_assignments'=>$assignment] as $table=>$row) {
        if(!in_array($row,$after['rows'][$table],false)) throw new RuntimeException("Genuine dependent row was deleted: $table");
    }
    $badSchool=$clone('schools',['school_code'=>'BATCH_SCOPE_SCHOOL','name'=>'BATCH scope school']);
    $clone('candidates',['user_id'=>null,'national_id'=>'079211990002','full_name'=>'Lê Hoàng An','middle_school_id'=>$badSchool['id']]);
    $blocked=false;
    try { cleanup_plan(cleanup_snapshot($pdo)); } catch(RuntimeException $e) { $blocked=str_contains($e->getMessage(),'manual classification'); }
    if(!$blocked) throw new RuntimeException('A genuine person linked to a test school must block cleanup.');
    return ['test_rows_deleted'=>$result['total_deleted'],'genuine_rows_preserved'=>true,'mixed_real_test_reference_blocked'=>true];
}
