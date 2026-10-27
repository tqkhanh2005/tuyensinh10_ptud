<?php
declare(strict_types=1);
ini_set('session.save_path',sys_get_temp_dir());
require_once __DIR__.'/../includes/publication_service.php';
$checks=0;
function publication_check(bool $ok,string $label): void {global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
$valid=['exam_number'=>'TP001','paper_id'=>1,'attendance_status'=>'present','total_score'=>'8.25','paper_status'=>'matched','anonymous_code'=>'P001','score_code'=>'P001'];
publication_check(exam_publication_row_errors([$valid])===[],'Matched initial score is publishable');
publication_check((bool)exam_publication_row_errors([]),'Empty cohort blocked');
foreach(['exam_number'=>null,'paper_id'=>null,'total_score'=>null,'paper_status'=>'scored','score_code'=>'OTHER','attendance_status'=>'unknown'] as $key=>$value){$row=$valid;$row[$key]=$value;publication_check((bool)exam_publication_row_errors([$row]),'Invalid '.$key.' blocked');}
foreach([-1,11,'not a score',INF] as $score){$row=$valid;$row['total_score']=$score;publication_check((bool)exam_publication_row_errors([$row]),'Invalid score blocked');}
$row=$valid;$row['total_score']=0;publication_check(!exam_publication_row_errors([$row]),'Zero is a valid published score');
$row=$valid;$row['attendance_status']='absent';$row['total_score']=null;publication_check(!exam_publication_row_errors([$row]),'Absent candidate needs no invented score');
$row['total_score']=5;publication_check((bool)exam_publication_row_errors([$row]),'Absent candidate with score blocked');
echo "Passed $checks exam publication checks; no database writes.\n";
