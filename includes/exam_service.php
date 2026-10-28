<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/exam_organization_service.php';
require_once __DIR__ . '/exam_grading_service.php';
require_once __DIR__ . '/excel_service.php';

function exam_sites(PDO $pdo, int $periodId): array {
    $s = $pdo->prepare('SELECT * FROM exam_sites WHERE admission_period_id=:p ORDER BY name');
    $s->execute(['p' => $periodId]);
    return $s->fetchAll();
}

function exam_rooms(PDO $pdo, int $siteId): array {
    $s = $pdo->prepare('SELECT * FROM exam_rooms WHERE exam_site_id=:s ORDER BY room_code');
    $s->execute(['s' => $siteId]);
    return $s->fetchAll();
}

function create_exam_site(PDO $pdo, array $d): int {
    $periodId = (int)($d['period_id'] ?? 0);
    $capacity = filter_var($d['capacity'] ?? null,FILTER_VALIDATE_INT);
    if($capacity===false || $capacity===null || $capacity<1) throw new InvalidArgumentException('Sức chứa phải là số nguyên dương.');
    if ($periodId < 1 || $capacity < 0 || trim((string)($d['site_code'] ?? '')) === '' || trim((string)($d['name'] ?? '')) === '') {
        throw new InvalidArgumentException('Thông tin điểm thi không hợp lệ.');
    }
    $period = $pdo->prepare('SELECT id FROM admission_periods WHERE id=:p LIMIT 1');
    $period->execute(['p' => $periodId]);
    if (!$period->fetchColumn()) {
        throw new InvalidArgumentException('Kỳ tuyển sinh không tồn tại.');
    }
    $pdo->beginTransaction();
    try {
        exam_organization_editable($pdo,$periodId);
    $s = $pdo->prepare('INSERT INTO exam_sites(admission_period_id,school_id,site_code,name,address,capacity,status)
                      VALUES(:p,:school,:code,:name,:address,:cap,"ready")');
    $s->execute([
        'p' => $periodId,
        'school' => ($d['school_id'] ?? '') !== '' ? (int)$d['school_id'] : null,
        'code' => trim((string)$d['site_code']),
        'name' => trim((string)$d['name']),
        'address' => trim((string)($d['address'] ?? '')) ?: null,
        'cap' => $capacity
    ]);
    $id=(int)$pdo->lastInsertId();$pdo->commit();return $id;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

function create_exam_room(PDO $pdo, array $d): int {
    $siteId = (int)($d['site_id'] ?? 0);
    $capacity = filter_var($d['capacity'] ?? null,FILTER_VALIDATE_INT);
    if($capacity===false || $capacity===null || $capacity<1) throw new InvalidArgumentException('Sức chứa phải là số nguyên dương.');
    if ($siteId < 1 || $capacity < 0 || trim((string)($d['room_code'] ?? '')) === '' || trim((string)($d['name'] ?? '')) === '') {
        throw new InvalidArgumentException('Thông tin phòng thi không hợp lệ.');
    }
    $site = $pdo->prepare('SELECT id,admission_period_id,capacity FROM exam_sites WHERE id=:id LIMIT 1');
    $site->execute(['id' => $siteId]);
    $site=$site->fetch();
    if (!$site) {
        throw new InvalidArgumentException('Điểm thi không tồn tại.');
    }
    $pdo->beginTransaction();
    try {
        exam_organization_editable($pdo,(int)$site['admission_period_id']);
        $lock=$pdo->prepare('SELECT capacity FROM exam_sites WHERE id=? FOR UPDATE');$lock->execute([$siteId]);$cap=(int)$lock->fetchColumn();
        $used=$pdo->prepare('SELECT COALESCE(SUM(capacity),0) FROM exam_rooms WHERE exam_site_id=?');$used->execute([$siteId]);
        if((int)$used->fetchColumn()+$capacity>$cap)throw new InvalidArgumentException('Tổng sức chứa phòng vượt sức chứa điểm thi.');
    $s = $pdo->prepare('INSERT INTO exam_rooms(exam_site_id,room_code,name,capacity,status)
                      VALUES(:site,:code,:name,:cap,"ready")');
    $s->execute([
        'site' => $siteId,
        'code' => trim((string)$d['room_code']),
        'name' => trim((string)$d['name']),
        'cap' => $capacity
    ]);
    $id=(int)$pdo->lastInsertId();$pdo->commit();return $id;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

/**
 * Candidate-period membership is not a first-class relation in the current schema.
 * For this batch, the existing period-scoped aspirations relation is used as the
 * source of period membership; existing allocations are also included so that
 * already-assigned candidates remain adjustable.
 * [MISSING SPECIFICATION] The requirements do not define a dedicated candidate-
 * admission-period table or another authoritative period-membership source.
 */
function candidate_rows(PDO $pdo, int $periodId): array {
    $q = $pdo->prepare("SELECT c.id,c.candidate_code,c.full_name,
                              a.exam_site_id AS allocated_site_id
                       FROM candidates c
                       LEFT JOIN exam_allocations a
                         ON a.candidate_id=c.id AND a.admission_period_id=:period
                       WHERE c.status='active' AND c.eligible=1
                         AND (a.id IS NOT NULL OR EXISTS (
                             SELECT 1 FROM aspirations asp
                             WHERE asp.candidate_id=c.id
                               AND asp.admission_period_id=:period2
                         ))
                       ORDER BY c.candidate_code,c.id");
    $q->execute(['period' => $periodId, 'period2' => $periodId]);
    return $q->fetchAll();
}

function candidate_belongs_to_period(PDO $pdo, int $periodId, int $candidateId): bool {
    $q = $pdo->prepare("SELECT 1
                        FROM candidates c
                        WHERE c.id=:candidate
                          AND c.status='active'
                          AND c.eligible=1
                          AND (
                              EXISTS (SELECT 1 FROM exam_allocations a
                                      WHERE a.admission_period_id=:period1 AND a.candidate_id=c.id)
                              OR EXISTS (SELECT 1 FROM aspirations asp
                                         WHERE asp.admission_period_id=:period2 AND asp.candidate_id=c.id)
                          )
                        LIMIT 1");
    $q->execute(['candidate' => $candidateId, 'period1' => $periodId, 'period2' => $periodId]);
    return (bool)$q->fetchColumn();
}

function allocation_preview(PDO $pdo, int $periodId): array {
    if ($periodId < 1) throw new InvalidArgumentException('Kỳ thi không hợp lệ.');
    $candidates = array_values(array_filter(candidate_rows($pdo, $periodId), static fn(array $c): bool => $c['allocated_site_id'] === null));
    $sites = exam_sites($pdo, $periodId);
    $countQ = $pdo->prepare('SELECT exam_site_id,COUNT(*) AS cnt
                             FROM exam_allocations
                             WHERE admission_period_id=:p
                             GROUP BY exam_site_id');
    $countQ->execute(['p' => $periodId]);
    $used = [];
    foreach ($sites as $site) $used[(int)$site['id']] = 0;
    foreach ($countQ as $row) {
        $siteId = (int)$row['exam_site_id'];
        if (isset($used[$siteId])) $used[$siteId] = (int)$row['cnt'];
    }

    $available = 0;
    foreach ($sites as $site) {
        $siteId = (int)$site['id'];
        $available += max(0, (int)$site['capacity'] - ($used[$siteId] ?? 0));
    }
    if (count($candidates) > $available) {
        throw new RuntimeException('Không đủ sức chứa để phân bố toàn bộ thí sinh.');
    }

    $result = [];
    $siteIndex = 0;
    while ($siteIndex < count($sites) && $used[(int)$sites[$siteIndex]['id']] >= (int)$sites[$siteIndex]['capacity']) $siteIndex++;
    foreach ($candidates as $candidate) {
        while ($siteIndex < count($sites) && $used[(int)$sites[$siteIndex]['id']] >= (int)$sites[$siteIndex]['capacity']) {
            $siteIndex++;
        }
        if ($siteIndex >= count($sites)) throw new RuntimeException('Không đủ sức chứa để phân bố toàn bộ thí sinh.');
        $siteId = (int)$sites[$siteIndex]['id'];
        $result[(int)$candidate['id']] = $siteId;
        $used[$siteId]++;
    }
    return $result;
}

function save_allocations(PDO $pdo, int $periodId, array $assignments): void {
    if ($periodId < 1) throw new InvalidArgumentException('Kỳ thi không hợp lệ.');
    $pdo->beginTransaction();
    try {
        exam_organization_editable($pdo,$periodId);
        $capacity = [];
        $count = [];
        $q = $pdo->prepare('SELECT id,capacity FROM exam_sites WHERE admission_period_id=:p FOR UPDATE');
        $q->execute(['p' => $periodId]);
        foreach ($q as $r) {
            $capacity[(int)$r['id']] = (int)$r['capacity'];
            $count[(int)$r['id']] = 0;
        }

        $existing = $pdo->prepare('SELECT exam_site_id FROM exam_allocations WHERE admission_period_id=:p FOR UPDATE');
        $existing->execute(['p' => $periodId]);
        foreach ($existing as $r) {
            if (isset($count[(int)$r['exam_site_id']])) $count[(int)$r['exam_site_id']]++;
        }

        $up = $pdo->prepare('INSERT INTO exam_allocations(admission_period_id,candidate_id,exam_site_id)
                           VALUES(:p,:c,:s)
                           ON DUPLICATE KEY UPDATE exam_site_id=VALUES(exam_site_id),assigned_at=CURRENT_TIMESTAMP');
        $oldStmt = $pdo->prepare('SELECT exam_site_id FROM exam_allocations WHERE admission_period_id=:p AND candidate_id=:c FOR UPDATE');

        foreach ($assignments as $candidateId => $siteId) {
            if ($siteId === '' || $siteId === null) continue;
            $candidateId = (int)$candidateId;
            $siteId = (int)$siteId;
            if ($candidateId < 1 || !candidate_belongs_to_period($pdo, $periodId, $candidateId)) {
                throw new InvalidArgumentException('Thí sinh không thuộc kỳ thi đang xử lý.');
            }
            if (!isset($capacity[$siteId])) throw new InvalidArgumentException('Điểm thi không thuộc kỳ thi.');

            $oldStmt->execute(['p' => $periodId, 'c' => $candidateId]);
            $oldSite = $oldStmt->fetchColumn();
            if ($oldSite !== false && (int)$oldSite === $siteId) continue;
            if ($oldSite !== false && isset($count[(int)$oldSite])) $count[(int)$oldSite]--;
            if ($count[$siteId] + 1 > $capacity[$siteId]) throw new RuntimeException('Điểm thi vượt sức chứa.');
            $up->execute(['p' => $periodId, 'c' => $candidateId, 's' => $siteId]);
            $count[$siteId]++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function room_preview(PDO $pdo, int $periodId, int $siteId): array {
    if ($periodId < 1 || $siteId < 1) throw new InvalidArgumentException('Kỳ thi hoặc điểm thi không hợp lệ.');
    $site = $pdo->prepare('SELECT id FROM exam_sites WHERE id=:site AND admission_period_id=:period LIMIT 1');
    $site->execute(['site' => $siteId, 'period' => $periodId]);
    if (!$site->fetchColumn()) throw new InvalidArgumentException('Điểm thi không thuộc kỳ thi.');

    $q = $pdo->prepare("SELECT c.id,c.candidate_code,c.full_name,ra.exam_room_id AS assigned_room_id
                        FROM candidates c
                        JOIN exam_allocations a
                          ON a.candidate_id=c.id AND a.admission_period_id=:p AND a.exam_site_id=:s
                        LEFT JOIN room_assignments ra
                          ON ra.candidate_id=c.id AND ra.admission_period_id=:p2
                        WHERE c.status='active' AND c.eligible=1
                        ORDER BY c.candidate_code,c.id");
    $q->execute(['p' => $periodId, 's' => $siteId, 'p2' => $periodId]);
    $candidates = $q->fetchAll();
    $rooms = exam_rooms($pdo, $siteId);
    $unassigned = array_values(array_filter($candidates, static fn(array $c): bool => $c['assigned_room_id'] === null));

    $countQ = $pdo->prepare('SELECT exam_room_id,COUNT(*) AS cnt
                             FROM room_assignments
                             WHERE admission_period_id=:p AND exam_site_id=:site
                             GROUP BY exam_room_id');
    $countQ->execute(['p' => $periodId, 'site' => $siteId]);
    $used = [];
    foreach ($rooms as $room) $used[(int)$room['id']] = 0;
    foreach ($countQ as $row) {
        $roomId = (int)$row['exam_room_id'];
        if (isset($used[$roomId])) $used[$roomId] = (int)$row['cnt'];
    }

    $available = 0;
    foreach ($rooms as $room) {
        $roomId = (int)$room['id'];
        $available += max(0, (int)$room['capacity'] - ($used[$roomId] ?? 0));
    }
    if (count($unassigned) > $available) throw new RuntimeException('Không đủ sức chứa để sắp xếp toàn bộ thí sinh.');

    $result = [];
    $i = 0;
    foreach ($unassigned as $candidate) {
        while ($i < count($rooms) && $used[(int)$rooms[$i]['id']] >= (int)$rooms[$i]['capacity']) $i++;
        if ($i >= count($rooms)) throw new RuntimeException('Không đủ sức chứa để sắp xếp toàn bộ thí sinh.');
        $roomId = (int)$rooms[$i]['id'];
        $result[(int)$candidate['id']] = $roomId;
        $used[$roomId]++;
    }
    return $result;
}

function save_room_assignments(PDO $pdo, int $periodId, int $siteId, array $assignments): void {
    if ($periodId < 1 || $siteId < 1) throw new InvalidArgumentException('Kỳ thi hoặc điểm thi không hợp lệ.');
    $pdo->beginTransaction();
    try {
        exam_organization_editable($pdo,$periodId);
        $site = $pdo->prepare('SELECT id FROM exam_sites WHERE id=:site AND admission_period_id=:period FOR UPDATE');
        $site->execute(['site' => $siteId, 'period' => $periodId]);
        if (!$site->fetchColumn()) throw new InvalidArgumentException('Điểm thi không thuộc kỳ thi.');

        $rooms = [];
        foreach ($assignments as $roomId) {
            if ($roomId === '' || $roomId === null) continue;
            $roomId = (int)$roomId;
            if (isset($rooms[$roomId])) continue;
            $s = $pdo->prepare('SELECT id,exam_site_id,capacity FROM exam_rooms WHERE id=:id AND exam_site_id=:site FOR UPDATE');
            $s->execute(['id' => $roomId, 'site' => $siteId]);
            $row = $s->fetch();
            if (!$row) throw new InvalidArgumentException('Phòng thi không thuộc điểm thi đã chọn.');
            $rooms[$roomId] = ['site' => (int)$row['exam_site_id'], 'capacity' => (int)$row['capacity'], 'count' => 0];
        }

        // Preload existing counts for the selected site's rooms, including assignments
        // from previous requests, before processing new/changed assignments.
        $countQ = $pdo->prepare('SELECT exam_room_id,COUNT(*) AS cnt
                                 FROM room_assignments
                                 WHERE admission_period_id=:p AND exam_site_id=:site
                                 GROUP BY exam_room_id');
        $countQ->execute(['p' => $periodId, 'site' => $siteId]);
        foreach ($countQ as $row) {
            $roomId = (int)$row['exam_room_id'];
            if (isset($rooms[$roomId])) $rooms[$roomId]['count'] = (int)$row['cnt'];
        }

        $candidateQ = $pdo->prepare('SELECT a.exam_site_id,ra.exam_room_id
                                     FROM exam_allocations a
                                     LEFT JOIN room_assignments ra
                                       ON ra.admission_period_id=a.admission_period_id AND ra.candidate_id=a.candidate_id
                                     WHERE a.admission_period_id=:p AND a.candidate_id=:c
                                     FOR UPDATE');
        $up = $pdo->prepare('INSERT INTO room_assignments(admission_period_id,candidate_id,exam_site_id,exam_room_id)
                             VALUES(:p,:c,:s,:r)
                             ON DUPLICATE KEY UPDATE exam_site_id=VALUES(exam_site_id),exam_room_id=VALUES(exam_room_id),assigned_at=CURRENT_TIMESTAMP');

        foreach ($assignments as $candidateId => $roomId) {
            if ($roomId === '' || $roomId === null) continue;
            $candidateId = (int)$candidateId;
            $roomId = (int)$roomId;
            if (!isset($rooms[$roomId])) throw new InvalidArgumentException('Phòng thi không thuộc điểm thi đã chọn.');

            $candidateQ->execute(['p' => $periodId, 'c' => $candidateId]);
            $allocation = $candidateQ->fetch();
            if (!$allocation || (int)$allocation['exam_site_id'] !== $siteId) {
                throw new InvalidArgumentException('Thí sinh không thuộc điểm thi đã chọn trong kỳ thi.');
            }

            $oldRoom = $allocation['exam_room_id'];
            if ($oldRoom !== null && (int)$oldRoom === $roomId) continue;
            if ($oldRoom !== null && isset($rooms[(int)$oldRoom])) $rooms[(int)$oldRoom]['count']--;
            if ($rooms[$roomId]['count'] + 1 > $rooms[$roomId]['capacity']) throw new RuntimeException('Phòng thi vượt sức chứa.');
            $up->execute(['p' => $periodId, 'c' => $candidateId, 's' => $siteId, 'r' => $roomId]);
            $rooms[$roomId]['count']++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/** Allocate only unassigned students. A shortage anywhere rolls back the entire operation. */
function exam_organization_allocate_all(PDO $pdo,int $periodId): void {
    $pdo->beginTransaction();
    try {
        exam_organization_editable($pdo,$periodId);
        $lock=$pdo->prepare('SELECT id FROM exam_sites WHERE admission_period_id=? ORDER BY id FOR UPDATE');$lock->execute([$periodId]);$lock->fetchAll();
        $insert=$pdo->prepare('INSERT INTO exam_allocations(admission_period_id,candidate_id,exam_site_id) VALUES(?,?,?)');
        foreach(allocation_preview($pdo,$periodId) as $candidate=>$site)$insert->execute([$periodId,$candidate,$site]);
        $insert=$pdo->prepare('INSERT INTO room_assignments(admission_period_id,candidate_id,exam_site_id,exam_room_id) VALUES(?,?,?,?)');
        foreach(exam_sites($pdo,$periodId) as $site)foreach(room_preview($pdo,$periodId,(int)$site['id']) as $candidate=>$room)$insert->execute([$periodId,$candidate,$site['id'],$room]);
        $pdo->commit();
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

function exam_card_for_user(PDO $pdo, int $userId, ?int $periodId=null): ?array {
    if($periodId===null){
        $select=$pdo->prepare('SELECT a.admission_period_id FROM exam_allocations a JOIN candidates c ON c.id=a.candidate_id WHERE c.user_id=? ORDER BY a.admission_period_id DESC LIMIT 1');$select->execute([$userId]);$periodId=(int)$select->fetchColumn();
    }
    if(!$periodId)return null;
    if(period_configuration($pdo,$periodId))return exam_organized_card($pdo,$userId,$periodId);
    $q = $pdo->prepare("SELECT c.id,c.full_name,c.exam_number,
                               s.name AS exam_site_name,
                               r.name AS exam_room_name,
                               GROUP_CONCAT(
                                   CONCAT(
                                       es.name,
                                       CASE WHEN es.exam_date IS NOT NULL THEN CONCAT(' - ', DATE_FORMAT(es.exam_date,'%d/%m/%Y')) ELSE '' END,
                                       CASE WHEN es.start_time IS NOT NULL THEN CONCAT(' ', TIME_FORMAT(es.start_time,'%H:%i')) ELSE '' END,
                                       CASE WHEN es.end_time IS NOT NULL THEN CONCAT('-', TIME_FORMAT(es.end_time,'%H:%i')) ELSE '' END
                                   )
                                   ORDER BY es.exam_date,es.start_time,es.id SEPARATOR ' | '
                               ) AS exam_schedule
                        FROM candidates c
                        JOIN exam_allocations a
                          ON a.candidate_id=c.id
                        JOIN exam_sites s
                          ON s.id=a.exam_site_id
                         AND s.admission_period_id=a.admission_period_id
                        JOIN room_assignments ra
                          ON ra.candidate_id=c.id
                         AND ra.admission_period_id=a.admission_period_id
                         AND ra.exam_site_id=a.exam_site_id
                        JOIN exam_rooms r
                          ON r.id=ra.exam_room_id
                         AND r.exam_site_id=ra.exam_site_id
                        LEFT JOIN exam_subjects es
                          ON es.admission_period_id=a.admission_period_id
                        WHERE c.user_id=:user_id AND a.admission_period_id=:period_id
                        GROUP BY c.id,c.full_name,c.exam_number,s.name,r.name
                        LIMIT 1");
    $q->execute(['user_id' => $userId,'period_id'=>$periodId]);
    $row = $q->fetch();
    return $row ?: null;
}

function invigilator_assign(PDO $pdo,int $roomId,int $subjectId,int $staff1,int $staff2):void {
    if($staff1<1 || $staff2<1 || $staff1===$staff2) throw new InvalidArgumentException('Giám thị 1 và 2 phải là hai người khác nhau.');
    $context = $pdo->prepare(
        'SELECT r.id AS room_id,r.exam_site_id,s.admission_period_id,
                es.id AS subject_id,es.exam_date,es.start_time,es.end_time
         FROM exam_rooms r
         JOIN exam_sites s ON s.id=r.exam_site_id
         JOIN exam_subjects es ON es.admission_period_id=s.admission_period_id
         WHERE r.id=:room AND es.id=:subject LIMIT 1'
    );
    $context->execute(['room'=>$roomId,'subject'=>$subjectId]);
    $t=$context->fetch();
    if(!$t) throw new InvalidArgumentException('Phòng thi và môn thi không thuộc cùng kỳ tuyển sinh.');

    $staff = $pdo->prepare('SELECT id,active FROM staff WHERE id IN (:s1,:s2)');
    $staff->execute(['s1'=>$staff1,'s2'=>$staff2]);
    $active=[];
    foreach($staff as $row) $active[(int)$row['id']] = (int)$row['active'];
    foreach([$staff1,$staff2] as $sid){
        if(!isset($active[$sid])) throw new InvalidArgumentException('Cán bộ coi thi không tồn tại.');
        if($active[$sid]!==1) throw new InvalidArgumentException('Cán bộ coi thi không hoạt động.');
    }

    $pdo->beginTransaction();
    try {
        exam_organization_editable($pdo,(int)$t['admission_period_id']);
        $context->execute(['room'=>$roomId,'subject'=>$subjectId]);$t=$context->fetch();
        if(!$t)throw new InvalidArgumentException('Phòng thi và môn thi không còn cùng kỳ.');
        $lock=$pdo->prepare('SELECT id,active FROM staff WHERE id IN (?,?) ORDER BY id FOR UPDATE');$lock->execute([$staff1,$staff2]);$locked=$lock->fetchAll();
        if(count($locked)!==2 || array_sum(array_column($locked,'active'))!==2)throw new InvalidArgumentException('Cán bộ coi thi không hoạt động.');
        if(period_configuration($pdo,(int)$t['admission_period_id']) && (!$t['exam_date'] || !$t['start_time'] || !$t['end_time']))throw new InvalidArgumentException('Cần hoàn tất lịch thi trước khi phân công.');

        $existing=$pdo->prepare('SELECT position,staff_id FROM exam_invigilator_assignments WHERE exam_room_id=:room AND exam_subject_id=:subject AND status="assigned" FOR UPDATE');
        $existing->execute(['room'=>$roomId,'subject'=>$subjectId]);
        if($existing->fetch()) throw new InvalidArgumentException('Phòng/môn này đã có phân công cán bộ coi thi. Dùng chức năng thay thế để đổi cán bộ.');

        $conflict=$pdo->prepare(
            'SELECT COUNT(*) FROM exam_invigilator_assignments a
             JOIN exam_subjects e ON e.id=a.exam_subject_id
             JOIN exam_rooms r ON r.id=a.exam_room_id
             JOIN exam_sites site ON site.id=r.exam_site_id
             WHERE a.staff_id IN (:s1,:s2)
               AND a.status="assigned"
               AND e.exam_date=:d
               AND e.start_time < :end_time
               AND e.end_time > :start_time
               AND NOT (a.exam_room_id=:room AND a.exam_subject_id=:subject)'
        );
        $conflict->execute([
            's1'=>$staff1,'s2'=>$staff2,'d'=>$t['exam_date'],
            'end_time'=>$t['end_time'],'start_time'=>$t['start_time'],
            'room'=>$roomId,'subject'=>$subjectId
        ]);
        if((int)$conflict->fetchColumn()>0) throw new RuntimeException('Cán bộ coi thi bị xung đột thời gian.');

        $up=$pdo->prepare(
            'INSERT INTO exam_invigilator_assignments(exam_room_id,exam_subject_id,staff_id,position)
             VALUES(:r,:s,:u,:pos)
             ON DUPLICATE KEY UPDATE staff_id=VALUES(staff_id),status="assigned",assigned_at=CURRENT_TIMESTAMP'
        );
        $up->execute(['r'=>$roomId,'s'=>$subjectId,'u'=>$staff1,'pos'=>'invigilator_1']);
        $up->execute(['r'=>$roomId,'s'=>$subjectId,'u'=>$staff2,'pos'=>'invigilator_2']);

        if($pdo->query('SELECT 1')->fetchColumn()) {
            $check=$pdo->prepare(
                'SELECT COUNT(*) FROM exam_rooms r
                 JOIN exam_sites site ON site.id=r.exam_site_id
                 WHERE site.id=:site
                   AND EXISTS (SELECT 1 FROM room_assignments ra WHERE ra.exam_room_id=r.id AND ra.admission_period_id=site.admission_period_id)
                   AND NOT EXISTS (
                       SELECT 1 FROM exam_invigilator_assignments a
                       WHERE a.exam_room_id=r.id AND a.exam_subject_id=:subject AND a.status="assigned"
                   )'
            );
            $check->execute(['site'=>(int)$t['exam_site_id'],'subject'=>$subjectId]);
            if((int)$check->fetchColumn()===0){
                $u=$pdo->prepare('UPDATE exam_sites SET status="assigned" WHERE id=:site');
                $u->execute(['site'=>(int)$t['exam_site_id']]);
            }
        }
        $pdo->commit();
    } catch(Throwable $e) {
        if($pdo->inTransaction()) $pdo->rollBack(); throw $e;
    }
}

function invigilator_replace(PDO $pdo,int $assignmentId,int $replacementStaffId):void {
    if($assignmentId<1 || $replacementStaffId<1) throw new InvalidArgumentException('Thông tin thay thế không hợp lệ.');
    $pdo->beginTransaction();
    try {
        $q=$pdo->prepare(
            'SELECT a.id,a.exam_room_id,a.exam_subject_id,a.staff_id,a.position,a.status,
                    es.admission_period_id,es.exam_date,es.start_time,es.end_time
             FROM exam_invigilator_assignments a
             JOIN exam_subjects es ON es.id=a.exam_subject_id
             WHERE a.id=:id FOR UPDATE'
        );
        $q->execute(['id'=>$assignmentId]); $a=$q->fetch();
        if(!$a) throw new InvalidArgumentException('Phân công không tồn tại.');
        exam_organization_editable($pdo,(int)$a['admission_period_id']);
        if($a['status']!=='assigned') throw new InvalidArgumentException('Phân công hiện tại không còn ở trạng thái được thay thế.');
        if((int)$a['staff_id']===$replacementStaffId) throw new InvalidArgumentException('Cán bộ thay thế phải khác cán bộ hiện tại.');

        $s=$pdo->prepare('SELECT id,active FROM staff WHERE id=:id LIMIT 1 FOR UPDATE');$s->execute(['id'=>$replacementStaffId]);$staff=$s->fetch();
        if(!$staff) throw new InvalidArgumentException('Cán bộ thay thế không tồn tại.');
        if((int)$staff['active']!==1) throw new InvalidArgumentException('Cán bộ thay thế không hoạt động.');

        $conflict=$pdo->prepare(
            'SELECT COUNT(*) FROM exam_invigilator_assignments x
             JOIN exam_subjects es ON es.id=x.exam_subject_id
             WHERE x.staff_id=:staff AND x.status="assigned"
               AND es.exam_date=:d AND es.start_time < :end_time AND es.end_time > :start_time
               AND x.id<>:id'
        );
        $conflict->execute(['staff'=>$replacementStaffId,'d'=>$a['exam_date'],'end_time'=>$a['end_time'],'start_time'=>$a['start_time'],'id'=>$assignmentId]);
        if((int)$conflict->fetchColumn()>0) throw new RuntimeException('Cán bộ thay thế bị xung đột thời gian.');

        $history=$pdo->prepare('INSERT INTO exam_invigilator_assignment_history(assignment_id,exam_room_id,exam_subject_id,staff_id,position,event_type) VALUES(:a,:r,:s,:staff,:pos,"replaced")');
        $history->execute(['a'=>$assignmentId,'r'=>$a['exam_room_id'],'s'=>$a['exam_subject_id'],'staff'=>$a['staff_id'],'pos'=>$a['position']]);
        $u=$pdo->prepare('UPDATE exam_invigilator_assignments SET staff_id=:staff,status="assigned",assigned_at=CURRENT_TIMESTAMP WHERE id=:id');
        $u->execute(['staff'=>$replacementStaffId,'id'=>$assignmentId]);
        $pdo->commit();
    } catch(Throwable $e) {
        if($pdo->inTransaction()) $pdo->rollBack(); throw $e;
    }
}

function exam_subject_context(PDO $pdo, int $periodId, int $subjectId): array {
    $q = $pdo->prepare('SELECT id,admission_period_id,name,subject_code,max_score,component_count FROM exam_subjects WHERE id=:s AND admission_period_id=:p LIMIT 1');
    $q->execute(['s' => $subjectId, 'p' => $periodId]);
    $row = $q->fetch();
    if (!$row) throw new InvalidArgumentException('Môn thi không thuộc kỳ thi đang xử lý.');
    return $row;
}

function generate_exam_papers(PDO $pdo, int $periodId, int $subjectId, int $roomId): int {
    $pdo->beginTransaction();
    try {
        grading_editable($pdo,$periodId);
        $subject = exam_subject_context($pdo, $periodId, $subjectId);
        $room = $pdo->prepare('SELECT r.id,r.exam_site_id FROM exam_rooms r JOIN exam_sites s ON s.id=r.exam_site_id WHERE r.id=:r AND s.admission_period_id=:p FOR UPDATE');
        $room->execute(['r' => $roomId, 'p' => $periodId]);
        $roomRow = $room->fetch();
        if (!$roomRow) throw new InvalidArgumentException('Phòng thi không thuộc kỳ thi.');

        $q = $pdo->prepare('SELECT ra.candidate_id FROM room_assignments ra
                            WHERE ra.admission_period_id=:p AND ra.exam_site_id=:site AND ra.exam_room_id=:room
                            ORDER BY ra.candidate_id FOR UPDATE');
        $q->execute(['p' => $periodId, 'site' => (int)$roomRow['exam_site_id'], 'room' => $roomId]);
        $find = $pdo->prepare('SELECT id,exam_room_id,paper_status,anonymous_code FROM exam_papers WHERE admission_period_id=:p AND candidate_id=:c AND exam_subject_id=:s FOR UPDATE');
        $insert = $pdo->prepare('INSERT INTO exam_papers(admission_period_id,candidate_id,exam_subject_id,exam_room_id) VALUES(:p,:c,:s,:r)');
        $created = 0;
        foreach ($q as $row) {
            $candidateId = (int)$row['candidate_id'];
            $find->execute(['p' => $periodId, 'c' => $candidateId, 's' => $subjectId]);
            $existing = $find->fetch();
            if ($existing) {
                if ((int)$existing['exam_room_id'] !== $roomId) {
                    throw new InvalidArgumentException('Bài thi đã tồn tại ở phòng khác; không tự động chuyển bài thi giữa các phòng.');
                }
                continue;
            }
            $insert->execute(['p' => $periodId, 'c' => $candidateId, 's' => $subjectId, 'r' => $roomId]);
            $created++;
        }
        $pdo->commit();
        return $created;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function paper_rows(PDO $pdo, int $periodId, int $subjectId, int $roomId): array {
    $number=exam_organization_schema($pdo) ? 'COALESCE(n.exam_number,c.candidate_code)' : 'c.candidate_code';
    $join=exam_organization_schema($pdo) ? ' LEFT JOIN exam_candidate_numbers n ON n.admission_period_id=p.admission_period_id AND n.candidate_id=p.candidate_id ' : '';
    $q = $pdo->prepare(
        'SELECT p.id,' . $number . ' AS candidate_code,c.full_name,p.candidate_id,p.exam_room_id,
                p.attendance_status,p.penalty_percent,p.anonymous_code,p.paper_status,
                es.subject_code,es.name AS subject_name,es.max_score,es.component_count
         FROM exam_papers p
         JOIN candidates c ON c.id=p.candidate_id' . $join . '
         JOIN exam_subjects es ON es.id=p.exam_subject_id AND es.admission_period_id=p.admission_period_id
         JOIN exam_rooms r ON r.id=p.exam_room_id
         JOIN exam_sites site ON site.id=r.exam_site_id AND site.admission_period_id=p.admission_period_id
         JOIN room_assignments ra ON ra.admission_period_id=p.admission_period_id
                                AND ra.candidate_id=p.candidate_id
                                AND ra.exam_site_id=site.id
                                AND ra.exam_room_id=p.exam_room_id
         WHERE p.admission_period_id=:p AND p.exam_subject_id=:s AND p.exam_room_id=:r
         ORDER BY ' . $number . ',c.id'
    );
    $q->execute(['p' => $periodId, 's' => $subjectId, 'r' => $roomId]);
    return $q->fetchAll();
}

function save_attendance(PDO $pdo, int $periodId, int $subjectId, int $roomId, array $attendance): void {
    $allowed = ['present', 'present_penalty', 'absent'];
    $penalties = [25, 50, 100];
    $rows = paper_rows($pdo, $periodId, $subjectId, $roomId);
    if (!$rows) throw new InvalidArgumentException('Chưa có bài thi cho phòng/môn được chọn.');
    $expected = array_fill_keys(array_map(static fn(array $r): int => (int)$r['id'], $rows), true);
    if (count($attendance) !== count($expected) || array_diff_key($expected, $attendance)) {
        throw new InvalidArgumentException('Phải cập nhật điểm danh cho toàn bộ danh sách bài thi.');
    }
    $pdo->beginTransaction();
    try {
        grading_editable($pdo,$periodId,$subjectId,'attendance');
        $locked=$pdo->prepare('SELECT id,attendance_status,penalty_percent,anonymous_code,paper_status FROM exam_papers WHERE admission_period_id=? AND exam_subject_id=? AND exam_room_id=? ORDER BY id FOR UPDATE');$locked->execute([$periodId,$subjectId,$roomId]);$existing=[];foreach($locked as $row)$existing[(int)$row['id']]=$row; $roomCoded=false;foreach($existing as $o){if($o['anonymous_code']!==null || in_array($o['paper_status'],['scored','matched'],true)){$roomCoded=true;break;}}
        $u = $pdo->prepare('UPDATE exam_papers SET attendance_status=:status,penalty_percent=:penalty
                            WHERE id=:id AND admission_period_id=:p AND exam_subject_id=:s AND exam_room_id=:r');
        foreach ($attendance as $paperId => $data) {
            $status = (string)($data['status'] ?? '');
            $penalty = isset($data['penalty']) && $data['penalty'] !== '' ? (int)$data['penalty'] : null;
            if (!in_array($status, $allowed, true)) throw new InvalidArgumentException('Trạng thái điểm danh không hợp lệ.');
            if ($status === 'present_penalty' && !in_array($penalty, $penalties, true)) throw new InvalidArgumentException('Mức trừ điểm không hợp lệ.');
            if ($status !== 'present_penalty') $penalty = null;
            if(period_configuration($pdo,$periodId)){
                $old=$existing[(int)$paperId] ?? null;
                if(!$old)throw new InvalidArgumentException('Bài thi đã thay đổi.');
                if($roomCoded && ($old['attendance_status']!==$status || (int)($old['penalty_percent'] ?? 0)!==(int)($penalty ?? 0)))throw new RuntimeException('Phòng/môn đã đánh phách; điểm danh và xử phạt đã được chốt, không thể thay đổi.');
            }
            $u->execute(['status' => $status, 'penalty' => $penalty, 'id' => (int)$paperId, 'p' => $periodId, 's' => $subjectId, 'r' => $roomId]);
            // rowCount() is not an ownership/existence check in MySQL: an UPDATE
            // that writes the same values may legitimately report 0 affected rows.
            // Membership was already validated against paper_rows()/expected above,
            // so do not reject an unchanged but valid paper as "không thuộc lựa chọn hiện tại".
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function preview_random_codes(PDO $pdo, int $periodId, int $subjectId, int $roomId, int $length = 6): array {
    if ($length < 1 || $length > 9) throw new InvalidArgumentException('[MISSING SPECIFICATION] Độ dài số phách chưa được quy định; độ dài kỹ thuật hiện tại phải từ 1 đến 9 chữ số.');
    $rows = paper_rows($pdo, $periodId, $subjectId, $roomId);
    if (!$rows) throw new InvalidArgumentException('Chưa có bài thi cho phòng/môn được chọn.');
    $space=10 ** $length;$needed=count(array_filter($rows,static fn($r)=>$r['attendance_status']!=='absent' && empty($r['anonymous_code'])));
    $count=$pdo->prepare('SELECT COUNT(*) FROM exam_papers WHERE admission_period_id=? AND anonymous_code REGEXP ?');$count->execute([$periodId,'^[0-9]{'.$length.'}$']);if($needed>$space-(int)$count->fetchColumn())throw new RuntimeException('Không đủ số phách với độ dài đã chọn.');
    $used = [];
    $check = $pdo->prepare('SELECT 1 FROM exam_papers WHERE admission_period_id=:p AND anonymous_code=:code LIMIT 1');
    foreach ($rows as $row) {
        if ($row['attendance_status'] === 'absent') continue;
        if(period_configuration($pdo,$periodId) && !in_array($row['attendance_status'],['present','present_penalty'],true))throw new RuntimeException('Cần điểm danh toàn bộ phòng trước khi đánh phách.');
        if(!empty($row['anonymous_code'])){$result[(int)$row['id']]=$row['anonymous_code'];continue;}
        $code = '';
        do {
            $code = str_pad((string)random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
            $check->execute(['p' => $periodId, 'code' => $code]);
        } while ($check->fetchColumn() || isset($used[$code]));
        $used[$code] = true;
        $result[(int)$row['id']] = $code;
    }
    return $result ?? [];
}

function save_codes(PDO $pdo, int $periodId, int $subjectId, int $roomId, array $codes): void {
    $rows = paper_rows($pdo, $periodId, $subjectId, $roomId);
    if (!$rows) throw new InvalidArgumentException('Chưa có bài thi cho phòng/môn được chọn.');
    $expected = [];
    foreach ($rows as $row) {
        if ($row['attendance_status'] !== 'absent') $expected[(int)$row['id']] = true;
    }
    if (array_diff_key($expected, $codes) || array_diff_key($codes, $expected)) throw new InvalidArgumentException('Phải cung cấp số phách cho toàn bộ bài thi có mặt.');
    $pdo->beginTransaction();
    try {
        grading_editable($pdo,$periodId,$subjectId,'codes');
        $seen = [];
        $check = $pdo->prepare('SELECT id FROM exam_papers WHERE admission_period_id=:p AND anonymous_code=:code AND id<>:id LIMIT 1');
        $q = $pdo->prepare('SELECT id,attendance_status,paper_status,anonymous_code FROM exam_papers WHERE id=:id AND admission_period_id=:p AND exam_subject_id=:s AND exam_room_id=:r FOR UPDATE');
        $u = $pdo->prepare('UPDATE exam_papers SET anonymous_code=:code,paper_status="coded" WHERE id=:id AND attendance_status<>"absent"');
        foreach ($codes as $paperId => $code) {
            $code = trim((string)$code);
            if ($code === '') throw new InvalidArgumentException('Số phách không được để trống.');
            if (isset($seen[$code])) throw new InvalidArgumentException('Số phách bị trùng.');
            $seen[$code] = true;
            $q->execute(['id' => (int)$paperId, 'p' => $periodId, 's' => $subjectId, 'r' => $roomId]);
            $paper = $q->fetch();
            if (!$paper || $paper['attendance_status'] === 'absent') throw new InvalidArgumentException('Bài thi không hợp lệ để đánh phách.');
            if(period_configuration($pdo,$periodId)){
                if(!in_array($paper['attendance_status'],['present','present_penalty'],true))throw new RuntimeException('Chưa điểm danh.');
                if($paper['anonymous_code']!==null){if((string)$paper['anonymous_code']!==$code)throw new RuntimeException('Đã cấp phách; không đánh lại số phách.');continue;}
                if($paper['paper_status']!=='waiting')throw new RuntimeException('Bài thi không còn được đánh phách.');
                if(!preg_match('/^[A-Za-z0-9]{1,30}$/',$code))throw new InvalidArgumentException('Phách gồm 1–30 chữ/số.');
            }

            $check->execute(['p' => $periodId, 'code' => $code, 'id' => (int)$paperId]);
            if ($check->fetchColumn()) throw new InvalidArgumentException('Số phách đã tồn tại trong kỳ thi.');
            $u->execute(['code' => $code, 'id' => (int)$paperId]);
            if ($u->rowCount() !== 1) throw new InvalidArgumentException('Không thể cập nhật số phách.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function generate_codes(PDO $pdo, int $periodId, int $subjectId, int $roomId, int $length = 6): array {
    $codes = preview_random_codes($pdo, $periodId, $subjectId, $roomId, $length);
    save_codes($pdo, $periodId, $subjectId, $roomId, $codes);
    return paper_rows($pdo, $periodId, $subjectId, $roomId);
}

function save_manual_codes(PDO $pdo, int $periodId, int $subjectId, int $roomId, array $codes): void {
    save_codes($pdo, $periodId, $subjectId, $roomId, $codes);
}

function validate_score_value(float $score, ?float $max): bool {
    return is_finite($score) && $score >= 0 && ($max === null || $score <= $max);
}

function validate_score_rows(PDO $pdo, int $periodId, int $subjectId, array $input): array {
    $subject = exam_subject_context($pdo, $periodId, $subjectId);
    $q = $pdo->prepare(
        'SELECT p.id,p.anonymous_code,p.paper_status,p.attendance_status,es.max_score,es.component_count
         FROM exam_papers p JOIN exam_subjects es ON es.id=p.exam_subject_id AND es.admission_period_id=p.admission_period_id
         JOIN exam_rooms r ON r.id=p.exam_room_id
         JOIN exam_sites site ON site.id=r.exam_site_id AND site.admission_period_id=p.admission_period_id
         JOIN room_assignments ra ON ra.admission_period_id=p.admission_period_id AND ra.candidate_id=p.candidate_id
                                        AND ra.exam_site_id=site.id AND ra.exam_room_id=p.exam_room_id
         WHERE p.admission_period_id=:p AND p.exam_subject_id=:s AND p.anonymous_code IS NOT NULL'
    );
    $q->execute(['p' => $periodId, 's' => $subjectId]);
    $papers = [];
    foreach ($q as $row) $papers[$row['anonymous_code']] = $row;

    $valid = []; $errors = []; $seen = [];
    foreach ($input as $code => $value) {
        if(is_array($value) && !array_filter($value,static fn($v)=>$v!=='' && $v!==null))continue;
        $code = trim((string)$code);
        if ($code === '') continue;
        if (isset($seen[$code])) { $errors[] = ['code' => $code, 'error' => 'Số phách bị trùng trong dữ liệu nhập.']; continue; }
        $seen[$code] = true;
        if (!isset($papers[$code])) { $errors[] = ['code' => $code, 'error' => 'Số phách không tồn tại hoặc không thuộc môn thi.']; continue; }
        $paper = $papers[$code];
        if ($paper['attendance_status'] === 'absent') { $errors[] = ['code' => $code, 'error' => 'Bài thi vắng mặt không được nhập điểm.']; continue; }
        if ($paper['paper_status'] !== 'coded') { $errors[] = ['code' => $code, 'error' => 'Bài thi chưa ở trạng thái đã đánh phách hoặc đã được nhập điểm.']; continue; }
        if ($subject['component_count'] > 2) { $errors[] = ['code' => $code, 'error' => '[MISSING SPECIFICATION] Schema hiện tại chỉ có score_1/score_2 cho môn nhiều phần.']; continue; }
        $data = is_array($value) ? $value : ['total_score' => $value];
        $has1 = array_key_exists('score_1', $data) && $data['score_1'] !== '';
        $has2 = array_key_exists('score_2', $data) && $data['score_2'] !== '';
        $hasTotal = array_key_exists('total_score', $data) && $data['total_score'] !== '';
        $s1 = $has1 && is_numeric($data['score_1']) ? (float)$data['score_1'] : null;
        $s2 = $has2 && is_numeric($data['score_2']) ? (float)$data['score_2'] : null;
        $total = $hasTotal && is_numeric($data['total_score']) ? (float)$data['total_score'] : null;
        if (($has1 && $s1 === null) || ($has2 && $s2 === null) || ($hasTotal && $total === null)) { $errors[] = ['code' => $code, 'error' => 'Điểm phải là số.']; continue; }
        if ($subject['component_count'] === 1) {
            if ($total === null && $s1 !== null) $total = $s1;
            if ($s1 === null && $total !== null) $s1 = $total;
            if ($total === null) { $errors[] = ['code' => $code, 'error' => 'Thiếu điểm.']; continue; }
        } else {
            if ($s1 === null || $s2 === null || $total === null) { $errors[] = ['code' => $code, 'error' => 'Môn có nhiều phần phải có đủ điểm thành phần và tổng điểm.']; continue; }
            if (abs(($s1 + $s2) - $total) > 0.0001) { $errors[] = ['code' => $code, 'error' => 'Tổng điểm không khớp tổng các điểm thành phần.']; continue; }
        }
        if ($s1 !== null && !validate_score_value($s1, (float)$paper['max_score'])) { $errors[] = ['code' => $code, 'error' => 'Điểm thành phần nằm ngoài thang điểm cho phép.']; continue; }
        if ($s2 !== null && !validate_score_value($s2, (float)$paper['max_score'])) { $errors[] = ['code' => $code, 'error' => 'Điểm thành phần nằm ngoài thang điểm cho phép.']; continue; }
        if (!validate_score_value((float)$total, (float)$paper['max_score'])) { $errors[] = ['code' => $code, 'error' => 'Tổng điểm nằm ngoài thang điểm cho phép.']; continue; }
        if(period_configuration($pdo,$periodId) && (!grading_score_valid($pdo,$periodId,(float)$total) || ($subject['component_count']===1 && ($s2!==null || abs((float)$s1-(float)$total)>0.00001)))){$errors[]=['code'=>$code,'error'=>'Điểm phải theo bước 0,25; môn một phần cần điểm thành phần khớp tổng và không có phần 2.'];continue;}
        $valid[] = ['paper_id' => (int)$paper['id'], 'code' => $code, 'score_1' => $s1, 'score_2' => $s2, 'total_score' => $total];
    }
    return ['valid' => $valid, 'errors' => $errors];
}

function match_scores_if_complete(PDO $pdo, int $periodId, int $subjectId): bool {
    $pdo->beginTransaction();
    try {
        $subject = exam_subject_context($pdo, $periodId, $subjectId);
        $q = $pdo->prepare('SELECT p.id,p.candidate_id FROM exam_papers p
                            WHERE p.admission_period_id=:p AND p.exam_subject_id=:s AND p.attendance_status IN ("present","present_penalty") FOR UPDATE');
        $q->execute(['p' => $periodId, 's' => $subjectId]);
        $papers = $q->fetchAll();
        if (!$papers) { $pdo->commit(); return false; }
        $check = $pdo->prepare('SELECT COUNT(*) FROM scores WHERE exam_paper_id=:id');
        $ids = [];
        foreach ($papers as $paper) { $check->execute(['id' => (int)$paper['id']]); if ((int)$check->fetchColumn() !== 1) { $pdo->commit(); return false; } $ids[] = (int)$paper['id']; }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $u = $pdo->prepare('UPDATE exam_papers SET paper_status="matched" WHERE admission_period_id=? AND exam_subject_id=? AND id IN (' . $in . ')');
        $u->execute(array_merge([$periodId, $subjectId], $ids));
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function save_scores(PDO $pdo, int $userId, int $periodId, int $subjectId, array $valid): void {
    if ($userId < 1) throw new InvalidArgumentException('Người nhập điểm không hợp lệ.');
    $pdo->beginTransaction();
    try {
        grading_editable($pdo,$periodId,$subjectId,'scores');
        $subject = exam_subject_context($pdo, $periodId, $subjectId);
        $input=[];foreach($valid as $row)$input[(string)$row['code']]=['score_1'=>$row['score_1'] ?? '', 'score_2'=>$row['score_2'] ?? '', 'total_score'=>$row['total_score'] ?? ''];
        $fresh=validate_score_rows($pdo,$periodId,$subjectId,$input);$validated=[];foreach($fresh['valid'] as $r)$validated[(string)$r['code']]=$r;
        if($fresh['errors'] || count($validated)!==count($valid))throw new InvalidArgumentException('Điểm không hợp lệ hoặc dữ liệu đã thay đổi.');
        $paperQ = $pdo->prepare('SELECT id,anonymous_code,paper_status,attendance_status FROM exam_papers WHERE id=:id AND admission_period_id=:p AND exam_subject_id=:s FOR UPDATE');
        $insert = $pdo->prepare('INSERT INTO scores(exam_paper_id,anonymous_code,score_1,score_2,total_score,entered_by) VALUES(:paper,:code,:s1,:s2,:total,:user)');
        $update = $pdo->prepare('UPDATE exam_papers SET paper_status="scored" WHERE id=:id AND admission_period_id=:p AND exam_subject_id=:s AND paper_status="coded"');
        foreach ($valid as $row) {
            $paperQ->execute(['id' => (int)$row['paper_id'], 'p' => $periodId, 's' => $subjectId]);
            $paper = $paperQ->fetch();
            if (!$paper || $paper['paper_status'] !== 'coded' || $paper['attendance_status'] === 'absent' || (string)$paper['anonymous_code'] !== (string)$row['code']) {
                throw new InvalidArgumentException('Bài thi không thuộc đúng kỳ/môn hoặc đã thay đổi trạng thái.');
            }
            if(!isset($validated[(string)$row['code']]) || (int)$validated[(string)$row['code']]['paper_id']!==(int)$row['paper_id'])throw new InvalidArgumentException('Điểm không hợp lệ hoặc dữ liệu đã thay đổi.');
            if(period_configuration($pdo,$periodId)){$g=$pdo->prepare('SELECT 1 FROM exam_paper_grader_assignments WHERE exam_paper_id=?');$g->execute([$paper['id']]);if(!$g->fetchColumn())throw new RuntimeException('Chưa phân công người chấm ban đầu.');}
            $insert->execute(['paper' => (int)$row['paper_id'], 'code' => (string)$row['code'], 's1' => $row['score_1'], 's2' => $row['score_2'], 'total' => (float)$row['total_score'], 'user' => $userId]);
            $update->execute(['id' => (int)$row['paper_id'], 'p' => $periodId, 's' => $subjectId]);
            if ($update->rowCount() !== 1) throw new InvalidArgumentException('Bài thi không còn ở trạng thái cho phép nhập điểm.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    match_scores_if_complete($pdo, $periodId, $subjectId);
}

function create_score_import_batch(PDO $pdo, int $userId, int $periodId, int $subjectId, string $fileName, array $parsedRows): int {
    if (!$parsedRows) throw new InvalidArgumentException('File không có dữ liệu.');
    $headers = array_map('excel_normalize_header', $parsedRows[0]);
    $allowedHeaders = ['anonymous_code', 'score_1', 'score_2', 'total_score'];
    if (count($headers) !== count(array_unique($headers))) throw new InvalidArgumentException('File có tên cột bị trùng.');
    foreach ($headers as $h) if (!in_array($h, $allowedHeaders, true)) throw new InvalidArgumentException('File có cột không đúng mẫu: ' . $h);
    if (!in_array('anonymous_code', $headers, true)) throw new InvalidArgumentException('File thiếu cột anonymous_code.');
    if (!in_array('total_score', $headers, true) && !in_array('score_1', $headers, true)) throw new InvalidArgumentException('File phải có total_score hoặc score_1.');
    $idx = array_flip($headers);
    $rowMeta = [];
    foreach (array_slice($parsedRows, 1) as $n => $row) {
        $line = $n + 2;
        if (count($row) > count($headers)) throw new InvalidArgumentException('Dòng ' . $line . ' có số cột nhiều hơn file mẫu.');
        $value = [];
        if (isset($idx['score_1'])) $value['score_1'] = $row[$idx['score_1']] ?? '';
        if (isset($idx['score_2'])) $value['score_2'] = $row[$idx['score_2']] ?? '';
        if (isset($idx['total_score'])) $value['total_score'] = $row[$idx['total_score']] ?? '';
        $rowMeta[] = ['line' => $line, 'code' => trim((string)($row[$idx['anonymous_code']] ?? '')), 'value' => $value];
    }
    $input=[];foreach($rowMeta as $m)if($m['code']!=='' && !array_key_exists($m['code'],$input))$input[$m['code']]=$m['value'];
    $all=validate_score_rows($pdo,$periodId,$subjectId,$input);$validByCode=[];$errorByCode=[];foreach($all['valid'] as $r)$validByCode[$r['code']]=$r;foreach($all['errors'] as $r)$errorByCode[$r['code']]=$r['error'];
    $validRows = []; $errors = []; $seenCodes = [];
    foreach ($rowMeta as $meta) {
        $code = $meta['code'];
        if ($code === '') { $errors[$meta['line']] = 'Số phách không được để trống.'; continue; }
        if (isset($seenCodes[$code])) { $errors[$meta['line']] = 'Số phách bị trùng trong cùng file.'; continue; }
        $seenCodes[$code] = true;
        if(isset($validByCode[$code]))$validRows[$meta['line']]=$validByCode[$code];
        else $errors[$meta['line']]=$errorByCode[$code] ?? 'Dòng không hợp lệ.';
    }

    $pdo->beginTransaction();
    try {
        grading_editable($pdo,$periodId,$subjectId,'scores');
        $b = $pdo->prepare('INSERT INTO exam_score_import_batches(user_id,admission_period_id,exam_subject_id,file_name,valid_rows,invalid_rows,status) VALUES(:u,:p,:s,:f,0,0,"previewed")');
        $b->execute(['u' => $userId, 'p' => $periodId, 's' => $subjectId, 'f' => basename($fileName)]);
        $batchId = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO exam_score_import_rows(batch_id,row_number,anonymous_code,score_1,score_2,total_score,error_message,is_valid) VALUES(:b,:n,:c,:s1,:s2,:t,:e,:v)');
        foreach ($rowMeta as $meta) {
            $line = $meta['line']; $v = $validRows[$line] ?? null; $err = $errors[$line] ?? null;
            $ins->execute(['b' => $batchId, 'n' => $line, 'c' => $meta['code'] ?: null, 's1' => $v['score_1'] ?? null, 's2' => $v['score_2'] ?? null, 't' => $v['total_score'] ?? null, 'e' => $err, 'v' => $v ? 1 : 0]);
        }
        $u = $pdo->prepare('UPDATE exam_score_import_batches SET valid_rows=:v,invalid_rows=:e WHERE id=:id');
        $u->execute(['v' => count($validRows), 'e' => count($errors), 'id' => $batchId]);
        $pdo->commit();
        return $batchId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function save_score_import_batch(PDO $pdo, int $batchId, int $userId): void {
    $pdo->beginTransaction();
    try {
        $b = $pdo->prepare('SELECT * FROM exam_score_import_batches WHERE id=:id AND user_id=:u AND status="previewed" FOR UPDATE');
        $b->execute(['id' => $batchId, 'u' => $userId]); $batch = $b->fetch();
        if (!$batch) throw new InvalidArgumentException('Batch nhập điểm không tồn tại hoặc đã được xử lý.');
        grading_editable($pdo,(int)$batch['admission_period_id'],(int)$batch['exam_subject_id'],'scores');
        $rows = $pdo->prepare('SELECT * FROM exam_score_import_rows WHERE batch_id=:b AND is_valid=1 ORDER BY row_number');
        $rows->execute(['b' => $batchId]); $valid = [];
        foreach ($rows as $r) $valid[] = ['paper_id' => null, 'code' => $r['anonymous_code'], 'score_1' => $r['score_1'] !== null ? (float)$r['score_1'] : null, 'score_2' => $r['score_2'] !== null ? (float)$r['score_2'] : null, 'total_score' => (float)$r['total_score']];
        if (!$valid) throw new InvalidArgumentException('Không có dữ liệu hợp lệ để lưu.');
        $lookup = $pdo->prepare('SELECT p.id,p.paper_status,p.admission_period_id,p.exam_subject_id FROM exam_papers p WHERE p.anonymous_code=:c AND p.admission_period_id=:p AND p.exam_subject_id=:s FOR UPDATE');
        foreach ($valid as &$row) { $lookup->execute(['c' => $row['code'], 'p' => $batch['admission_period_id'], 's' => $batch['exam_subject_id']]); $p = $lookup->fetch(); if (!$p || $p['paper_status'] !== 'coded') throw new InvalidArgumentException('Dữ liệu preview đã thay đổi, vui lòng tải lại file.'); $row['paper_id'] = (int)$p['id']; }
        unset($row);
        $input=[];foreach($valid as $row)$input[$row['code']]=['score_1'=>$row['score_1'] ?? '', 'score_2'=>$row['score_2'] ?? '', 'total_score'=>$row['total_score']];
        $check=validate_score_rows($pdo,(int)$batch['admission_period_id'],(int)$batch['exam_subject_id'],$input);if($check['errors'] || count($check['valid'])!==count($valid))throw new RuntimeException('Dữ liệu preview đã thay đổi; kiểm tra lại điểm danh và điểm.');
        if(period_configuration($pdo,(int)$batch['admission_period_id'])){$g=$pdo->prepare('SELECT 1 FROM exam_paper_grader_assignments WHERE exam_paper_id=?');foreach($valid as $row){$g->execute([$row['paper_id']]);if(!$g->fetchColumn())throw new RuntimeException('Chưa phân công người chấm ban đầu.');}}
        $insert = $pdo->prepare('INSERT INTO scores(exam_paper_id,anonymous_code,score_1,score_2,total_score,entered_by) VALUES(:p,:c,:s1,:s2,:t,:u)');
        $update = $pdo->prepare('UPDATE exam_papers SET paper_status="scored" WHERE id=:id AND paper_status="coded"');
        foreach ($valid as $row) { $insert->execute(['p' => $row['paper_id'], 'c' => $row['code'], 's1' => $row['score_1'], 's2' => $row['score_2'], 't' => $row['total_score'], 'u' => $userId]); $update->execute(['id' => $row['paper_id']]); if ($update->rowCount() !== 1) throw new InvalidArgumentException('Trạng thái bài thi đã thay đổi.'); }
        $u = $pdo->prepare('UPDATE exam_score_import_batches SET status="saved" WHERE id=:id'); $u->execute(['id' => $batchId]);
        $pdo->commit();
        match_scores_if_complete($pdo, (int)$batch['admission_period_id'], (int)$batch['exam_subject_id']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
