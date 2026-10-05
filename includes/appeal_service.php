<?php

declare(strict_types=1);
require_once __DIR__ . '/db.php';

const UC03_HEADERS = ['appeal_id', 'Số phách', 'SBD', 'Điểm cũ', 'Người chấm phúc khảo', 'Điểm mới', 'Lý do/biên bản thay đổi'];

function appeal_window(PDO $pdo, int $periodId): ?array
{
    $q = $pdo->prepare('SELECT * FROM appeal_windows WHERE admission_period_id = :p LIMIT 1');
    $q->execute(['p' => $periodId]);
    return $q->fetch() ?: null;
}

function appeal_window_open(PDO $pdo, int $periodId): array
{
    $window = appeal_window($pdo, $periodId);
    if (!$window) {
        throw new RuntimeException('Chưa cấu hình thời hạn đăng ký phúc khảo.');
    }

    $now = new DateTimeImmutable('now');
    $start = new DateTimeImmutable($window['start_at']);
    $end = new DateTimeImmutable($window['end_at']);
    if ($window['status'] !== 'open' || $now < $start || $now > $end) {
        throw new RuntimeException('Đã hết thời hạn đăng ký phúc khảo.');
    }
    return $window;
}

function candidate_for_current_user(PDO $pdo, int $userId): ?array
{
    $q = $pdo->prepare('SELECT * FROM candidates WHERE user_id = :u LIMIT 1');
    $q->execute(['u' => $userId]);
    return $q->fetch() ?: null;
}

function appeal_eligible_subjects(PDO $pdo, int $candidateId, int $periodId): array
{
    $q = $pdo->prepare(
        'SELECT s.id, s.subject_code, s.name, s.max_score,
                p.id AS exam_paper_id, sc.total_score,
                ar.id AS appeal_id
         FROM exam_papers p
         JOIN exam_subjects s ON s.id = p.exam_subject_id AND s.admission_period_id = p.admission_period_id
         JOIN scores sc ON sc.exam_paper_id = p.id
         LEFT JOIN appeal_requests ar ON ar.exam_paper_id = p.id
         WHERE p.admission_period_id = :period
           AND p.candidate_id = :candidate
           AND p.attendance_status IN ("present", "present_penalty")
         ORDER BY s.name'
    );
    $q->execute(['period' => $periodId, 'candidate' => $candidateId]);
    return $q->fetchAll();
}

function create_appeal(PDO $pdo, int $candidateId, int $periodId, int $subjectId, string $reason): int
{
    $reason = trim($reason);
    if ($reason === '') {
        throw new InvalidArgumentException('Lý do phúc khảo không được để trống.');
    }

    $window = appeal_window_open($pdo, $periodId);
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare(
            'SELECT p.id AS exam_paper_id, p.candidate_id, sc.total_score
             FROM exam_papers p
             JOIN exam_subjects s ON s.id = p.exam_subject_id AND s.admission_period_id = p.admission_period_id
             JOIN scores sc ON sc.exam_paper_id = p.id
             WHERE p.admission_period_id = :period
               AND p.candidate_id = :candidate
               AND p.exam_subject_id = :subject
               AND s.id = :subject2
               AND p.attendance_status IN ("present", "present_penalty")
             FOR UPDATE'
        );
        $q->execute([
            'period' => $periodId,
            'candidate' => $candidateId,
            'subject' => $subjectId,
            'subject2' => $subjectId,
        ]);
        $paper = $q->fetch();
        if (!$paper) {
            throw new RuntimeException('Bạn không đủ điều kiện đăng ký phúc khảo.');
        }

        $check = $pdo->prepare(
            'SELECT id FROM appeal_requests
             WHERE admission_period_id = :p AND candidate_id = :c AND exam_subject_id = :s
             FOR UPDATE'
        );
        $check->execute(['p' => $periodId, 'c' => $candidateId, 's' => $subjectId]);
        if ($check->fetch()) {
            throw new RuntimeException('Bạn đã đăng ký phúc khảo môn thi này.');
        }

        $fee = $window['fee_amount'] !== null ? (float)$window['fee_amount'] : null;
        $paymentStatus = ($fee !== null && $fee > 0) ? 'pending' : 'not_required';

        $ins = $pdo->prepare(
            'INSERT INTO appeal_requests
                (admission_period_id, candidate_id, exam_paper_id, exam_subject_id,
                 original_score, reason, fee_amount, payment_status, status)
             VALUES (:p, :c, :paper, :s, :score, :reason, :fee, :payment, "pending")'
        );
        $ins->execute([
            'p' => $periodId,
            'c' => $candidateId,
            'paper' => (int)$paper['exam_paper_id'],
            's' => $subjectId,
            'score' => (float)$paper['total_score'],
            'reason' => $reason,
            'fee' => $fee,
            'payment' => $paymentStatus,
        ]);

        $id = (int)$pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function candidate_appeals(PDO $pdo, int $candidateId, int $periodId): array
{
    $q = $pdo->prepare(
        'SELECT ar.id, ar.submitted_at, ar.status, ar.payment_status, ar.fee_amount,
                s.subject_code, s.name AS subject_name,
                r.new_score, r.old_score, r.change_reason, r.created_at AS result_at
         FROM appeal_requests ar
         JOIN exam_subjects s ON s.id = ar.exam_subject_id
         LEFT JOIN appeal_results r ON r.appeal_request_id = ar.id
         WHERE ar.candidate_id = :c AND ar.admission_period_id = :p
         ORDER BY ar.submitted_at DESC'
    );
    $q->execute(['c' => $candidateId, 'p' => $periodId]);
    return $q->fetchAll();
}

function appeal_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Đã gửi',
        'grading' => 'Đang chấm',
        'completed' => 'Đã có kết quả',
        'cancelled' => 'Đã hủy',
        default => $status,
    };
}

