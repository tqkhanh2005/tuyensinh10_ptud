<?php
declare(strict_types=1);

/** Read-only setup indicators, scoped to one published (or latest draft) period. */
function admission_readiness(PDO $pdo): array
{
    $period = $pdo->query('SELECT id,name,academic_year,status,registration_start,registration_end FROM admission_periods ORDER BY (status="published") DESC,id DESC LIMIT 1')->fetch() ?: null;
    $periodId = (int)($period['id'] ?? 0);
    $count = static function (string $sql, array $params = []) use ($pdo): int {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return (int)$statement->fetchColumn();
    };
    $students = $count('SELECT COUNT(*) FROM candidates WHERE status="active"');
    $schools = $count('SELECT COUNT(*) FROM schools WHERE level="THPT" AND active=1');
    $teachers = $count('SELECT COUNT(*) FROM staff st JOIN users u ON u.id=st.user_id AND u.active=1 JOIN schools s ON s.id=st.school_id AND s.active=1 WHERE st.active=1 AND EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id AND r.active=1 WHERE ur.user_id=u.id AND r.code IN ("teacher","teacher_thcs","teacher_thpt","staff"))');
    $p = ['p' => $periodId];
    $quotas = $periodId ? $count('SELECT COUNT(*) FROM admission_quotas WHERE admission_period_id=:p AND status="set" AND quota>0', $p) : 0;
    $aspirations = $periodId ? $count('SELECT COUNT(DISTINCT candidate_id) FROM aspirations WHERE admission_period_id=:p', $p) : 0;
    $subjects = $periodId ? $count('SELECT COUNT(*) FROM exam_subjects WHERE admission_period_id=:p', $p) : 0;
    $rooms = $periodId ? $count('SELECT COUNT(*) FROM exam_rooms r JOIN exam_sites s ON s.id=r.exam_site_id WHERE s.admission_period_id=:p', $p) : 0;
    $councils = $periodId ? $count('SELECT COUNT(*) FROM councils WHERE admission_period_id=:p', $p) : 0;
    $publication = $periodId ? $count('SELECT COUNT(*) FROM publication_events WHERE admission_period_id=:p AND publication_type="admission_results" AND status="published"', $p) : 0;
    $suffix = $periodId ? '?period_id=' . $periodId : '';
    $steps = [
        ['label'=>'Hồ sơ học sinh', 'detail'=>$students . ' học sinh đang hoạt động', 'available'=>$students>0, 'path'=>'/candidates/index.php', 'permission'=>'candidate.manage'],
        ['label'=>'Trường THPT', 'detail'=>$schools . ' trường đang hoạt động', 'available'=>$schools>0, 'path'=>'/schools/index.php', 'permission'=>'school.manage'],
        ['label'=>'Tài khoản giáo viên', 'detail'=>$teachers . ' giáo viên có tài khoản và trường liên kết đang hoạt động', 'available'=>$teachers>0, 'path'=>'/staff/index.php', 'permission'=>'staff.manage'],
        ['label'=>'Kỳ tuyển sinh', 'detail'=>$period ? $period['name'] . ' · ' . ['draft'=>'Bản nháp','published'=>'Đã công bố','closed'=>'Đã đóng'][$period['status']] : 'Chưa thiết lập kỳ tuyển sinh', 'available'=>$periodId>0, 'path'=>'/admission/index.php', 'permission'=>'admission.manage'],
        ['label'=>'Chỉ tiêu', 'detail'=>$quotas . ' trường đã đặt chỉ tiêu trong kỳ', 'available'=>$quotas>0, 'path'=>'/quotas/index.php' . $suffix, 'permission'=>'admission.manage'],
        ['label'=>'Nguyện vọng', 'detail'=>$aspirations . ' học sinh có nguyện vọng trong kỳ', 'available'=>$aspirations>0, 'path'=>'/aspirations/index.php' . $suffix, 'permission'=>'aspiration.manage'],
        ['label'=>'Tổ chức thi', 'detail'=>"$subjects môn thi · $rooms phòng thi · $councils hội đồng trong kỳ", 'available'=>$subjects>0 && $rooms>0 && $councils>0, 'path'=>'/exam/organization.php' . $suffix, 'permission'=>'exam.manage'],
        ['label'=>'Kết quả tuyển sinh', 'detail'=>$publication ? 'Đã công bố kết quả của kỳ' : 'Chưa công bố kết quả của kỳ', 'available'=>$publication>0, 'path'=>'/admission/index.php', 'permission'=>'admission.manage'],
    ];
    return ['period'=>$period, 'steps'=>$steps];
}
