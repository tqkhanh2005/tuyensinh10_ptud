<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/publication_service.php';
require_once __DIR__ . '/admission_period_configuration.php';
require_once __DIR__ . '/admission_selection_service.php';
require_once __DIR__ . '/enrollment_service.php';

function admission_period_for_run(PDO $pdo, int $periodId): array
{
    $q = $pdo->prepare('SELECT * FROM admission_periods WHERE id=:id FOR UPDATE');
    $q->execute(['id' => $periodId]);
    $row = $q->fetch();
    if (!$row)
        throw new RuntimeException('Không tìm thấy kỳ tuyển sinh.');
    return $row;
}

function admission_check_prerequisites(PDO $pdo, int $periodId): array
{
    if(period_configuration($pdo,$periodId))return selection_prerequisites($pdo,$periodId);
    $errors = [];
    $period = admission_period_find_local($pdo, $periodId);
    if (!$period)
        return ['Kỳ tuyển sinh không tồn tại.'];
    $configuration=period_configuration($pdo,$periodId);
    if($configuration && $configuration['settings']['mode']==='official') $errors[]='Bộ máy xét tuyển hiện tại chỉ dùng mô phỏng; cần đối chiếu và nghiệm thu đầy đủ quy tắc xét tuyển chính thức trước khi chạy kỳ này.';

    $quota = $pdo->prepare('SELECT COUNT(*) FROM admission_quotas WHERE admission_period_id=:p AND status="set" AND quota IS NOT NULL');
    $quota->execute(['p' => $periodId]);
    if ((int) $quota->fetchColumn() === 0)
        $errors[] = 'Chưa thiết lập chỉ tiêu tuyển sinh.';

    $asp = $pdo->prepare('SELECT COUNT(*) FROM aspirations a JOIN candidates c ON c.id=a.candidate_id WHERE a.admission_period_id=:p AND c.eligible=1 AND c.status="active"');
    $asp->execute(['p' => $periodId]);
    if ((int) $asp->fetchColumn() === 0)
        $errors[] = 'Chưa có nguyện vọng của thí sinh đủ điều kiện.';

    $window = $pdo->prepare('SELECT * FROM appeal_windows WHERE admission_period_id=:p LIMIT 1');
    $window->execute(['p' => $periodId]);
    $w = $window->fetch();
    if (!$w)
        $errors[] = 'Chưa cấu hình thời hạn phúc khảo.';
    else {
        $now = new DateTimeImmutable('now');
        if (new DateTimeImmutable($w['end_at']) >= $now)
            $errors[] = 'Chưa kết thúc thời hạn phúc khảo.';
        $pending = $pdo->prepare('SELECT COUNT(*) FROM appeal_requests WHERE admission_period_id=:p AND status IN ("pending","grading")');
        $pending->execute(['p' => $periodId]);
        if ((int) $pending->fetchColumn() > 0)
            $errors[] = 'Vẫn còn yêu cầu phúc khảo chưa có kết quả.';
    }

    $subjects = $pdo->prepare('SELECT COUNT(*) FROM exam_subjects WHERE admission_period_id=:p');
    $subjects->execute(['p' => $periodId]);
    $subjectCount = (int) $subjects->fetchColumn();
    if ($subjectCount === 0)
        $errors[] = 'Chưa thiết lập môn thi.';

    if ($subjectCount > 0) {
        $candidates = $pdo->prepare('SELECT DISTINCT c.id,c.full_name FROM candidates c JOIN aspirations a ON a.candidate_id=c.id AND a.admission_period_id=:p WHERE c.eligible=1 AND c.status="active" ORDER BY c.id');
        $candidates->execute(['p' => $periodId]);
        $check = $pdo->prepare('SELECT COUNT(DISTINCT p.exam_subject_id)
            FROM exam_papers p JOIN scores s ON s.exam_paper_id=p.id
            WHERE p.admission_period_id=:p AND p.candidate_id=:c AND p.attendance_status IN ("present","present_penalty")');
        while ($c = $candidates->fetch()) {
            $check->execute(['p' => $periodId, 'c' => (int) $c['id']]);
            if ((int) $check->fetchColumn() < $subjectCount) {
                $errors[] = 'Chưa đủ điểm chính thức cho toàn bộ thí sinh đủ điều kiện (ví dụ: ' . $c['full_name'] . ').';
                break;
            }
        }
    }
    return $errors;
}

function admission_period_find_local(PDO $pdo, int $id): ?array
{
    $q = $pdo->prepare('SELECT * FROM admission_periods WHERE id=:id');
    $q->execute(['id' => $id]);
    $r = $q->fetch();
    return $r ?: null;
}

function admission_candidate_rows(PDO $pdo, int $periodId): array
{
    $sql = 'SELECT c.id candidate_id, c.candidate_code, c.full_name,
                   COALESCE(pp.priority_points,0) priority_points,
                   COALESCE(SUM(COALESCE(apr.new_score,sc.total_score)),0) score_total,
                   COALESCE(SUM(COALESCE(apr.new_score,sc.total_score)),0)+COALESCE(pp.priority_points,0) admission_score,
                   a.id aspiration_id, a.school_id, a.priority_order,
                   s.school_code, s.name school_name
            FROM candidates c
            JOIN aspirations a ON a.candidate_id=c.id AND a.admission_period_id=:p1
            JOIN schools s ON s.id=a.school_id AND s.level="THPT" AND s.active=1
            LEFT JOIN candidate_priority_points pp ON pp.candidate_id=c.id AND pp.admission_period_id=:p2
            JOIN exam_papers ep ON ep.candidate_id=c.id AND ep.admission_period_id=:p3 AND ep.attendance_status IN ("present","present_penalty")
            JOIN scores sc ON sc.exam_paper_id=ep.id
            LEFT JOIN appeal_requests ar ON ar.exam_paper_id=ep.id AND ar.status="completed"
            LEFT JOIN appeal_results apr ON apr.appeal_request_id=ar.id
            WHERE c.eligible=1 AND c.status="active"
            GROUP BY c.id,c.candidate_code,c.full_name,pp.priority_points,a.id,a.school_id,a.priority_order,s.school_code,s.name
            ORDER BY a.priority_order ASC, admission_score DESC, c.id ASC';
    $q = $pdo->prepare($sql);
    $q->execute(['p1' => $periodId, 'p2' => $periodId, 'p3' => $periodId]);
    return $q->fetchAll();
}

function admission_tie_at_quota(array $rows, array $quotas): ?string
{
    $groups = [];
    foreach ($rows as $r) {
        $school = (int) $r['school_id'];
        $priority = (int) $r['priority_order'];
        $groups[$priority][$school][] = $r;
    }
    foreach ($groups as $priority => $schools)
        foreach ($schools as $school => $items) {
            usort($items, fn($a, $b) => ((float) $b['admission_score'] <=> (float) $a['admission_score']) ?: ((int) $a['candidate_id'] <=> (int) $b['candidate_id']));
            $quota = (int) ($quotas[$school] ?? 0);
            if ($quota <= 0 || count($items) <= $quota)
                continue;
            $boundary = (float) $items[$quota - 1]['admission_score'];
            $next = (float) $items[$quota]['admission_score'];
            if (abs($boundary - $next) < 0.00001)
                return 'Có đồng điểm tại ranh giới chỉ tiêu của trường ' . $school . ' ở nguyện vọng ' . $priority . '. Quy tắc phá đồng điểm chưa được đặc tả.';
        }
    return null;
}

function admission_calculate(PDO $pdo, int $periodId, int $userId): int
{
    throw new RuntimeException('Cần chạy xem trước và xác nhận lưu tại trang Xét tuyển.');
}

function admission_results(PDO $pdo, int $runId): array
{
    $q = $pdo->prepare('SELECT ar.*,c.candidate_code,c.full_name,s.school_code,s.name school_name FROM admission_results ar JOIN candidates c ON c.id=ar.candidate_id LEFT JOIN schools s ON s.id=ar.school_id WHERE ar.admission_run_id=:r ORDER BY ar.priority_order,ar.admission_score DESC,c.full_name');
    $q->execute(['r' => $runId]);
    return $q->fetchAll();
}

function latest_admission_run(PDO $pdo, int $periodId): ?array
{
    $q = $pdo->prepare('SELECT * FROM admission_runs WHERE admission_period_id=:p ORDER BY id DESC LIMIT 1');
    $q->execute(['p' => $periodId]);
    $r = $q->fetch();
    return $r ?: null;
}

function candidate_admission_result(PDO $pdo, int $candidateId, int $periodId): array
{
    if(period_configuration($pdo,$periodId)){if(!admission_results_published($pdo,$periodId))return [];return public_admission_results($pdo,$periodId,$candidateId);}
    $q = $pdo->prepare('SELECT ar.*,s.school_code,s.name school_name FROM admission_results ar LEFT JOIN schools s ON s.id=ar.school_id JOIN admission_runs r ON r.id=ar.admission_run_id WHERE ar.candidate_id=:c AND ar.admission_period_id=:p AND r.status IN ("calculated","finalized") ORDER BY ar.priority_order');
    $q->execute(['c' => $candidateId, 'p' => $periodId]);
    return $q->fetchAll();
}

function enrollment_confirm(PDO $pdo, int $candidateId, int $periodId, ?int $actor=null): void
{
    if(period_configuration($pdo,$periodId)){enrollment_confirm_managed($pdo,$candidateId,$periodId,(int)$actor);return;}
    $pdo->beginTransaction();
    try {
        $managed=(bool)period_configuration($pdo,$periodId);
        if($managed){grading_period_lock($pdo,$periodId);if(!admission_results_published($pdo,$periodId))throw new RuntimeException('Kết quả tuyển sinh chưa được công bố.');}

        $q = $pdo->prepare('SELECT ar.*,r.status run_status FROM admission_results ar JOIN admission_runs r ON r.id=ar.admission_run_id WHERE ar.candidate_id=:c AND ar.admission_period_id=:p AND ar.result_status="admitted" ORDER BY r.id DESC LIMIT 1 FOR UPDATE');
        $q->execute(['c' => $candidateId, 'p' => $periodId]);
        $r = $q->fetch();
        if (!$r)
            throw new RuntimeException('Học sinh chưa có kết quả trúng tuyển.');
        $period = $pdo->prepare('SELECT * FROM admission_periods WHERE id=:p FOR UPDATE');
        $period->execute(['p' => $periodId]);
        $period = $period->fetch();
        if (!$period)
            throw new RuntimeException('Không tìm thấy kỳ tuyển sinh.');
        if(period_configuration($pdo,$periodId) && $period['status']!=='published') throw new RuntimeException('Kỳ tuyển sinh đã đóng hoặc chưa công bố.');
        if (empty($period['enrollment_start']) || empty($period['enrollment_end']))
            throw new RuntimeException('[MISSING SPECIFICATION] Chưa cấu hình thời gian xác nhận nhập học.');
        $now = new DateTimeImmutable('now');
        if ($now < new DateTimeImmutable($period['enrollment_start']) || $now > new DateTimeImmutable($period['enrollment_end']))
            throw new RuntimeException('Thời gian xác nhận nhập học đã hết hoặc chưa bắt đầu.');
        $existing = $pdo->prepare('SELECT id FROM enrollment_confirmations WHERE candidate_id=:c LIMIT 1 FOR UPDATE');
        $existing->execute(['c' => $candidateId]);
        if ($existing->fetch())
            throw new RuntimeException('Hồ sơ đã được xác nhận nhập học.');
        if(enrollment_schema($pdo)){$ins=$pdo->prepare('INSERT INTO enrollment_confirmations(admission_result_id,candidate_id,school_id,admission_period_id) VALUES(?,?,?,?)');$ins->execute([(int)$r['id'],$candidateId,(int)$r['school_id'],$periodId]);}
        else {$ins=$pdo->prepare('INSERT INTO enrollment_confirmations(admission_result_id,candidate_id,school_id) VALUES(?,?,?)');$ins->execute([(int)$r['id'],$candidateId,(int)$r['school_id']]);}
        $pdo->prepare('UPDATE admission_results SET enrollment_status="confirmed" WHERE id=:id')->execute(['id' => (int) $r['id']]);
        if(!$managed)$pdo->prepare('UPDATE candidates SET admission_status="enrolled" WHERE id=:id')->execute(['id' => $candidateId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        throw $e;
    }
}
