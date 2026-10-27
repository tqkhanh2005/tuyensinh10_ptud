<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php';
require_once __DIR__.'/exam_grading_service.php';

function selection_json(array $value): string {return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
function selection_schema(PDO $pdo): bool {return (bool)$pdo->query("SHOW TABLES LIKE 'admission_run_snapshots'")->fetchColumn();}
function selection_actor(PDO $pdo,int $actor): void {period_manager($pdo,$actor);}
function selection_lock(PDO $pdo,int $period): array {
    $q=$pdo->prepare('SELECT status FROM admission_periods WHERE id=? FOR UPDATE');$q->execute([$period]);$status=$q->fetchColumn();$cfg=period_configuration($pdo,$period);
    if(!$status || !$cfg)throw new RuntimeException('Kỳ chưa có cấu hình theo năm.');
    $q=$pdo->prepare('SELECT 1 FROM publication_events WHERE admission_period_id=? AND publication_type="admission_results" AND status="published"');$q->execute([$period]);if($q->fetchColumn())throw new RuntimeException('Kết quả tuyển sinh đã được công bố và khóa.');
    if($status!=='published')throw new RuntimeException('Kỳ chưa công bố hoặc đã đóng.');
    if($cfg['settings']['mode']!=='simulation')throw new RuntimeException('Bộ máy xét tuyển hiện tại chỉ dùng mô phỏng; kỳ chính thức cần nghiệm thu riêng.');
    if(!selection_schema($pdo))throw new RuntimeException('Chưa cập nhật cấu trúc xét tuyển.');
    return $cfg;
}
function selection_bonus_save(PDO $pdo,int $period,int $candidate,string $priority,string $award,string $evidence,int $actor): void {
    selection_actor($pdo,$actor);$policy=admission_policy();$evidence=trim($evidence);
    if(!isset($policy['priority_groups'][$priority],$policy['encouragement_awards'][$award]) || mb_strlen($evidence)<10 || mb_strlen($evidence)>4000)throw new InvalidArgumentException('Chọn đúng nhóm và ghi minh chứng/biên bản từ 10–4000 ký tự.');
    $pdo->beginTransaction();try{
        selection_lock($pdo,$period);
        $q=$pdo->prepare('SELECT 1 FROM aspirations WHERE admission_period_id=? AND candidate_id=? LIMIT 1');$q->execute([$period,$candidate]);if(!$q->fetchColumn())throw new InvalidArgumentException('Học sinh không đăng ký kỳ này.');
        $previous=$pdo->prepare('SELECT * FROM admission_bonus_reviews WHERE admission_period_id=? AND candidate_id=? FOR UPDATE');$previous->execute([$period,$candidate]);$previous=$previous->fetch() ?: null;
        $pdo->prepare('INSERT INTO admission_bonus_reviews(admission_period_id,candidate_id,priority_group,award_group,evidence,reviewed_by) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE priority_group=VALUES(priority_group),award_group=VALUES(award_group),evidence=VALUES(evidence),reviewed_by=VALUES(reviewed_by),reviewed_at=NOW()')->execute([$period,$candidate,$priority,$award,$evidence,$actor]);
        period_event($pdo,$period,$actor,'selection_bonus',['candidate'=>$candidate,'priority'=>$priority,'award'=>$award,'evidence'=>$evidence,'previous'=>$previous]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
/** Capture exactly the records that determine selection; the same capture verifies a saved run. */
function selection_inputs(PDO $pdo,int $period): array {
    $fetch=static function(string $sql)use($pdo,$period):array{$q=$pdo->prepare($sql.($pdo->inTransaction()?' FOR UPDATE':''));$q->execute([$period]);return $q->fetchAll();};
    $cfg=period_configuration($pdo,$period);
    // A missing number must not remove a candidate from the audit population.
    $q=$pdo->prepare('SELECT c.id,c.candidate_code,c.full_name,c.eligible,c.status,u.active user_active,n.exam_number,pp.priority_points legacy_bonus FROM candidates c LEFT JOIN users u ON u.id=c.user_id LEFT JOIN exam_candidate_numbers n ON n.candidate_id=c.id AND n.admission_period_id=? LEFT JOIN candidate_priority_points pp ON pp.candidate_id=c.id AND pp.admission_period_id=? WHERE EXISTS(SELECT 1 FROM aspirations a WHERE a.candidate_id=c.id AND a.admission_period_id=?) ORDER BY c.id'.($pdo->inTransaction()?' FOR UPDATE':''));$q->execute([$period,$period,$period]);$candidates=$q->fetchAll();
    return ['policy'=>admission_policy(),'configuration'=>$cfg['settings'] ?? [],'grading'=>grading_state($pdo,$period),'candidates'=>$candidates,
        'aspirations'=>$fetch('SELECT id,candidate_id,school_id,priority_order FROM aspirations WHERE admission_period_id=? ORDER BY candidate_id,priority_order,id'),
        'quotas'=>$fetch('SELECT aq.school_id,aq.quota,aq.status,s.name,s.school_code,s.level,s.active FROM admission_quotas aq JOIN schools s ON s.id=aq.school_id WHERE aq.admission_period_id=? ORDER BY aq.school_id'),
        'subjects'=>$fetch('SELECT id,name,subject_code,max_score,component_count FROM exam_subjects WHERE admission_period_id=? ORDER BY id'),
        'papers'=>$fetch('SELECT ep.id,ep.candidate_id,ep.exam_subject_id,ep.attendance_status,ep.penalty_percent,ep.paper_status,ep.anonymous_code,sc.total_score original_score,apr.new_score appeal_score,ar.status appeal_status FROM exam_papers ep LEFT JOIN scores sc ON sc.exam_paper_id=ep.id LEFT JOIN appeal_requests ar ON ar.exam_paper_id=ep.id LEFT JOIN appeal_results apr ON apr.appeal_request_id=ar.id WHERE ep.admission_period_id=? ORDER BY ep.candidate_id,ep.exam_subject_id'),
        'bonus_reviews'=>$fetch('SELECT candidate_id,priority_group,award_group,evidence,reviewed_by,reviewed_at FROM admission_bonus_reviews WHERE admission_period_id=? ORDER BY candidate_id')];
}
function selection_input_hash(array $input): string {return hash('sha256',selection_json($input));}
function selection_prerequisites(PDO $pdo,int $period): array {
    $errors=[];$q=$pdo->prepare('SELECT status FROM admission_periods WHERE id=?');$q->execute([$period]);if($q->fetchColumn()!=='published')$errors[]='Kỳ chưa công bố hoặc đã đóng.';
    $cfg=period_configuration($pdo,$period);if(!$cfg)return ['Kỳ chưa có cấu hình theo năm.'];
    if($cfg['settings']['mode']!=='simulation')$errors[]='Bộ máy xét tuyển hiện tại chỉ dùng mô phỏng; cần nghiệm thu riêng cho kỳ chính thức.';
    if($cfg['settings']['academic_year']!==admission_policy()['academic_year'])$errors[]='Chưa có quy tắc cho năm học này.';
    if(!selection_schema($pdo))return [...$errors,'Chưa cập nhật cấu trúc xét tuyển.'];
    if(!grading_state($pdo,$period))$errors[]='Chưa rà soát và khóa điểm thi.';
    $q=$pdo->prepare('SELECT 1 FROM publication_events WHERE admission_period_id=? AND publication_type="exam_results" AND status="published"');$q->execute([$period]);if(!$q->fetchColumn())$errors[]='Chưa công bố điểm thi.';
    if(empty($cfg['settings']['appeal_end']) || $cfg['settings']['appeal_end']>=date('Y-m-d H:i:s'))$errors[]='Chưa kết thúc thời hạn phúc khảo.';
    $q=$pdo->prepare('SELECT COUNT(*) FROM appeal_requests WHERE admission_period_id=? AND status IN ("pending","grading")');$q->execute([$period]);if($q->fetchColumn())$errors[]='Vẫn còn yêu cầu phúc khảo chưa có kết quả.';
    $q=$pdo->prepare('SELECT COUNT(*) FROM aspirations WHERE admission_period_id=?');$q->execute([$period]);if(!$q->fetchColumn())$errors[]='Chưa có nguyện vọng.';
    $q=$pdo->prepare('SELECT COUNT(*) FROM admission_quotas q JOIN schools s ON s.id=q.school_id AND s.active=1 AND s.level="THPT" WHERE q.admission_period_id=? AND q.status="set" AND q.quota>0');$q->execute([$period]);if(!$q->fetchColumn())$errors[]='Chưa có chỉ tiêu trường THPT hợp lệ.';
    return $errors;
}
/** Pure simulation: one shared quota per school, NV1 then NV2 then NV3, no ID tie-break. */
function selection_plan(array $input,array $resolutions=[]): array {
    $errors=[];$candidates=[];$asp=[];$papers=[];$reviews=[];$quotas=[];$schools=[];$expected=['Ngữ văn','Toán','Ngoại ngữ'];$subjectNames=[];
    foreach($input['subjects'] as $s)$subjectNames[(int)$s['id']]=$s['name'];$actual=array_values($subjectNames);sort($actual);sort($expected);if($actual!==$expected)$errors[]='Cần đúng ba môn của kỳ tuyển sinh thường.';
    foreach($input['quotas'] as $q){$id=(int)$q['school_id'];$schools[$id]=$q;if($q['status']==='set' && (int)$q['active']===1 && $q['level']==='THPT' && (int)$q['quota']>=0)$quotas[$id]=(int)$q['quota'];}
    foreach($input['aspirations'] as $a)$asp[(int)$a['candidate_id']][]=$a;
    foreach($input['papers'] as $p)$papers[(int)$p['candidate_id']][(int)$p['exam_subject_id']]=$p;
    foreach($input['bonus_reviews'] as $r)$reviews[(int)$r['candidate_id']]=$r;
    foreach($input['candidates'] as $c){$id=(int)$c['id'];$reasons=[];$sum=0;$bonus=0;$subject_scores=[];$list=$asp[$id] ?? [];
        if($c['status']!=='active' || !(int)$c['user_active'] || !(int)$c['eligible'])$reasons[]='Hồ sơ hoặc tài khoản không đủ điều kiện.';
        $profileEligible=!$reasons;
        if($profileEligible && empty($c['exam_number']))$reasons[]='Có học sinh đăng ký chưa có SBD của kỳ.';
        if(count($list)!==3 || array_map('intval',array_column($list,'priority_order'))!==range(1,count($list)) || count(array_unique(array_column($list,'school_id')))!==count($list))$reasons[]='Có thứ tự nguyện vọng không hợp lệ.';
        foreach($list as $a)if(!array_key_exists((int)$a['school_id'],$quotas))$errors[]='Nguyện vọng gắn trường không hoạt động hoặc thiếu chỉ tiêu.';
        if($profileEligible)foreach($subjectNames as $subject=>$name){$p=$papers[$id][$subject] ?? null;
            if(!$p){$reasons[]='Có hồ sơ thiếu bài thi; cần hoàn tất điểm danh.';continue;}
            if($p['attendance_status']==='absent'){$reasons[]='Vắng môn '.$name.'.';continue;}
            $raw=$p['appeal_status']==='completed'?$p['appeal_score']:$p['original_score'];
            if(!in_array($p['attendance_status'],['present','present_penalty'],true) || $raw===null || $p['paper_status']!=='matched'){$reasons[]='Có bài thi chưa đủ điểm hoặc chưa ghép phách.';continue;}
            if(!is_numeric($raw)){$reasons[]='Điểm môn không phải số.';continue;}$raw=(float)$raw;if(!is_finite($raw) || $raw<0 || $raw>10 || abs($raw*4-round($raw*4))>0.00001){$reasons[]='Có điểm môn không hợp lệ.';continue;}
            $penalty=(int)($p['penalty_percent'] ?? 0);
            if($p['attendance_status']==='present_penalty' && !in_array($penalty,[25,50,100],true)){$reasons[]='Có mức xử phạt không hợp lệ.';continue;}
            if($p['attendance_status']==='present' && $penalty!==0){$reasons[]='Mức xử phạt không khớp điểm danh.';continue;}
            $effective=round($raw*(100-$penalty)/100,2);$sum+=$effective;$subject_scores[$name]=$effective;if($effective<=0)$reasons[]='Môn '.$name.' có điểm 0 sau xử lý điểm.';
        }
        if(isset($reviews[$id])){$r=$reviews[$id];$policy=$input['policy'];if(!isset($policy['priority_groups'][$r['priority_group']],$policy['encouragement_awards'][$r['award_group']]) || trim($r['evidence'])==='')$reasons[]='Hồ sơ điểm cộng chưa hợp lệ.';
            else $bonus=min((float)$policy['bonus_cap'],(float)$policy['priority_groups'][$r['priority_group']]+(float)$policy['encouragement_awards'][$r['award_group']]);
        }elseif($profileEligible && (float)($c['legacy_bonus'] ?? 0)!==0.0)$reasons[]='Có điểm cộng cũ chưa được đối chiếu minh chứng.';
        $candidates[$id]=$c+['reasons'=>array_values(array_unique($reasons)),'subject_scores'=>$subject_scores,'score_total'=>round($sum,2),'priority_points'=>$bonus,'admission_score'=>round($sum+$bonus,2),'eligible_selection'=>!$reasons];
    }
    $assigned=[];$ranks=[];$used=array_fill_keys(array_keys($quotas),0);$cutoffs=[];$hash=selection_input_hash($input);$tie=null;
    if(!$errors)for($priority=1;$priority<=3;$priority++){
        $groups=[];foreach($input['aspirations'] as $a){$id=(int)$a['candidate_id'];$school=(int)$a['school_id'];if((int)$a['priority_order']===$priority && !isset($assigned[$id]) && !empty($candidates[$id]['eligible_selection']))$groups[$school][]=$a;}
        ksort($groups);foreach($groups as $school=>$items){$left=$quotas[$school]-$used[$school];if($left<=0)continue;
            usort($items,static fn($a,$b)=>$candidates[(int)$b['candidate_id']]['admission_score']<=>$candidates[(int)$a['candidate_id']]['admission_score'] ?: $candidates[(int)$b['candidate_id']]['score_total']<=>$candidates[(int)$a['candidate_id']]['score_total']);
            $selected=array_slice($items,0,$left);
            if(count($items)>$left){$boundary=$candidates[(int)$items[$left-1]['candidate_id']]['admission_score'];$next=$candidates[(int)$items[$left]['candidate_id']]['admission_score'];
                if(abs($boundary-$next)<0.00001 && abs($candidates[(int)$items[$left-1]['candidate_id']]['score_total']-$candidates[(int)$items[$left]['candidate_id']]['score_total'])<0.00001){$base=$candidates[(int)$items[$left-1]['candidate_id']]['score_total'];$higher=[];$equal=[];foreach($items as $a){$score=$candidates[(int)$a['candidate_id']]['admission_score'];if($score>$boundary || (abs($score-$boundary)<0.00001 && $candidates[(int)$a['candidate_id']]['score_total']>$base))$higher[]=$a;elseif(abs($score-$boundary)<0.00001 && abs($candidates[(int)$a['candidate_id']]['score_total']-$base)<0.00001)$equal[]=$a;}
                    $ids=array_map('intval',array_column($equal,'candidate_id'));sort($ids);$seats=$left-count($higher);$key=hash('sha256',selection_json([$hash,$school,$priority,$boundary,$seats,$ids]));$chosen=array_map('intval',$resolutions[$key] ?? []);
                    if(count($chosen)!==$seats || count(array_unique($chosen))!==$seats || array_diff($chosen,$ids)){$tie=['key'=>$key,'school_id'=>$school,'school_name'=>$schools[$school]['name'],'priority'=>$priority,'score'=>$boundary,'seats'=>$seats,'candidates'=>array_map(static fn($id)=>$candidates[$id],$ids)];break 2;}
                    $selected=array_merge($higher,array_values(array_filter($equal,static fn($a)=>in_array((int)$a['candidate_id'],$chosen,true))));
                }
            }
            foreach($items as $i=>$a)$ranks[(int)$a['id']]=$i+1;
            foreach($selected as $a){$assigned[(int)$a['candidate_id']]=(int)$a['id'];$used[$school]++;}
            if($selected){$minimum=min(array_map(static fn($a)=>$candidates[(int)$a['candidate_id']]['admission_score'],$selected));$cutoffs[$school]['NV'.$priority]=$minimum;$cutoffs[$school]['minimum']=min($cutoffs[$school]['minimum'] ?? $minimum,$minimum);}
        }
    }
    return compact('candidates','assigned','ranks','used','quotas','cutoffs','tie','hash')+['errors'=>array_values(array_unique($errors))];
}
function selection_resolutions(PDO $pdo,int $period): array {$q=$pdo->prepare('SELECT tie_key,chosen_candidates FROM admission_tie_resolutions WHERE admission_period_id=?');$q->execute([$period]);$out=[];foreach($q as $r)$out[$r['tie_key']]=json_decode($r['chosen_candidates'],true,512,JSON_THROW_ON_ERROR);return $out;}
function selection_snapshot(PDO $pdo,int $run): ?array {$q=$pdo->prepare('SELECT * FROM admission_run_snapshots WHERE admission_run_id=?');$q->execute([$run]);$r=$q->fetch();if(!$r)return null;$r['inputs']=json_decode($r['inputs'],true,512,JSON_THROW_ON_ERROR);$r['report']=json_decode($r['report'],true,512,JSON_THROW_ON_ERROR);return $r;}
function selection_assert_not_saved(PDO $pdo,int $period): void {
    $q=$pdo->prepare('SELECT id FROM admission_runs WHERE admission_period_id=? AND status IN ("calculated","finalized") LIMIT 1');$q->execute([$period]);
    if($q->fetchColumn())throw new RuntimeException('Đã xét tuyển. Không được ghi đè kết quả đã lưu.');
}
function selection_preview(PDO $pdo,int $period,int $actor): array {
    selection_actor($pdo,$actor);selection_assert_not_saved($pdo,$period);
    $errors=selection_prerequisites($pdo,$period);if($errors)throw new RuntimeException(implode(' ',$errors));
    $inputs=selection_inputs($pdo,$period);$plan=selection_plan($inputs);
    if($plan['errors'])throw new RuntimeException(implode(' ',$plan['errors']));
    return ['inputs'=>$inputs,'report'=>$plan];
}
function selection_calculate(PDO $pdo,int $period,int $actor,?string $previewHash=null): int {
    if(!$previewHash)throw new RuntimeException('Cần chạy xem trước và xác nhận lưu.');
    selection_actor($pdo,$actor);$pdo->beginTransaction();try{
        selection_lock($pdo,$period);selection_assert_not_saved($pdo,$period);$errors=selection_prerequisites($pdo,$period);if($errors)throw new RuntimeException(implode(' ',$errors));
        $inputs=selection_inputs($pdo,$period);$plan=selection_plan($inputs);if(!hash_equals($previewHash,$plan['hash']))throw new RuntimeException('Dữ liệu đã thay đổi. Hãy chạy xem trước lại.');if($plan['tie'])throw new RuntimeException('Vẫn đồng điểm gốc tại ranh giới chỉ tiêu; chưa thể lưu.');if($plan['errors'])throw new RuntimeException(implode(' ',$plan['errors']));
        $status=$plan['tie']?'blocked':'calculated';$reason=$plan['tie']?'Đồng điểm tại ranh giới chỉ tiêu; cần rà soát biên bản mô phỏng.':null;
        $pdo->prepare('INSERT INTO admission_runs(admission_period_id,status,started_by_user_id,calculated_at,blocked_reason) VALUES(?,?,?,NOW(),?)')->execute([$period,$status,$actor,$reason]);$run=(int)$pdo->lastInsertId();
        if(!$plan['tie']){
            $insert=$pdo->prepare('INSERT INTO admission_results(admission_run_id,admission_period_id,candidate_id,aspiration_id,school_id,priority_order,score_total,priority_points,admission_score,rank_no,result_status) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
            foreach($inputs['aspirations'] as $a){$c=$plan['candidates'][(int)$a['candidate_id']];$selected=($plan['assigned'][(int)$a['candidate_id']] ?? 0)===(int)$a['id'];$insert->execute([$run,$period,$a['candidate_id'],$a['id'],$a['school_id'],$a['priority_order'],$c['score_total'],$c['priority_points'],$c['admission_score'],$plan['ranks'][(int)$a['id']] ?? null,$selected?'admitted':($c['eligible_selection']?'not_admitted':'unprocessed')]);}
        }
        $pdo->prepare('INSERT INTO admission_run_snapshots(admission_run_id,input_hash,inputs,report) VALUES(?,?,?,?)')->execute([$run,$plan['hash'],selection_json($inputs),selection_json($plan)]);
        period_event($pdo,$period,$actor,'selection_calculated',['run'=>$run,'status'=>$status,'admitted'=>count($plan['assigned'])]);$pdo->commit();return $run;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function selection_tie_resolve(PDO $pdo,int $period,int $run,string $key,array $chosen,string $evidence,int $actor): void {
    selection_actor($pdo,$actor);$evidence=trim($evidence);if(mb_strlen($evidence)<10 || mb_strlen($evidence)>4000)throw new InvalidArgumentException('Ghi biên bản rà soát mô phỏng từ 10–4000 ký tự.');
    $pdo->beginTransaction();try{
        selection_lock($pdo,$period);$q=$pdo->prepare('SELECT id,status FROM admission_runs WHERE admission_period_id=? ORDER BY id DESC LIMIT 1 FOR UPDATE');$q->execute([$period]);$latest=$q->fetch();$snap=selection_snapshot($pdo,$run);
        if(!$latest || (int)$latest['id']!==$run || $latest['status']!=='blocked' || !$snap || !hash_equals($snap['input_hash'],selection_input_hash(selection_inputs($pdo,$period))))throw new RuntimeException('Lượt xét hoặc dữ liệu đã thay đổi; chạy lại xét tuyển.');
        $tie=$snap['report']['tie'];$ids=array_map('intval',array_column($tie['candidates'],'id'));$chosen=array_map('intval',$chosen);
        if(!hash_equals($tie['key'],$key) || count($chosen)!==(int)$tie['seats'] || count(array_unique($chosen))!==count($chosen) || array_diff($chosen,$ids))throw new InvalidArgumentException('Chỉ chọn đúng số chỗ trong nhóm đồng điểm hiện tại.');
        $pdo->prepare('INSERT INTO admission_tie_resolutions(admission_period_id,tie_key,chosen_candidates,evidence,resolved_by) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE chosen_candidates=VALUES(chosen_candidates),evidence=VALUES(evidence),resolved_by=VALUES(resolved_by),resolved_at=NOW()')->execute([$period,$key,selection_json($chosen),$evidence,$actor]);
        period_event($pdo,$period,$actor,'selection_tie',['run'=>$run,'key'=>$key,'chosen'=>$chosen,'evidence'=>$evidence]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function selection_assert_current(PDO $pdo,int $period,int $run): array {
    $q=$pdo->prepare('SELECT id,status FROM admission_runs WHERE admission_period_id=? ORDER BY id DESC LIMIT 1 FOR UPDATE');$q->execute([$period]);$latest=$q->fetch();$snap=selection_snapshot($pdo,$run);
    if(!$latest || (int)$latest['id']!==$run || $latest['status']!=='calculated' || !$snap || (!hash_equals($snap['input_hash'],selection_input_hash(selection_inputs($pdo,$period))) || !hash_equals($snap['input_hash'],selection_input_hash($snap['inputs']))))throw new RuntimeException('Kết quả không còn khớp dữ liệu hiện tại hoặc không phải lượt mới nhất. Cần chạy lại xét tuyển.');
    $plan=selection_plan($snap['inputs'],selection_resolutions($pdo,$period));if($plan['errors'] || $plan['tie'] || selection_json($plan)!==selection_json($snap['report']))throw new RuntimeException('Kết quả còn lỗi hoặc chưa xử lý đồng điểm.');
    $q=$pdo->prepare('SELECT candidate_id,aspiration_id,school_id,priority_order,score_total,priority_points,admission_score,rank_no,result_status FROM admission_results WHERE admission_run_id=? ORDER BY aspiration_id FOR UPDATE');$q->execute([$run]);$rows=$q->fetchAll();
    if(count($rows)!==count($snap['inputs']['aspirations']))throw new RuntimeException('Danh sách kết quả thiếu hoặc thừa nguyện vọng.');
    $asp=array_column($snap['inputs']['aspirations'],null,'id');foreach($rows as $r){$a=$asp[$r['aspiration_id']] ?? null;$c=$plan['candidates'][(int)$r['candidate_id']] ?? null;$status=(($plan['assigned'][(int)$r['candidate_id']] ?? 0)===(int)$r['aspiration_id'])?'admitted':(!empty($c['eligible_selection'])?'not_admitted':'unprocessed');
        if(!$a || !$c || (int)$a['candidate_id']!==(int)$r['candidate_id'] || (int)$a['school_id']!==(int)$r['school_id'] || (int)$a['priority_order']!==(int)$r['priority_order'] || abs((float)$r['score_total']-$c['score_total'])>0.00001 || abs((float)$r['priority_points']-$c['priority_points'])>0.00001 || abs((float)$r['admission_score']-$c['admission_score'])>0.00001 || (int)($r['rank_no'] ?? 0)!==(int)($plan['ranks'][(int)$r['aspiration_id']] ?? 0) || $r['result_status']!==$status)throw new RuntimeException('Kết quả đã bị thay đổi; cần chạy lại xét tuyển.');
    }
    return $snap;
}
function selection_review(PDO $pdo,int $period,int $run,int $actor): void {
    selection_actor($pdo,$actor);$pdo->beginTransaction();try{selection_lock($pdo,$period);selection_assert_current($pdo,$period,$run);$pdo->prepare('UPDATE admission_run_snapshots SET reviewed_by=?,reviewed_at=NOW() WHERE admission_run_id=?')->execute([$actor,$run]);period_event($pdo,$period,$actor,'selection_review',['run'=>$run]);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
/** Called by publication service inside its transaction. */
function selection_publish_check(PDO $pdo,int $period): int {
    selection_lock($pdo,$period);$errors=selection_prerequisites($pdo,$period);if($errors)throw new RuntimeException(implode(' ',$errors));
    $q=$pdo->prepare('SELECT id FROM admission_runs WHERE admission_period_id=? ORDER BY id DESC LIMIT 1');$q->execute([$period]);$run=(int)$q->fetchColumn();$snap=selection_assert_current($pdo,$period,$run);
    if(empty($snap['reviewed_at']) || empty($snap['reviewed_by']))throw new RuntimeException('Cần duyệt lượt xét tuyển hiện tại trước khi công bố.');return $run;
}