function admin_appeals(PDO $pdo, int $periodId, ?int $subjectId = null): array
{
    $sql =
        'SELECT ar.id, ar.admission_period_id, ar.exam_paper_id, ar.exam_subject_id,
                ar.status, ar.original_score, ar.submitted_at,
                c.candidate_code, c.full_name,
                s.subject_code, s.name AS subject_name, s.max_score,
                p.anonymous_code,
                r.id AS result_id, r.new_score, r.old_score, r.change_reason,
                r.grader_user_id, r.created_at AS result_at,
                aga.id AS assignment_id,
                aga.council_member_id AS assigned_council_member_id,
                astaff.full_name AS assigned_grader_name,
                assigned_user.id AS assigned_grader_user_id,
                assigned_user.username AS assigned_grader_username,
                ig.id AS initial_grader_assignment_id,
                istaff.full_name AS initial_grader_name,
                iuser.id AS initial_grader_user_id
         FROM appeal_requests ar
         JOIN candidates c ON c.id = ar.candidate_id
         JOIN exam_subjects s ON s.id = ar.exam_subject_id
         JOIN exam_papers p ON p.id = ar.exam_paper_id
         LEFT JOIN appeal_results r ON r.appeal_request_id = ar.id
         LEFT JOIN appeal_grader_assignments aga ON aga.appeal_request_id = ar.id
         LEFT JOIN council_members acm ON acm.id = aga.council_member_id
         LEFT JOIN staff astaff ON astaff.id = acm.staff_id
         LEFT JOIN users assigned_user ON assigned_user.id = astaff.user_id
         LEFT JOIN exam_paper_grader_assignments ig ON ig.exam_paper_id = ar.exam_paper_id
         LEFT JOIN council_members icm ON icm.id = ig.council_member_id
         LEFT JOIN staff istaff ON istaff.id = icm.staff_id
         LEFT JOIN users iuser ON iuser.id = istaff.user_id
         WHERE ar.admission_period_id = :p';
    $params = ['p' => $periodId];
    if ($subjectId !== null && $subjectId > 0) {
        $sql .= ' AND ar.exam_subject_id = :s';
        $params['s'] = $subjectId;
    }
    $sql .= ' ORDER BY s.name, c.candidate_code, ar.id';
    $q = $pdo->prepare($sql);
    $q->execute($params);
    return $q->fetchAll();
}

function appeal_grader_candidates(PDO $pdo, int $appealId): array
{
    $q = $pdo->prepare(
        'SELECT cm.id AS council_member_id, st.id AS staff_id, st.full_name,
                u.id AS user_id, u.username,
                c.id AS council_id, c.name AS council_name
         FROM appeal_requests ar
         JOIN councils c
           ON c.admission_period_id = ar.admission_period_id
          AND c.council_type = "appeal"
          AND c.status = "established"
         JOIN council_subjects cs ON cs.council_id = c.id AND cs.exam_subject_id = ar.exam_subject_id
         JOIN council_members cm ON cm.council_id = c.id
         JOIN staff st ON st.id = cm.staff_id AND st.active = 1 AND st.user_id IS NOT NULL
         JOIN users u ON u.id = st.user_id AND u.active = 1
         LEFT JOIN exam_paper_grader_assignments ig
           ON ig.exam_paper_id = ar.exam_paper_id AND ig.council_member_id = cm.id
         WHERE ar.id = :appeal
           AND ig.id IS NULL
         ORDER BY st.full_name, u.username'
    );
    $q->execute(['appeal' => $appealId]);
    return $q->fetchAll();
}

