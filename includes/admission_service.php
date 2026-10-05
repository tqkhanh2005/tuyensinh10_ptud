<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/publication_service.php';

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
    $errors = [];
    $period = admission_period_find_local($pdo, $periodId);
    if (!$period)
        return ['Kỳ tuyển sinh không tồn tại.'];

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
                   COALESCE(SUM(sc.total_score),0) score_total,
                   COALESCE(SUM(sc.total_score),0)+COALESCE(pp.priority_points,0) admission_score,
                   a.id aspiration_id, a.school_id, a.priority_order,
                   s.school_code, s.name school_name
            FROM candidates c
            JOIN aspirations a ON a.candidate_id=c.id AND a.admission_period_id=:p1
            JOIN schools s ON s.id=a.school_id AND s.level="THPT" AND s.active=1
            LEFT JOIN candidate_priority_points pp ON pp.candidate_id=c.id AND pp.admission_period_id=:p2
            JOIN exam_papers ep ON ep.candidate_id=c.id AND ep.admission_period_id=:p3 AND ep.attendance_status IN ("present","present_penalty")
            JOIN scores sc ON sc.exam_paper_id=ep.id
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
    $pdo->beginTransaction();
    try {
        if (admission_results_published($pdo, $periodId)) {
            throw new RuntimeException('Kết quả tuyển sinh đã được công bố, không được phép chạy lại xét tuyển.');
        }

        $errors = admission_check_prerequisites($pdo, $periodId);
        if ($errors)
            throw new RuntimeException(implode(' ', array_unique($errors)));

        admission_period_for_run($pdo, $periodId);
        $q = $pdo->prepare('SELECT school_id,quota FROM admission_quotas WHERE admission_period_id=:p AND status="set" FOR UPDATE');
        $q->execute(['p' => $periodId]);
        $quotaRows = $q->fetchAll();
        $quotas = [];
        foreach ($quotaRows as $r)
            $quotas[(int) $r['school_id']] = (int) $r['quota'];

        $rows = admission_candidate_rows($pdo, $periodId);
        $tie = admission_tie_at_quota($rows, $quotas);
        if ($tie)
            throw new RuntimeException($tie);

        $run = $pdo->prepare('INSERT INTO admission_runs(admission_period_id,status,started_by_user_id,calculated_at) VALUES(:p,"calculated",:u,CURRENT_TIMESTAMP)');
        $run->execute(['p' => $periodId, 'u' => $userId]);
        $runId = (int) $pdo->lastInsertId();

        // Each candidate/aspiration gets a result row. A candidate can be selected only once.
        $byPriority = [];
        foreach ($rows as $r)
            $byPriority[(int) $r['priority_order']][] = $r;
        $allCandidates = [];
        foreach ($rows as $r)
            $allCandidates[(int) $r['candidate_id']][] = $r;
        $assigned = [];
        $rankMap = [];
        $insert = $pdo->prepare('INSERT INTO admission_results
            (admission_run_id,admission_period_id,candidate_id,aspiration_id,school_id,priority_order,score_total,priority_points,admission_score,rank_no,result_status)
            VALUES(:run,:period,:candidate,:asp,:school,:priority,:score,:points,:total,:rank,:status)');

        foreach ($byPriority as $priority => $items) {
            $schoolGroups = [];
            foreach ($items as $r)
                $schoolGroups[(int) $r['school_id']][] = $r;
            foreach ($schoolGroups as $schoolId => $cands) {
                usort($cands, fn($a, $b) => ((float) $b['admission_score'] <=> (float) $a['admission_score']) ?: ((int) $a['candidate_id'] <=> (int) $b['candidate_id']));
                $rank = 0;
                foreach ($cands as $r) {
                    if (isset($assigned[(int) $r['candidate_id']]))
                        continue;
                    $rank++;
                    $rankMap[(int) $r['aspiration_id']] = $rank;
                    $quota = $quotas[$schoolId] ?? 0;
                    if ($rank <= $quota)
                        $assigned[(int) $r['candidate_id']] = [
                            'aspiration_id' => (int) $r['aspiration_id'],
                            'school_id' => $schoolId,
                            'priority' => $priority
                        ];
                }
            }
        }

        // Persist every aspiration. The selected aspiration is admitted; other aspirations are not admitted.
        foreach ($allCandidates as $candidateId => $aspirations) {
            foreach ($aspirations as $r) {
                $selected = isset($assigned[$candidateId]) && $assigned[$candidateId]['aspiration_id'] === (int) $r['aspiration_id'];
                $rankQ = $pdo->prepare('SELECT COUNT(*)+1 FROM admission_results x
                    WHERE 1=0'); // placeholder; rank is calculated below per school/priority.
                unset($rankQ);
                $insert->execute([
                    'run' => $runId,
                    'period' => $periodId,
                    'candidate' => $candidateId,
                    'asp' => (int) $r['aspiration_id'],
                    'school' => (int) $r['school_id'],
                    'priority' => (int) $r['priority_order'],
                    'score' => (float) $r['score_total'],
                    'points' => (float) $r['priority_points'],
                    'total' => (float) $r['admission_score'],
                    'rank' => $rankMap[(int) $r['aspiration_id']] ?? null,
                    'status' => $selected ? 'admitted' : 'not_admitted'
                ]);
            }
        }

        foreach ($assigned as $candidateId => $selection) {
            $u = $pdo->prepare('UPDATE candidates SET admission_status="admitted" WHERE id=:id');
            $u->execute(['id' => $candidateId]);
        }
        $pdo->prepare('UPDATE candidates c SET admission_status="not_processed" WHERE id IN (SELECT DISTINCT candidate_id FROM admission_results WHERE admission_run_id=:run1) AND id NOT IN (SELECT candidate_id FROM admission_results WHERE admission_run_id=:run2 AND result_status="admitted")')
            ->execute(['run1'=>$runId,'run2'=>$runId]);

        $pdo->commit();
        return $runId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        throw $e;
    }
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
    $q = $pdo->prepare('SELECT ar.*,s.school_code,s.name school_name FROM admission_results ar LEFT JOIN schools s ON s.id=ar.school_id JOIN admission_runs r ON r.id=ar.admission_run_id WHERE ar.candidate_id=:c AND ar.admission_period_id=:p AND r.status IN ("calculated","finalized") ORDER BY ar.priority_order');
    $q->execute(['c' => $candidateId, 'p' => $periodId]);
    return $q->fetchAll();
}

function enrollment_confirm(PDO $pdo, int $candidateId, int $periodId): void
{
    $pdo->beginTransaction();
    try {
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
        if (empty($period['enrollment_start']) || empty($period['enrollment_end']))
            throw new RuntimeException('[MISSING SPECIFICATION] Chưa cấu hình thời gian xác nhận nhập học.');
        $now = new DateTimeImmutable('now');
        if ($now < new DateTimeImmutable($period['enrollment_start']) || $now > new DateTimeImmutable($period['enrollment_end']))
            throw new RuntimeException('Thời gian xác nhận nhập học đã hết hoặc chưa bắt đầu.');
        $existing = $pdo->prepare('SELECT id FROM enrollment_confirmations WHERE candidate_id=:c LIMIT 1 FOR UPDATE');
        $existing->execute(['c' => $candidateId]);
        if ($existing->fetch())
            throw new RuntimeException('Hồ sơ đã được xác nhận nhập học.');
        $ins = $pdo->prepare('INSERT INTO enrollment_confirmations(admission_result_id,candidate_id,school_id) VALUES(:r,:c,:s)');
        $ins->execute(['r' => (int) $r['id'], 'c' => $candidateId, 's' => (int) $r['school_id']]);
        $pdo->prepare('UPDATE admission_results SET enrollment_status="confirmed" WHERE id=:id')->execute(['id' => (int) $r['id']]);
        $pdo->prepare('UPDATE candidates SET admission_status="enrolled" WHERE id=:id')->execute(['id' => $candidateId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        throw $e;
    }
}
