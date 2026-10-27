<?php
// Included by calculate.php after authorization.
if($cfg && selection_schema($pdo)):
    $listing=$preview ?? ['inputs'=>selection_inputs($pdo,$periodId)];
    $listingPlan=$listing['report'] ?? selection_plan($listing['inputs']);
    $schoolNames=array_column($listing['inputs']['quotas'],'name','school_id');
    $wishes=[];foreach($listing['inputs']['aspirations'] as $a)$wishes[$a['candidate_id']][$a['priority_order']]=$schoolNames[$a['school_id']] ?? ('Trường #'.$a['school_id']);
?>
<h2 class="h5">Danh sách thí sinh đăng ký</h2>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>SBD</th><th>Họ tên</th><th>Điểm các môn</th><th>Trạng thái điểm</th><th>NV1 / NV2 / NV3</th><th>Xét tuyển</th></tr></thead><tbody>
<?php foreach($listingPlan['candidates'] as $c): ?>
<tr><td><?=e($c['exam_number'] ?? '—')?></td><td><?=e($c['full_name'])?></td><td><?=e(implode(' / ',array_map(static fn($name)=>$name.': '.($c['subject_scores'][$name] ?? '—'),['Ngữ văn','Toán','Ngoại ngữ'])))?></td><td><?=e($c['reasons']?implode(' ',$c['reasons']):(!empty($listing['inputs']['grading'])?'Đã chốt':'Chưa chốt'))?></td><td><?=e(implode(' / ',array_map(static fn($nv)=>$wishes[$c['id']][$nv] ?? '—',[1,2,3])))?></td><td><?= $run && in_array($run['status'],['calculated','finalized'],true)?'Đã xét':'Chưa xét' ?></td></tr>
<?php endforeach;?></tbody></table></div>
<?php endif; if($preview):$plan=$preview['report'];$aspById=array_column($preview['inputs']['aspirations'],null,'id'); ?>
<dialog id="selection-preview" style="width:95vw;max-width:1200px;max-height:90vh;overflow:auto" aria-labelledby="preview-title">
<h2 id="preview-title">Xem trước xét tuyển — chưa lưu CSDL</h2>
<p>Điểm chuẩn là điểm xét tuyển thấp nhất trong số trúng tuyển, kể cả trường chưa đủ chỉ tiêu. Trường không nhận thí sinh hiển thị “—”.</p>
<?php if($plan['tie']):?><p role="alert" class="alert alert-warning">Vẫn đồng điểm xét tuyển và điểm gốc tại <?=e($plan['tie']['school_name'])?>, NV<?=$plan['tie']['priority']?>. Chưa thể lưu; số liệu dưới đây mới là kết quả một phần.</p><?php endif;?>
<div class="table-responsive"><table class="table"><thead><tr><th>Mã trường</th><th>Tên trường</th><th>Chỉ tiêu</th><th>Trúng tuyển</th><th>Điểm chuẩn tạm tính</th></tr></thead><tbody>
<?php foreach($preview['inputs']['quotas'] as $q):$id=(int)$q['school_id'];?><tr><td><?=e($q['school_code'])?></td><td><?=e($q['name'])?></td><td><?=(int)$q['quota']?></td><td><?=$plan['used'][$id] ?? 0?></td><td><?=e((string)($plan['cutoffs'][$id]['minimum'] ?? '—'))?></td></tr><?php endforeach;?></tbody></table></div>
<div class="table-responsive"><table class="table"><thead><tr><th>SBD</th><th>Họ tên</th><th>Điểm gốc</th><th>Tổng xét tuyển</th><th>Nguyện vọng trúng tuyển</th><th>Trạng thái / cảnh báo</th></tr></thead><tbody>
<?php foreach($plan['candidates'] as $id=>$c):$a=$aspById[$plan['assigned'][$id] ?? 0] ?? null;?>
<tr><td><?=e($c['exam_number'] ?? '—')?></td><td><?=e($c['full_name'])?></td><td><?=$c['score_total']?></td><td><?=$c['admission_score']?></td><td><?= $a?'NV'.(int)$a['priority_order'].' · '.e($schoolNames[$a['school_id']] ?? ''):'—' ?></td><td><?=e($c['reasons']?implode(' ',$c['reasons']):($a?'Trúng tuyển':($plan['tie']?'Chưa hoàn tất':'Không trúng tuyển')))?></td></tr>
<?php endforeach;?></tbody></table></div>
<form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="preview_token" value="<?=e($preview['token'])?>"><button class="btn btn-success" name="action" value="confirm" <?=$plan['tie']?'disabled':''?>>Xác nhận lưu</button> <button class="btn btn-secondary" name="action" value="cancel">Hủy, không lưu</button></form>
</dialog>
<script>const selectionDialog=document.getElementById('selection-preview');selectionDialog.showModal();selectionDialog.addEventListener('cancel',event=>{event.preventDefault();selectionDialog.querySelector('button[value="cancel"]').click();});</script>
<?php endif;?>