function assign_appeal_grader(PDO $pdo, int $appealId, int $councilMemberId, int $assignedByUserId): void
{
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare(
            'SELECT ar.id, ar.admission_period_id, ar.exam_subject_id, ar.exam_paper_id,
                    ar.status,
                    cm.id AS council_member_id, cm.staff_id,
                    c.id AS council_id, c.council_type, c.status AS council_status,
                    cs.exam_subject_id AS council_subject_id,
                    st.active AS staff_active, st.user_id AS grader_user_id,
                    gu.active AS grader_user_active,
                    ig.id AS initial_grader_assignment_id,
                    ist.user_id AS initial_grader_user_id
             FROM appeal_requests ar
             JOIN council_members cm ON cm.id = :member_id
             JOIN councils c ON c.id = cm.council_id
             JOIN council_subjects cs ON cs.council_id = c.id AND cs.exam_subject_id = ar.exam_subject_id
             JOIN staff st ON st.id = cm.staff_id
             LEFT JOIN users gu ON gu.id = st.user_id
             LEFT JOIN exam_paper_grader_assignments ig ON ig.exam_paper_id = ar.exam_paper_id
             LEFT JOIN council_members icm ON icm.id = ig.council_member_id
             LEFT JOIN staff ist ON ist.id = icm.staff_id
             WHERE ar.id = :appeal_id
             FOR UPDATE'
        );
        $q->execute(['member_id' => $councilMemberId, 'appeal_id' => $appealId]);
        $row = $q->fetch();
        if (!$row) throw new RuntimeException('Không tìm thấy đơn hoặc thành viên Hội đồng phúc khảo.');
        if ($row['status'] === 'completed') throw new RuntimeException('Đơn đã có kết quả, không thể phân công lại.');
        if ($row['council_type'] !== 'appeal' || $row['council_status'] !== 'established') {
            throw new RuntimeException('Cán bộ được chọn không thuộc Hội đồng thi phúc khảo đang hoạt động.');
        }
        if ((int)$row['council_subject_id'] !== (int)$row['exam_subject_id']) {
            throw new RuntimeException('Hội đồng phúc khảo không phụ trách môn của đơn.');
        }
        if (!(bool)$row['staff_active'] || empty($row['grader_user_id']) || !(bool)$row['grader_user_active']) {
            throw new RuntimeException('Cán bộ hoặc tài khoản cán bộ không còn hoạt động.');
        }
        if (empty($row['initial_grader_assignment_id'])) {
            throw new RuntimeException('[MISSING SPECIFICATION / DATA DEPENDENCY] Chưa xác định người chấm bài ban đầu cho bài thi.');
        }
        if ((int)$row['initial_grader_user_id'] === (int)$row['grader_user_id']) {
            throw new RuntimeException('Người chấm bài ban đầu không được chấm phúc khảo chính bài thi đó.');
        }

        $upsert = $pdo->prepare(
            'INSERT INTO appeal_grader_assignments (appeal_request_id, council_member_id, assigned_by_user_id)
             VALUES (:appeal, :member, :by)
             ON DUPLICATE KEY UPDATE council_member_id = VALUES(council_member_id), assigned_by_user_id = VALUES(assigned_by_user_id), assigned_at = CURRENT_TIMESTAMP'
        );
        $upsert->execute(['appeal' => $appealId, 'member' => $councilMemberId, 'by' => $assignedByUserId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function set_appeal_grading(PDO $pdo, int $appealId, int $currentUserId): void
{
    $q = $pdo->prepare(
        'SELECT ar.status, aga.council_member_id, st.user_id AS assigned_user_id
         FROM appeal_requests ar
         LEFT JOIN appeal_grader_assignments aga ON aga.appeal_request_id = ar.id
         LEFT JOIN council_members cm ON cm.id = aga.council_member_id
         LEFT JOIN staff st ON st.id = cm.staff_id
         WHERE ar.id = :id
         FOR UPDATE'
    );
    $q->execute(['id' => $appealId]);
    $row = $q->fetch();
    if (!$row) throw new RuntimeException('Không tìm thấy đơn phúc khảo.');
    if ($row['status'] !== 'pending') throw new RuntimeException('Đơn phúc khảo không ở trạng thái Chờ xử lý.');
    if (empty($row['council_member_id']) || (int)$row['assigned_user_id'] !== $currentUserId) {
        throw new RuntimeException('Bạn chưa được phân công cho đơn phúc khảo này.');
    }
    $u = $pdo->prepare('UPDATE appeal_requests SET status = "grading" WHERE id = :id AND status = "pending"');
    $u->execute(['id' => $appealId]);
    if ($u->rowCount() !== 1) throw new RuntimeException('Đơn phúc khảo không ở trạng thái Chờ xử lý.');
}

function validate_appeal_result(PDO $pdo, int $appealId, $newScore, string $reason, ?int $currentUserId = null): array
{
    $reason = trim($reason);
    $q = $pdo->prepare(
        'SELECT ar.id, ar.status, ar.admission_period_id, ar.original_score, ar.exam_paper_id,
                s.max_score, aga.council_member_id, ast.user_id AS assigned_user_id,
                ig.id AS initial_grader_assignment_id, ist.user_id AS initial_grader_user_id
         FROM appeal_requests ar
         JOIN exam_subjects s ON s.id = ar.exam_subject_id
         LEFT JOIN appeal_grader_assignments aga ON aga.appeal_request_id = ar.id
         LEFT JOIN council_members acm ON acm.id = aga.council_member_id
         LEFT JOIN staff ast ON ast.id = acm.staff_id
         LEFT JOIN exam_paper_grader_assignments ig ON ig.exam_paper_id = ar.exam_paper_id
         LEFT JOIN council_members icm ON icm.id = ig.council_member_id
         LEFT JOIN staff ist ON ist.id = icm.staff_id
         WHERE ar.id = :id LIMIT 1'
    );
    $q->execute(['id' => $appealId]);
    $appeal = $q->fetch();

    if (!$appeal) return ['ok' => false, 'error' => 'Không tìm thấy đơn phúc khảo.'];
    if ($appeal['status'] === 'completed') return ['ok' => false, 'error' => 'Đơn đã có kết quả, không thể cập nhật lại.'];
    if ($appeal['status'] !== 'grading') return ['ok' => false, 'error' => 'Đơn phúc khảo phải ở trạng thái Đang chấm.'];
    if ($currentUserId !== null && (int)$appeal['assigned_user_id'] !== $currentUserId) return ['ok' => false, 'error' => 'Bạn chưa được phân công cho đơn phúc khảo này.'];
    if (empty($appeal['council_member_id'])) return ['ok' => false, 'error' => 'Chưa có phân công cán bộ chấm phúc khảo.'];
    if (empty($appeal['initial_grader_assignment_id'])) return ['ok' => false, 'error' => '[MISSING SPECIFICATION / DATA DEPENDENCY] Chưa xác định người chấm bài ban đầu.'];
    if ($currentUserId !== null && (int)$appeal['initial_grader_user_id'] === $currentUserId) return ['ok' => false, 'error' => 'Người chấm bài ban đầu không được chấm phúc khảo chính bài thi đó.'];
    if (!is_numeric($newScore)) return ['ok' => false, 'error' => 'Điểm mới không phải số.'];

    $score = (float)$newScore;
    $max = $appeal['max_score'] !== null ? (float)$appeal['max_score'] : null;
    if ($score < 0 || ($max !== null && $score > $max)) return ['ok' => false, 'error' => 'Điểm mới nằm ngoài thang điểm cho phép.'];
    if ($reason === '') return ['ok' => false, 'error' => 'Thiếu lý do/biên bản thay đổi.'];

    return ['ok' => true, 'appeal_id' => (int)$appeal['id'], 'exam_paper_id' => (int)$appeal['exam_paper_id'], 'old_score' => (float)$appeal['original_score'], 'new_score' => $score, 'reason' => $reason];
}

function uc03_validate_import(PDO $pdo, int $periodId, int $subjectId, array $parsed, int $userId): array
{
    $errors = [];
    $valid = [];
    if ($parsed === []) throw new InvalidArgumentException('File không có dữ liệu.');

    $headers = array_map('excel_normalize_header', $parsed[0]);
    $expected = array_map('excel_normalize_header', UC03_HEADERS);
    if ($headers !== $expected) {
        throw new InvalidArgumentException('Sai cấu trúc cột. Vui lòng dùng đúng file mẫu UC03.');
    }

    foreach (array_slice($parsed, 1) as $index => $row) {
        $row = array_pad($row, count(UC03_HEADERS), '');
        $appealId = (int)trim((string)$row[0]);
        $anonymousCode = trim((string)$row[1]);
        $candidateCode = trim((string)$row[2]);
        $oldScoreFile = trim((string)$row[3]);
        $graderFile = trim((string)$row[4]);
        $newScore = trim((string)$row[5]);
        $reason = trim((string)$row[6]);
        $rowErrors = [];

        if ($appealId <= 0) $rowErrors[] = 'Thiếu appeal_id.';
        if ($newScore === '' || !is_numeric($newScore)) $rowErrors[] = 'Điểm mới không phải số.';
        if ($reason === '') $rowErrors[] = 'Thiếu lý do/biên bản thay đổi.';

        $appeal = null;
        if ($appealId > 0) {
            $q = $pdo->prepare(
                'SELECT ar.id, ar.admission_period_id, ar.exam_subject_id, ar.exam_paper_id,
                        ar.status, ar.original_score, p.anonymous_code,
                        c.candidate_code, s.max_score,
                        aga.council_member_id, au.username AS assigned_username,
                        ast.user_id AS assigned_user_id,
                        ig.id AS initial_grader_assignment_id,
                        iu.id AS initial_grader_user_id
                 FROM appeal_requests ar
                 JOIN exam_papers p ON p.id = ar.exam_paper_id
                 JOIN candidates c ON c.id = ar.candidate_id
                 JOIN exam_subjects s ON s.id = ar.exam_subject_id
                 LEFT JOIN appeal_grader_assignments aga ON aga.appeal_request_id = ar.id
                 LEFT JOIN council_members acm ON acm.id = aga.council_member_id
                 LEFT JOIN staff ast ON ast.id = acm.staff_id
                 LEFT JOIN users au ON au.id = ast.user_id
                 LEFT JOIN exam_paper_grader_assignments ig ON ig.exam_paper_id = ar.exam_paper_id
                 LEFT JOIN council_members icm ON icm.id = ig.council_member_id
                 LEFT JOIN staff ist ON ist.id = icm.staff_id
                 LEFT JOIN users iu ON iu.id = ist.user_id
                 WHERE ar.id = :id LIMIT 1'
            );
            $q->execute(['id' => $appealId]);
            $appeal = $q->fetch();
            if (!$appeal) $rowErrors[] = 'Không tìm thấy đơn phúc khảo.';
        }

        if ($appeal) {
            if ((int)$appeal['admission_period_id'] !== $periodId || (int)$appeal['exam_subject_id'] !== $subjectId) $rowErrors[] = 'Đơn không thuộc kỳ/môn đang xử lý.';
            if ($appeal['status'] === 'completed') $rowErrors[] = 'Đơn đã có kết quả, không thể cập nhật lại.';
            elseif ($appeal['status'] !== 'grading') $rowErrors[] = 'Đơn chưa ở trạng thái Đang chấm.';
            if (empty($appeal['council_member_id']) || (int)$appeal['assigned_user_id'] !== $userId) $rowErrors[] = 'Người dùng hiện tại không được phân công cho đơn này.';
            if (empty($appeal['initial_grader_assignment_id'])) $rowErrors[] = '[MISSING SPECIFICATION / DATA DEPENDENCY] Chưa xác định người chấm bài ban đầu.';
            elseif ((int)$appeal['initial_grader_user_id'] === $userId) $rowErrors[] = 'Người chấm bài ban đầu không được chấm phúc khảo chính bài thi đó.';
            if ($anonymousCode !== (string)$appeal['anonymous_code']) $rowErrors[] = 'Số phách không khớp dữ liệu hệ thống.';
            if ($candidateCode !== (string)$appeal['candidate_code']) $rowErrors[] = 'SBD không khớp dữ liệu hệ thống.';
            if ($oldScoreFile === '' || !is_numeric($oldScoreFile) || abs((float)$oldScoreFile - (float)$appeal['original_score']) > 0.00001) $rowErrors[] = 'Điểm cũ không khớp dữ liệu hệ thống.';
            if ($graderFile !== '' && $graderFile !== (string)$appeal['assigned_username']) $rowErrors[] = 'Người chấm trong file không khớp phân công.';

            $score = is_numeric($newScore) ? (float)$newScore : null;
            $max = $appeal['max_score'] !== null ? (float)$appeal['max_score'] : null;
            if ($score !== null && ($score < 0 || ($max !== null && $score > $max) || $score !== round($score, 2))) $rowErrors[] = 'Điểm mới nằm ngoài thang điểm hoặc vượt quá 2 chữ số thập phân.';

            $dup = $pdo->prepare('SELECT id FROM appeal_results WHERE appeal_request_id = :id LIMIT 1');
            $dup->execute(['id' => $appealId]);
            if ($dup->fetchColumn()) $rowErrors[] = 'Đơn đã có kết quả, không thể cập nhật lại.';
        }

        if ($rowErrors === []) {
            $valid[] = ['appeal_id' => $appealId, 'new_score' => (float)$newScore, 'reason' => $reason];
        } else {
            $errors[] = ['row' => $row, 'error' => implode(' ', $rowErrors)];
        }
    }
    return ['valid' => $valid, 'errors' => $errors];
}

function complete_appeal(PDO $pdo, int $appealId, float $newScore, string $reason, int $graderUserId): void
{
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare(
            'SELECT ar.id, ar.status, ar.admission_period_id, ar.original_score, ar.exam_paper_id,
                    ar.exam_subject_id, c.user_id AS candidate_user_id, s.max_score,
                    aga.council_member_id, ast.user_id AS assigned_user_id,
                    ig.id AS initial_grader_assignment_id, ist.user_id AS initial_grader_user_id
             FROM appeal_requests ar
             JOIN candidates c ON c.id = ar.candidate_id
             JOIN exam_subjects s ON s.id = ar.exam_subject_id
             LEFT JOIN appeal_grader_assignments aga ON aga.appeal_request_id = ar.id
             LEFT JOIN council_members acm ON acm.id = aga.council_member_id
             LEFT JOIN staff ast ON ast.id = acm.staff_id
             LEFT JOIN exam_paper_grader_assignments ig ON ig.exam_paper_id = ar.exam_paper_id
             LEFT JOIN council_members icm ON icm.id = ig.council_member_id
             LEFT JOIN staff ist ON ist.id = icm.staff_id
             WHERE ar.id = :id
             FOR UPDATE'
        );
        $q->execute(['id' => $appealId]);
        $appeal = $q->fetch();
        if (!$appeal) throw new RuntimeException('Không tìm thấy đơn phúc khảo.');
        if ($appeal['status'] !== 'grading') throw new RuntimeException('Đơn phúc khảo phải ở trạng thái Đang chấm.');
        if (empty($appeal['initial_grader_assignment_id'])) throw new RuntimeException('[MISSING SPECIFICATION / DATA DEPENDENCY] Chưa xác định người chấm bài ban đầu.');
        if ((int)$appeal['initial_grader_user_id'] === $graderUserId) throw new RuntimeException('Người chấm bài ban đầu không được chấm phúc khảo chính bài thi đó.');
        if ((int)$appeal['assigned_user_id'] !== $graderUserId) throw new RuntimeException('Bạn chưa được phân công cho đơn phúc khảo này.');

        $max = $appeal['max_score'] !== null ? (float)$appeal['max_score'] : null;
        if ($newScore < 0 || ($max !== null && $newScore > $max)) throw new InvalidArgumentException('Điểm mới nằm ngoài thang điểm cho phép.');
        $reason = trim($reason);
        if ($reason === '') throw new InvalidArgumentException('Thiếu lý do/biên bản thay đổi.');

        $scoreQ = $pdo->prepare('SELECT id, total_score FROM scores WHERE exam_paper_id = :p FOR UPDATE');
        $scoreQ->execute(['p' => (int)$appeal['exam_paper_id']]);
        $scoreRow = $scoreQ->fetch();
        if (!$scoreRow) throw new RuntimeException('Không tìm thấy điểm chính thức để cập nhật.');

        $oldScore = (float)$appeal['original_score'];
        $currentScore = (float)$scoreRow['total_score'];
        if (abs($currentScore - $oldScore) > 0.00001) throw new RuntimeException('Điểm hiện tại không còn khớp điểm cũ của đơn phúc khảo.');
        if ($newScore !== round($newScore, 2)) throw new InvalidArgumentException('Điểm mới chỉ được phép tối đa 2 chữ số thập phân.');

        $changed = abs($newScore - $oldScore) > 0.00001;
        if ($changed) {
            $pub = $pdo->prepare(
                'SELECT 1 FROM publication_events
                 WHERE admission_period_id = :p AND publication_type = "exam_results" AND status = "published"
                 LIMIT 1'
            );
            $pub->execute(['p' => (int)$appeal['admission_period_id']]);
            if ($pub->fetchColumn()) {
                throw new RuntimeException('[BLOCKED — OFFICIAL SCORE WORKFLOW] Điểm thi đã công bố và đang được trigger bảo vệ; chưa có quy trình chính thức cho phép cập nhật điểm chính thức sau công bố.');
            }
        }

        $ins = $pdo->prepare(
            'INSERT INTO appeal_results (appeal_request_id, old_score, new_score, grader_user_id, change_reason)
             VALUES (:a, :old, :new, :u, :reason)'
        );
        $ins->execute(['a' => $appealId, 'old' => $oldScore, 'new' => $newScore, 'u' => $graderUserId, 'reason' => $reason]);

        $updAppeal = $pdo->prepare('UPDATE appeal_requests SET status = "completed", completed_at = CURRENT_TIMESTAMP WHERE id = :id AND status = "grading"');
        $updAppeal->execute(['id' => $appealId]);
        if ($updAppeal->rowCount() !== 1) throw new RuntimeException('Đơn phúc khảo đã được xử lý bởi phiên khác.');

        if ($changed) {
            $updScore = $pdo->prepare('UPDATE scores SET total_score = :total_score, score_1 = :score_1 WHERE exam_paper_id = :p');
            $updScore->execute(['total_score' => $newScore, 'score_1' => $newScore, 'p' => (int)$appeal['exam_paper_id']]);
            $log = $pdo->prepare(
                'INSERT INTO score_change_logs (exam_paper_id, appeal_request_id, old_score, new_score, changed_by_user_id, reason)
                 VALUES (:paper, :appeal, :old, :new, :u, :reason)'
            );
            $log->execute(['paper' => (int)$appeal['exam_paper_id'], 'appeal' => $appealId, 'old' => $oldScore, 'new' => $newScore, 'u' => $graderUserId, 'reason' => $reason]);
        }

        if (!empty($appeal['candidate_user_id'])) {
            $notify = $pdo->prepare('INSERT INTO notifications(user_id, title, message) VALUES (:u, :title, :message)');
            $notify->execute([
                'u' => (int)$appeal['candidate_user_id'],
                'title' => 'Kết quả phúc khảo',
                'message' => 'Đơn phúc khảo của bạn đã có kết quả. Điểm phúc khảo: ' . number_format($newScore, 2),
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function complete_appeal_batch(PDO $pdo, array $items, int $graderUserId): void
{
    if ($items === []) {
        throw new InvalidArgumentException('Không có dòng hợp lệ để lưu.');
    }

    $pdo->beginTransaction();
    try {
        foreach ($items as $item) {
            $appealId = (int)$item['appeal_id'];
            $newScore = (float)$item['new_score'];
            $reason = trim((string)$item['reason']);

            $q = $pdo->prepare(
                'SELECT ar.id, ar.status, ar.admission_period_id, ar.original_score, ar.exam_paper_id,
                        c.user_id AS candidate_user_id, s.max_score,
                        aga.council_member_id, ast.user_id AS assigned_user_id,
                        ig.id AS initial_grader_assignment_id, ist.user_id AS initial_grader_user_id
                 FROM appeal_requests ar
                 JOIN candidates c ON c.id = ar.candidate_id
                 JOIN exam_subjects s ON s.id = ar.exam_subject_id
                 LEFT JOIN appeal_grader_assignments aga ON aga.appeal_request_id = ar.id
                 LEFT JOIN council_members acm ON acm.id = aga.council_member_id
                 LEFT JOIN staff ast ON ast.id = acm.staff_id
                 LEFT JOIN exam_paper_grader_assignments ig ON ig.exam_paper_id = ar.exam_paper_id
                 LEFT JOIN council_members icm ON icm.id = ig.council_member_id
                 LEFT JOIN staff ist ON ist.id = icm.staff_id
                 WHERE ar.id = :id
                 FOR UPDATE'
            );
            $q->execute(['id' => $appealId]);
            $appeal = $q->fetch();
            if (!$appeal) throw new RuntimeException('Không tìm thấy đơn phúc khảo #' . $appealId . '.');
            if ($appeal['status'] !== 'grading') throw new RuntimeException('Đơn #' . $appealId . ' không ở trạng thái Đang chấm.');
            if (empty($appeal['initial_grader_assignment_id'])) throw new RuntimeException('[MISSING SPECIFICATION / DATA DEPENDENCY] Chưa xác định người chấm bài ban đầu cho đơn #' . $appealId . '.');
            if ((int)$appeal['initial_grader_user_id'] === $graderUserId) throw new RuntimeException('Người chấm bài ban đầu không được chấm phúc khảo đơn #' . $appealId . '.');
            if ((int)$appeal['assigned_user_id'] !== $graderUserId) throw new RuntimeException('Bạn chưa được phân công cho đơn #' . $appealId . '.');
            if ($reason === '') throw new InvalidArgumentException('Đơn #' . $appealId . ' thiếu lý do/biên bản thay đổi.');

            $max = $appeal['max_score'] !== null ? (float)$appeal['max_score'] : null;
            if ($newScore < 0 || ($max !== null && $newScore > $max) || $newScore !== round($newScore, 2)) {
                throw new InvalidArgumentException('Điểm mới của đơn #' . $appealId . ' không hợp lệ.');
            }

            $scoreQ = $pdo->prepare('SELECT id, total_score FROM scores WHERE exam_paper_id = :p FOR UPDATE');
            $scoreQ->execute(['p' => (int)$appeal['exam_paper_id']]);
            $scoreRow = $scoreQ->fetch();
            if (!$scoreRow) throw new RuntimeException('Không tìm thấy điểm chính thức của đơn #' . $appealId . '.');

            $oldScore = (float)$appeal['original_score'];
            $currentScore = (float)$scoreRow['total_score'];
            if (abs($currentScore - $oldScore) > 0.00001) throw new RuntimeException('Điểm hiện tại không còn khớp điểm cũ của đơn #' . $appealId . '.');

            $changed = abs($newScore - $oldScore) > 0.00001;
            if ($changed) {
                $pub = $pdo->prepare('SELECT 1 FROM publication_events WHERE admission_period_id = :p AND publication_type = "exam_results" AND status = "published" LIMIT 1');
                $pub->execute(['p' => (int)$appeal['admission_period_id']]);
                if ($pub->fetchColumn()) throw new RuntimeException('[BLOCKED — OFFICIAL SCORE WORKFLOW] Điểm thi đã công bố; chưa có quy trình chính thức cho phép cập nhật điểm chính thức sau công bố.');
            }

            $ins = $pdo->prepare('INSERT INTO appeal_results (appeal_request_id, old_score, new_score, grader_user_id, change_reason) VALUES (:a,:old,:new,:u,:reason)');
            $ins->execute(['a' => $appealId, 'old' => $oldScore, 'new' => $newScore, 'u' => $graderUserId, 'reason' => $reason]);

            $u = $pdo->prepare('UPDATE appeal_requests SET status = "completed", completed_at = CURRENT_TIMESTAMP WHERE id=:id AND status="grading"');
            $u->execute(['id' => $appealId]);
            if ($u->rowCount() !== 1) throw new RuntimeException('Đơn #' . $appealId . ' đã được xử lý bởi phiên khác.');

            if ($changed) {
                $upd = $pdo->prepare('UPDATE scores SET total_score=:total_score, score_1=:score_1 WHERE exam_paper_id=:p');
                $upd->execute(['total_score' => $newScore, 'score_1' => $newScore, 'p' => (int)$appeal['exam_paper_id']]);
                $log = $pdo->prepare('INSERT INTO score_change_logs (exam_paper_id, appeal_request_id, old_score, new_score, changed_by_user_id, reason) VALUES (:paper,:appeal,:old,:new,:u,:reason)');
                $log->execute(['paper' => (int)$appeal['exam_paper_id'], 'appeal' => $appealId, 'old' => $oldScore, 'new' => $newScore, 'u' => $graderUserId, 'reason' => $reason]);
            }

            if (!empty($appeal['candidate_user_id'])) {
                $notify = $pdo->prepare('INSERT INTO notifications(user_id,title,message) VALUES (:u,:title,:message)');
                $notify->execute([
                    'u' => (int)$appeal['candidate_user_id'],
                    'title' => 'Kết quả phúc khảo',
                    'message' => 'Đơn phúc khảo của bạn đã có kết quả. Điểm phúc khảo: ' . number_format($newScore, 2),
                ]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
