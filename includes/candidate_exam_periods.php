<?php
declare(strict_types=1);
function candidate_exam_periods(PDO $pdo,int $candidate): array {
    $q=$pdo->prepare('SELECT DISTINCT p.id,p.name,p.academic_year FROM admission_periods p JOIN exam_papers ep ON ep.admission_period_id=p.id WHERE ep.candidate_id=? ORDER BY p.id DESC');$q->execute([$candidate]);return $q->fetchAll();
}
function candidate_exam_period_options(array $periods,int $selected): void {
    echo '<select name="period_id" class="form-select" required>';
    if(!$periods)echo '<option value="">Chưa có kỳ thi</option>';
    foreach($periods as $p)echo '<option value="'.(int)$p['id'].'" '.((int)$p['id']===$selected?'selected':'').'>'.e($p['name']).'</option>';
    echo '</select>';
}
