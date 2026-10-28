<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(2);
require_once __DIR__.'/../includes/council_service.php';
$p=db();$checks=0;
function verify_cancel(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException($label);echo 'PASS ',++$checks,': ',$label,PHP_EOL;}
foreach(['admission_periods'=>'id INT PRIMARY KEY','councils'=>'id INT PRIMARY KEY,admission_period_id INT,council_type VARCHAR(30),exam_site_id INT,status VARCHAR(30)','council_members'=>'id INT PRIMARY KEY,council_id INT,staff_id INT','council_subjects'=>'council_id INT,exam_subject_id INT','exam_paper_grader_assignments'=>'id INT PRIMARY KEY,council_member_id INT','appeal_grader_assignments'=>'id INT PRIMARY KEY,council_member_id INT','exam_invigilator_assignments'=>'id INT PRIMARY KEY,exam_room_id INT,staff_id INT','exam_rooms'=>'id INT PRIMARY KEY,exam_site_id INT','exam_sites'=>'id INT PRIMARY KEY,admission_period_id INT'] as $t=>$ddl)$p->exec("CREATE TEMPORARY TABLE `$t` ($ddl) ENGINE=InnoDB");
$p->exec('INSERT INTO admission_periods VALUES(1)');
$p->exec("INSERT INTO councils VALUES(1,1,'question',NULL,'established'),(2,1,'grading',NULL,'established'),(3,1,'appeal',NULL,'established'),(4,1,'invigilation',1,'established')");
$p->exec('INSERT INTO council_members VALUES(1,1,1),(2,2,2),(3,3,3),(4,4,4)');
$p->exec('INSERT INTO council_subjects VALUES(1,1)');
verify_cancel(council_cancel($p,1)===1,'Cancel returns correct period');
verify_cancel($p->query('SELECT status FROM councils WHERE id=1')->fetchColumn()==='cancelled','Status changes');
verify_cancel((int)$p->query('SELECT COUNT(*) FROM council_members WHERE council_id=1')->fetchColumn()===1,'Member history retained');
verify_cancel((int)$p->query('SELECT COUNT(*) FROM council_subjects')->fetchColumn()===1,'Subject history retained');
$p->exec('INSERT INTO exam_paper_grader_assignments VALUES(1,2)');$p->exec('INSERT INTO appeal_grader_assignments VALUES(1,3)');
$p->exec('INSERT INTO exam_sites VALUES(1,1)');$p->exec('INSERT INTO exam_rooms VALUES(1,1)');$p->exec('INSERT INTO exam_invigilator_assignments VALUES(1,1,4)');
foreach([1,2,3,4,99] as $id){$failed=false;try{council_cancel($p,$id);}catch(InvalidArgumentException $e){$failed=true;}verify_cancel($failed,'Reject repeated, assigned or unknown council '.$id);verify_cancel(!$p->inTransaction(),'Rollback rejection '.$id);}
verify_cancel((int)$p->query('SELECT COUNT(*) FROM councils WHERE status="established"')->fetchColumn()===3,'Assigned councils unchanged');
echo "Passed $checks checks using temporary tables only.\n";
