<?php
declare(strict_types=1);
function grading_filter_context(PDO $pdo,int $period,int $subject=0): array {
    $periods=$pdo->query('SELECT id,name FROM admission_periods ORDER BY id DESC')->fetchAll();
    if(!$period)$period=(int)($periods[0]['id'] ?? 0);
    $q=$pdo->prepare('SELECT id,name FROM exam_subjects WHERE admission_period_id=? ORDER BY exam_date,start_time,id');$q->execute([$period]);$subjects=$q->fetchAll();
    if(!$subject || !in_array($subject,array_map('intval',array_column($subjects,'id')),true))$subject=(int)($subjects[0]['id'] ?? 0);
    return compact('periods','subjects','period','subject');
}
function grading_filters(array $context): void { ?>
<form method="get" class="row g-2 mb-3"><div class="col-md-6"><label class="form-label w-100">Kỳ tuyển sinh<select class="form-select" name="period_id" onchange="this.form.submit()"><?php foreach($context['periods'] as $p):?><option value="<?=$p['id']?>" <?=$p['id']==$context['period']?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select></label></div><div class="col-md-4"><label class="form-label w-100">Môn thi<select class="form-select" name="subject_id" onchange="this.form.submit()"><?php foreach($context['subjects'] as $s):?><option value="<?=$s['id']?>" <?=$s['id']==$context['subject']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></label></div><div class="col-md-2 align-self-end"><button class="btn btn-outline-primary mb-2">Xem</button></div></form>
<?php }
