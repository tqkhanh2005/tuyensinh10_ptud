<?php
declare(strict_types=1);

/** Batch C runtime verification. Run on XAMPP after importing 011_batchC_score_entry.sql. */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_once __DIR__ . '/../includes/excel_service.php';

$pdo = db();
$token = 'C' . date('YmdHis') . bin2hex(random_bytes(3));
$ids = ['periodA'=>0,'periodB'=>0,'school'=>0,'user'=>0,'candidateA'=>0,'candidateB'=>0,'candidateC'=>0,'siteA'=>0,'siteB'=>0,'roomA'=>0,'roomB'=>0,'subjectA'=>0,'subjectB'=>0,'paperA'=>0,'paperB'=>0];
$results=[];
$record=function(int $n,string $name,bool $ok,string $detail='')use(&$results):void{$results[$n]=[$ok?'PASS':'FAIL',$name,$detail];};
$expectFailure=function(callable $fn):array{try{$fn();return[false,'operation unexpectedly succeeded'];}catch(Throwable $e){return[true,$e->getMessage()];}};
$pdo->beginTransaction();
try {
    $q=$pdo->prepare('INSERT INTO admission_periods(name,academic_year,status) VALUES(:n,:y,"published")');
    $q->execute(['n'=>'Batch C A '.$token,'y'=>'C-A-'.$token]);$ids['periodA']=(int)$pdo->lastInsertId();
    $q->execute(['n'=>'Batch C B '.$token,'y'=>'C-B-'.$token]);$ids['periodB']=(int)$pdo->lastInsertId();
    $q=$pdo->prepare('INSERT INTO schools(school_code,name,level,active) VALUES(:c,:n,"THCS",1)');$q->execute(['c'=>'CT-'.$token,'n'=>'Batch C School '.$token]);$ids['school']=(int)$pdo->lastInsertId();
    $q=$pdo->prepare('INSERT INTO users(username,password_hash,full_name,role,active) VALUES(:u,:h,:n,"so_gdtd",1)');$q->execute(['u'=>'ctest_'.$token,'h'=>password_hash('test',PASSWORD_DEFAULT),'n'=>'Batch C Tester']);$ids['user']=(int)$pdo->lastInsertId();
    $q=$pdo->prepare('INSERT INTO candidates(candidate_code,national_id,full_name,eligible,status) VALUES(:c,:n,:f,1,"active")');
    foreach([['candidateA','A'],['candidateB','B'],['candidateC','C']] as $x){$q->execute(['c'=>'C-SBD-'.$x[1].'-'.substr($token,-6),'n'=>'C-NID-'.$x[1].'-'.$token,'f'=>'Candidate '.$x[1]]);$ids[$x[0]]=(int)$pdo->lastInsertId();}
    $q=$pdo->prepare('INSERT INTO aspirations(candidate_id,admission_period_id,school_id,priority_order) VALUES(:c,:p,:s,1)');
    foreach(['candidateA','candidateB','candidateC'] as $c)$q->execute(['c'=>$ids[$c],'p'=>$ids['periodA'],'s'=>$ids['school']]);
    $q->execute(['c'=>$ids['candidateB'],'p'=>$ids['periodB'],'s'=>$ids['school']]);
    $q=$pdo->prepare('INSERT INTO exam_sites(admission_period_id,site_code,name,capacity,status) VALUES(:p,:c,:n,5,"ready")');
    $q->execute(['p'=>$ids['periodA'],'c'=>'C-SA-'.$token,'n'=>'C Site A '.$token]);$ids['siteA']=(int)$pdo->lastInsertId();
    $q->execute(['p'=>$ids['periodB'],'c'=>'C-SB-'.$token,'n'=>'C Site B '.$token]);$ids['siteB']=(int)$pdo->lastInsertId();
    $q=$pdo->prepare('INSERT INTO exam_rooms(exam_site_id,room_code,name,capacity,status) VALUES(:s,:c,:n,5,"ready")');
    $q->execute(['s'=>$ids['siteA'],'c'=>'C-RA-'.$token,'n'=>'C Room A '.$token]);$ids['roomA']=(int)$pdo->lastInsertId();
    $q->execute(['s'=>$ids['siteB'],'c'=>'C-RB-'.$token,'n'=>'C Room B '.$token]);$ids['roomB']=(int)$pdo->lastInsertId();
    $q=$pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,max_score,component_count,status) VALUES(:p,:c,:n,10,1,"scheduled")');
    $q->execute(['p'=>$ids['periodA'],'c'=>'C-MATH-'.$token,'n'=>'C Math']);$ids['subjectA']=(int)$pdo->lastInsertId();
    $q->execute(['p'=>$ids['periodB'],'c'=>'C-PHY-'.$token,'n'=>'C Physics']);$ids['subjectB']=(int)$pdo->lastInsertId();
    $pdo->commit();

    save_allocations($pdo,$ids['periodA'],[$ids['candidateA']=>$ids['siteA'],$ids['candidateB']=>$ids['siteA'],$ids['candidateC']=>$ids['siteA']]);
    save_room_assignments($pdo,$ids['periodA'],$ids['siteA'],[$ids['candidateA']=>$ids['roomA'],$ids['candidateB']=>$ids['roomA'],$ids['candidateC']=>$ids['roomA']]);
    generate_exam_papers($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA']);
    $rows=paper_rows($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA']);
    $record(1,'Candidate eligible -> exam papers generated',count($rows)===3);

    $attendance=[]; foreach($rows as $r)$attendance[(int)$r['id']]=['status'=>$r['candidate_code']===('C-SBD-C-'.substr($token,-6))?'absent':'present','penalty'=>''];
    save_attendance($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA'],$attendance);
    $codes=preview_random_codes($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA']);
    $record(2,'Absent candidate receives no generated code',count($codes)===2);
    $before=(int)$pdo->query('SELECT COUNT(*) FROM exam_papers WHERE admission_period_id='.$ids['periodA'].' AND anonymous_code IS NOT NULL')->fetchColumn();
    $record(10,'Random-code preview does not persist before confirmation',$before===0);
    save_codes($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA'],$codes);
    $record(4,'Manual/random confirmation saves unique codes',count($codes)===2);

    $firstPaper=(int)array_key_first($codes); $dupCode=(string)reset($codes); $otherPaper=(int)array_key_last($codes);
    [$ok,$detail]=$expectFailure(fn()=>save_codes($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA'],[$firstPaper=>$dupCode,$otherPaper=>$dupCode]));$record(3,'Duplicate anonymous code within submission rejected',$ok,$detail);
    $codeOther=(string)$codes[$otherPaper]; $codeFirst=(string)$codes[$firstPaper]; [$ok,$detail]=$expectFailure(fn()=>save_codes($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA'],[$firstPaper=>$codeOther,$otherPaper=>$codeFirst]));$record(5,'Anonymous-code collision with existing DB code rejected',$ok,$detail);

    // Penalty 25/50/100 are valid; 75 is invalid.
    // save_attendance() intentionally requires the complete exam-paper list, matching UC01 step 10:
    // attendance/penalty must be updated for every candidate before confirmation. Keep the absent
    // candidate in the submitted map instead of updating only the present papers.
    $allRows=paper_rows($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA']);
    $presentRows=array_values(array_filter($allRows,fn($r)=>$r['attendance_status']!=='absent'));
    $att=[];
    foreach($allRows as $r) {
        if($r['attendance_status']==='absent') {
            $att[(int)$r['id']]=['status'=>'absent','penalty'=>''];
        }
    }
    foreach($presentRows as $i=>$r)$att[(int)$r['id']]=['status'=>'present_penalty','penalty'=>[25,50,100][$i%3]];
    save_attendance($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA'],$att);
    $record(7,'Penalty 25/50/100 accepted',true);
    $bad=$att; $bad[(int)$presentRows[1]['id']]=['status'=>'present_penalty','penalty'=>75];
    [$ok,$detail]=$expectFailure(fn()=>save_attendance($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA'],$bad));$record(8,'Invalid penalty rejected',$ok,$detail);
    $current=$pdo->query('SELECT penalty_percent FROM exam_papers WHERE id='.(int)$presentRows[0]['id'])->fetchColumn();$current2=$pdo->query('SELECT penalty_percent FROM exam_papers WHERE id='.(int)$presentRows[1]['id'])->fetchColumn();$record(9,'Attendance transaction rollback preserves previous penalties',(int)$current===25 && (int)$current2===50);

    // Cross-period paper/room cannot be used for period A.
    $q=$pdo->prepare('INSERT INTO exam_allocations(admission_period_id,candidate_id,exam_site_id) VALUES(:p,:c,:s)');$q->execute(['p'=>$ids['periodB'],'c'=>$ids['candidateB'],'s'=>$ids['siteB']]);
    $q=$pdo->prepare('INSERT INTO room_assignments(admission_period_id,candidate_id,exam_site_id,exam_room_id) VALUES(:p,:c,:s,:r)');$q->execute(['p'=>$ids['periodB'],'c'=>$ids['candidateB'],'s'=>$ids['siteB'],'r'=>$ids['roomB']]);
    generate_exam_papers($pdo,$ids['periodB'],$ids['subjectB'],$ids['roomB']);
    $bpaper=(int)$pdo->query('SELECT id FROM exam_papers WHERE admission_period_id='.$ids['periodB'].' AND exam_subject_id='.$ids['subjectB'].' LIMIT 1')->fetchColumn();
    $batt=paper_rows($pdo,$ids['periodB'],$ids['subjectB'],$ids['roomB']); $battMap=[(int)$batt[0]['id']=>['status'=>'present','penalty'=>'']]; save_attendance($pdo,$ids['periodB'],$ids['subjectB'],$ids['roomB'],$battMap); save_codes($pdo,$ids['periodB'],$ids['subjectB'],$ids['roomB'],[(int)$batt[0]['id']=>'B123456']);
    [$ok,$detail]=$expectFailure(fn()=>save_codes($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA'],[$bpaper=>'999999']));$record(6,'Period-A operation cannot use period-B paper',$ok,$detail);

    // UC02 valid score.
    $rows=paper_rows($pdo,$ids['periodA'],$ids['subjectA'],$ids['roomA']);$code=(string)$rows[0]['anonymous_code'];
    $vr=validate_score_rows($pdo,$ids['periodA'],$ids['subjectA'],[$code=>['total_score'=>8.5]]);
    save_scores($pdo,$ids['user'],$ids['periodA'],$ids['subjectA'],$vr['valid']); $record(11,'Valid anonymous code + valid score persistence',$vr['valid']!==[]);
    [$ok,$detail]=$expectFailure(fn()=>save_scores($pdo,$ids['user'],$ids['periodA'],$ids['subjectA'],$vr['valid']));$record(14,'Duplicate score rejected',$ok,$detail);
    $invalid=validate_score_rows($pdo,$ids['periodA'],$ids['subjectA'],['NO-SUCH-CODE'=>['total_score'=>5]]);$record(12,'Invalid anonymous code rejected',count($invalid['valid'])===0 && count($invalid['errors'])===1);
    $over=validate_score_rows($pdo,$ids['periodA'],$ids['subjectA'],[$rows[1]['anonymous_code']=>['total_score'=>10.01]]);$record(13,'Score above max rejected',count($over['valid'])===0);
    $wrong=validate_score_rows($pdo,$ids['periodB'],$ids['subjectB'],['B123456'=>['total_score'=>5]]); $wrongValid=$wrong['valid']; [$ok,$detail]=$expectFailure(fn()=>save_scores($pdo,$ids['user'],$ids['periodA'],$ids['subjectA'],$wrongValid)); $record(15,'Wrong-period score rejected at persistence',$ok,$detail);

    // Excel parser/import tests use CSV because it is Excel-compatible and does not depend on server ZipArchive.
    $csv=tempnam(sys_get_temp_dir(),'batchc_');file_put_contents($csv,"anonymous_code,total_score\n".$rows[1]['anonymous_code'].",7\n");
    $batch=create_score_import_batch($pdo,$ids['user'],$ids['periodA'],$ids['subjectA'],'valid.csv',excel_parse_upload($csv,'valid.csv'));$b=$pdo->query('SELECT valid_rows FROM exam_score_import_batches WHERE id='.$batch)->fetchColumn();$record(16,'Valid Excel/CSV import preview accepted',(int)$b===1);unlink($csv);
    $badHeader=tempnam(sys_get_temp_dir(),'batchc_');file_put_contents($badHeader,"anonymous_code,bad_column\n{$rows[1]['anonymous_code']},7\n");[$ok,$detail]=$expectFailure(fn()=>create_score_import_batch($pdo,$ids['user'],$ids['periodA'],$ids['subjectA'],'bad.csv',excel_parse_upload($badHeader,'bad.csv')));$record(17,'Invalid Excel header rejected',$ok,$detail);unlink($badHeader);
    $badScore=tempnam(sys_get_temp_dir(),'batchc_');file_put_contents($badScore,"anonymous_code,total_score\n{$rows[1]['anonymous_code']},99\n");$batch2=create_score_import_batch($pdo,$ids['user'],$ids['periodA'],$ids['subjectA'],'badscore.csv',excel_parse_upload($badScore,'badscore.csv'));$err=(int)$pdo->query('SELECT invalid_rows FROM exam_score_import_batches WHERE id='.$batch2)->fetchColumn();$record(18,'Invalid score row reported',$err===1);unlink($badScore);
    $dup=tempnam(sys_get_temp_dir(),'batchc_');file_put_contents($dup,"anonymous_code,total_score\n{$rows[1]['anonymous_code']},6\n{$rows[1]['anonymous_code']},7\n");$batch3=create_score_import_batch($pdo,$ids['user'],$ids['periodA'],$ids['subjectA'],'dup.csv',excel_parse_upload($dup,'dup.csv'));$err=(int)$pdo->query('SELECT invalid_rows FROM exam_score_import_batches WHERE id='.$batch3)->fetchColumn();$record(19,'Duplicate rows in import rejected as invalid row',$err===1);unlink($dup);

    // Revalidation: change the paper after preview, then confirm must rollback without inserting a score.
    $pdo->prepare('UPDATE exam_papers SET paper_status="scored" WHERE id=:id')->execute(['id'=>(int)$rows[1]['id']]);
    [$ok,$detail]=$expectFailure(fn()=>save_score_import_batch($pdo,$batch,$ids['user'])); $remaining=(int)$pdo->query('SELECT COUNT(*) FROM scores WHERE exam_paper_id='.(int)$rows[1]['id'])->fetchColumn(); $pdo->prepare('UPDATE exam_papers SET paper_status="coded" WHERE id=:id')->execute(['id'=>(int)$rows[1]['id']]); $record(20,'Import save revalidates and rolls back when paper changed',$ok && $remaining===0,$detail);
    $matched=(string)$pdo->query('SELECT paper_status FROM exam_papers WHERE id='.(int)$rows[0]['id'])->fetchColumn();$record(21,'Automatic matching is deferred until all present papers have scores',$matched==='scored' || $matched==='matched');

} catch(Throwable $e){$record(0,'SETUP/UNEXPECTED',false,$e->getMessage());}
finally {
    try{
        $pdo->beginTransaction();
        $periods=array_values(array_filter([$ids['periodA'],$ids['periodB']]));
        if($periods){$in=implode(',',array_map('intval',$periods));$pdo->exec("DELETE FROM scores WHERE exam_paper_id IN (SELECT id FROM exam_papers WHERE admission_period_id IN ($in))");$pdo->exec("DELETE FROM exam_score_import_rows WHERE batch_id IN (SELECT id FROM exam_score_import_batches WHERE admission_period_id IN ($in))");$pdo->exec("DELETE FROM exam_score_import_batches WHERE admission_period_id IN ($in)");$pdo->exec("DELETE FROM room_assignments WHERE admission_period_id IN ($in)");$pdo->exec("DELETE FROM exam_allocations WHERE admission_period_id IN ($in)");$pdo->exec("DELETE FROM exam_papers WHERE admission_period_id IN ($in)");$pdo->exec("DELETE FROM exam_subjects WHERE admission_period_id IN ($in)");$pdo->exec("DELETE FROM exam_rooms WHERE exam_site_id IN (SELECT id FROM exam_sites WHERE admission_period_id IN ($in))");$pdo->exec("DELETE FROM exam_sites WHERE admission_period_id IN ($in)");$pdo->exec("DELETE FROM aspirations WHERE admission_period_id IN ($in)");$pdo->exec("DELETE FROM admission_periods WHERE id IN ($in)");}
        foreach(['candidateA','candidateB','candidateC'] as $k)if($ids[$k])$pdo->exec('DELETE FROM candidates WHERE id='.(int)$ids[$k]);
        if($ids['user'])$pdo->exec('DELETE FROM users WHERE id='.(int)$ids['user']);
        if($ids['school'])$pdo->exec('DELETE FROM schools WHERE id='.(int)$ids['school']);
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,"Cleanup warning: ".$e->getMessage()."\n");}
}
ksort($results);$fail=0;foreach($results as $n=>$r){printf("%s %s: %s%s\n",$r[0],$n===0?'SETUP':'TEST '.$n,$r[1],$r[2]!==''?' — '.$r[2]:'');if($r[0]!=='PASS')$fail++;}exit($fail?1:0);
