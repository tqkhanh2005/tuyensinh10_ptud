<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/admission_selection_service.php';
$checks=0;
function check(bool $ok,string $message): void {global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
$input=['policy'=>['priority_groups'=>['none'=>0,'priority'=>1.5],'encouragement_awards'=>['none'=>0],'bonus_cap'=>3.5],'subjects'=>[['id'=>1,'name'=>'Ngữ văn'],['id'=>2,'name'=>'Toán'],['id'=>3,'name'=>'Ngoại ngữ']],'bonus_reviews'=>[],'quotas'=>[],'candidates'=>[],'papers'=>[],'aspirations'=>[]];
foreach([1,2,3] as $id)$input['quotas'][]=['school_id'=>$id,'quota'=>1,'status'=>'set','active'=>1,'level'=>'THPT','name'=>'Trường mô phỏng '.$id];
foreach([1=>7.5,2=>8,3=>6] as $id=>$mark){
    $input['candidates'][]=['id'=>$id,'full_name'=>'Thí sinh '.$id,'exam_number'=>'S'.$id,'status'=>'active','eligible'=>1,'user_active'=>1,'legacy_bonus'=>0];
    foreach([1,2,3] as $subject)$input['papers'][]=['candidate_id'=>$id,'exam_subject_id'=>$subject,'attendance_status'=>'present','penalty_percent'=>0,'paper_status'=>'matched','appeal_status'=>null,'original_score'=>$mark];
    foreach([1,2,3] as $nv)$input['aspirations'][]=['id'=>$id*10+$nv,'candidate_id'=>$id,'school_id'=>$nv,'priority_order'=>$nv];
}
$input['bonus_reviews'][]=['candidate_id'=>1,'priority_group'=>'priority','award_group'=>'none','evidence'=>'Minh chứng mô phỏng'];
$plan=selection_plan($input);
check(!$plan['errors'] && !$plan['tie'],'Valid plan');
check($plan['assigned'][2]===21 && $plan['assigned'][1]===12,'Equal admission scores prefer original subject total');
check($plan['assigned'][3]===33,'Unassigned candidate advances to NV3');
check($plan['used']===[1=>1,2=>1,3=>1],'Shared quotas respected');
$missing=$input;$missing['papers'][0]['original_score']=null;$p=selection_plan($missing);
check(!$p['errors'] && !$p['candidates'][1]['eligible_selection'] && !isset($p['assigned'][1]),'Missing score excludes only affected candidate');
$tie=$input;$tie['bonus_reviews']=[];foreach($tie['papers'] as &$paper)if($paper['candidate_id']===1)$paper['original_score']=8;unset($paper);$p=selection_plan($tie);
check($p['tie']!==null,'Equal raw scores at boundary block confirmation');
$appeal=$input;$appeal['papers'][0]['appeal_status']='completed';$appeal['papers'][0]['appeal_score']=9;$p=selection_plan($appeal);
check($p['candidates'][1]['score_total']===24.0,'Completed appeal replaces original score');
$absent=$input;$absent['papers'][0]['attendance_status']='absent';$p=selection_plan($absent);check(!$p['candidates'][1]['eligible_selection'],'Absence excluded');
$zero=$input;$zero['papers'][0]['original_score']=0;$p=selection_plan($zero);check(!$p['candidates'][1]['eligible_selection'],'Zero mark excluded');
$rounds=$input;$rounds['quotas'][1]['quota']=2;$rounds['aspirations'][6]['school_id']=2;$rounds['aspirations'][7]['school_id']=1;$p=selection_plan($rounds);
check($p['assigned'][3]===31 && $p['assigned'][1]===12,'Later rounds use remaining quota without earlier-round score floor');
check($p['cutoffs'][2]['minimum']===18.0,'School cutoff is minimum admitted score even below capacity');
echo "Passed $checks selection checks (no database writes).\n";
