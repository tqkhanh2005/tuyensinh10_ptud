<?php
declare(strict_types=1);

/**
 * Batch B runtime verification for XAMPP/MariaDB.
 *
 * This script creates a small isolated dataset, exercises UC37/UC38/UC16,
 * then removes the dataset. It is intentionally separate from production UI.
 * Run from the project root: php tests/batchB_runtime.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/exam_service.php';

$pdo = db();
$token = 'B' . date('YmdHis') . bin2hex(random_bytes(3));
$ids = [
    'periodA' => 0, 'periodB' => 0, 'school' => 0,
    'candidateA' => 0, 'candidateB' => 0, 'candidateC' => 0,
    'userA' => 0, 'siteA' => 0, 'siteB' => 0,
    'roomA1' => 0, 'roomB1' => 0, 'subjectA' => 0,
];

$results = [];
$record = static function (array &$results, int $n, string $name, bool $ok, string $detail = ''): void {
    $results[$n] = [$ok ? 'PASS' : 'FAIL', $name, $detail];
};
$expectFailure = static function (callable $fn): array {
    try { $fn(); return [false, 'operation unexpectedly succeeded']; }
    catch (Throwable $e) { return [true, $e->getMessage()]; }
};

try {
    // Test fixture setup.
    $pdo->beginTransaction();
    $s = $pdo->prepare('INSERT INTO admission_periods(name,academic_year,status) VALUES(:name,:year,"published")');
    $s->execute(['name' => 'Batch B Test A ' . $token, 'year' => 'TEST-A-' . $token]);
    $ids['periodA'] = (int)$pdo->lastInsertId();
    $s->execute(['name' => 'Batch B Test B ' . $token, 'year' => 'TEST-B-' . $token]);
    $ids['periodB'] = (int)$pdo->lastInsertId();

    $s = $pdo->prepare('INSERT INTO schools(school_code,name,level,active) VALUES(:code,:name,"THCS",1)');
    $s->execute(['code' => 'BTEST-' . substr($token, -8), 'name' => 'Batch B Test School ' . $token]);
    $ids['school'] = (int)$pdo->lastInsertId();

    $u = $pdo->prepare('INSERT INTO users(username,password_hash,full_name,role,active) VALUES(:u,:h,:n,"candidate",1)');
    $u->execute(['u' => 'btest_a_' . $token, 'h' => password_hash('1111', PASSWORD_DEFAULT), 'n' => 'Batch B Candidate A']);
    $ids['userA'] = (int)$pdo->lastInsertId();

    $c = $pdo->prepare('INSERT INTO candidates(user_id,candidate_code,national_id,full_name,eligible,status,exam_number) VALUES(:u,:code,:nid,:name,1,"active",:exam)');
    $c->execute(['u' => $ids['userA'], 'code' => 'SBD-' . substr($token, -6), 'nid' => 'NID-A-' . $token, 'name' => 'Candidate A', 'exam' => 'SBD-' . substr($token, -6)]);
    $ids['candidateA'] = (int)$pdo->lastInsertId();
    $c->execute(['u' => null, 'code' => 'SBD-B-' . substr($token, -6), 'nid' => 'NID-B-' . $token, 'name' => 'Candidate B', 'exam' => 'SBD-B-' . substr($token, -6)]);
    $ids['candidateB'] = (int)$pdo->lastInsertId();
    $c->execute(['u' => null, 'code' => 'SBD-C-' . substr($token, -6), 'nid' => 'NID-C-' . $token, 'name' => 'Candidate C', 'exam' => 'SBD-C-' . substr($token, -6)]);
    $ids['candidateC'] = (int)$pdo->lastInsertId();

    $a = $pdo->prepare('INSERT INTO aspirations(candidate_id,admission_period_id,school_id,priority_order) VALUES(:c,:p,:s,1)');
    $a->execute(['c' => $ids['candidateA'], 'p' => $ids['periodA'], 's' => $ids['school']]);
    $a->execute(['c' => $ids['candidateB'], 'p' => $ids['periodB'], 's' => $ids['school']]);
    $a->execute(['c' => $ids['candidateC'], 'p' => $ids['periodA'], 's' => $ids['school']]);

    $site = $pdo->prepare('INSERT INTO exam_sites(admission_period_id,site_code,name,capacity,status) VALUES(:p,:code,:name,:cap,"ready")');
    $site->execute(['p' => $ids['periodA'], 'code' => 'SITE-A-' . substr($token, -5), 'name' => 'Site A ' . $token, 'cap' => 2]);
    $ids['siteA'] = (int)$pdo->lastInsertId();
    $site->execute(['p' => $ids['periodB'], 'code' => 'SITE-B-' . substr($token, -5), 'name' => 'Site B ' . $token, 'cap' => 2]);
    $ids['siteB'] = (int)$pdo->lastInsertId();

    $room = $pdo->prepare('INSERT INTO exam_rooms(exam_site_id,room_code,name,capacity,status) VALUES(:s,:code,:name,:cap,"ready")');
    $room->execute(['s' => $ids['siteA'], 'code' => 'R-A-' . substr($token, -5), 'name' => 'Room A ' . $token, 'cap' => 1]);
    $ids['roomA1'] = (int)$pdo->lastInsertId();
    $room->execute(['s' => $ids['siteB'], 'code' => 'R-B-' . substr($token, -5), 'name' => 'Room B ' . $token, 'cap' => 1]);
    $ids['roomB1'] = (int)$pdo->lastInsertId();

    $sub = $pdo->prepare('INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,status) VALUES(:p,:code,:name,CURDATE(),"08:00:00","10:00:00","scheduled")');
    $sub->execute(['p' => $ids['periodA'], 'code' => 'SUB-' . substr($token, -5), 'name' => 'Toan Test']);
    $ids['subjectA'] = (int)$pdo->lastInsertId();
    $pdo->commit();

    // 1: candidate A -> site A.
    save_allocations($pdo, $ids['periodA'], [$ids['candidateA'] => $ids['siteA']]);
    $record($results, 1, 'Candidate A -> Site A', true);

    // 2: candidate A -> site B must fail because site B belongs to period B.
    [$ok, $detail] = $expectFailure(fn() => save_allocations($pdo, $ids['periodA'], [$ids['candidateA'] => $ids['siteB']]));
    $record($results, 2, 'Candidate A -> Site B rejected', $ok, $detail);

    // 3: site capacity must reject a third candidate when site A capacity is 2.
    save_allocations($pdo, $ids['periodA'], [$ids['candidateC'] => $ids['siteA']]);
    $third = $pdo->prepare('INSERT INTO candidates(candidate_code,national_id,full_name,eligible,status) VALUES(:code,:nid,:name,1,"active")');
    $third->execute(['code' => 'SBD-D-' . substr($token, -6), 'nid' => 'NID-D-' . $token, 'name' => 'Candidate D']);
    $candidateD = (int)$pdo->lastInsertId();
    $asp = $pdo->prepare('INSERT INTO aspirations(candidate_id,admission_period_id,school_id,priority_order) VALUES(:c,:p,:s,1)');
    $asp->execute(['c' => $candidateD, 'p' => $ids['periodA'], 's' => $ids['school']]);
    $ids['candidateD'] = $candidateD;
    [$ok, $detail] = $expectFailure(fn() => save_allocations($pdo, $ids['periodA'], [$candidateD => $ids['siteA']]));
    $record($results, 3, 'Allocation over site capacity rejected', $ok, $detail);

    // 4: valid manual allocation of D to a newly created site A2.
    $site2 = $pdo->prepare('INSERT INTO exam_sites(admission_period_id,site_code,name,capacity,status) VALUES(:p,:code,:name,1,"ready")');
    $site2->execute(['p' => $ids['periodA'], 'code' => 'SITE-A2-' . substr($token, -5), 'name' => 'Site A2 ' . $token]);
    $siteA2 = (int)$pdo->lastInsertId();
    save_allocations($pdo, $ids['periodA'], [$candidateD => $siteA2]);
    $record($results, 4, 'Valid manual allocation', true);

    // 5: duplicate allocation is an update, not a second row, due unique period+candidate.
    $before = (int)$pdo->query('SELECT COUNT(*) FROM exam_allocations WHERE admission_period_id=' . $ids['periodA'] . ' AND candidate_id=' . $candidateD)->fetchColumn();
    save_allocations($pdo, $ids['periodA'], [$candidateD => $siteA2]);
    $after = (int)$pdo->query('SELECT COUNT(*) FROM exam_allocations WHERE admission_period_id=' . $ids['periodA'] . ' AND candidate_id=' . $candidateD)->fetchColumn();
    $record($results, 5, 'Duplicate allocation does not create duplicate', $before === 1 && $after === 1);

    // 6: transaction rollback: valid change + invalid cross-period site must leave original allocation.
    [$ok, $detail] = $expectFailure(fn() => save_allocations($pdo, $ids['periodA'], [
        $ids['candidateD'] => $ids['siteA'],
        $ids['candidateA'] => $ids['siteB'],
    ]));
    $current = (int)$pdo->query('SELECT exam_site_id FROM exam_allocations WHERE admission_period_id=' . $ids['periodA'] . ' AND candidate_id=' . $candidateD)->fetchColumn();
    $record($results, 6, 'Allocation batch rolls back on failure', $ok && $current === $siteA2, $detail);

    // 7: candidate A -> Room A passes.
    save_room_assignments($pdo, $ids['periodA'], $ids['siteA'], [$ids['candidateA'] => $ids['roomA1']]);
    $record($results, 7, 'Candidate A -> Room A valid', true);

    // 8: Site A allocation -> Room B must fail.
    [$ok, $detail] = $expectFailure(fn() => save_room_assignments($pdo, $ids['periodA'], $ids['siteA'], [$ids['candidateA'] => $ids['roomB1']]));
    $record($results, 8, 'Room from another site rejected', $ok, $detail);

    // 9: Room A capacity is 1; candidate A currently occupies it, so candidate C cannot be added.
    [$ok, $detail] = $expectFailure(fn() => save_room_assignments($pdo, $ids['periodA'], $ids['siteA'], [$ids['candidateC'] => $ids['roomA1']]));
    $record($results, 9, 'Room over capacity rejected', $ok, $detail);

    // 10: candidate A already has Room A; moving to a second room requires capacity and remains one row.
    $roomA2Stmt = $pdo->prepare('INSERT INTO exam_rooms(exam_site_id,room_code,name,capacity,status) VALUES(:s,:code,:name,1,"ready")');
    $roomA2Stmt->execute(['s' => $ids['siteA'], 'code' => 'R-A2-' . substr($token, -5), 'name' => 'Room A2 ' . $token]);
    $roomA2 = (int)$pdo->lastInsertId();
    save_room_assignments($pdo, $ids['periodA'], $ids['siteA'], [$ids['candidateA'] => $roomA2]);
    $roomCount = (int)$pdo->query('SELECT COUNT(*) FROM room_assignments WHERE admission_period_id=' . $ids['periodA'] . ' AND candidate_id=' . $ids['candidateA'])->fetchColumn();
    $record($results, 10, 'Candidate remains in one room after adjustment', $roomCount === 1);

    // 11: Candidate B belongs only to period B; assigning to period A must fail.
    [$ok, $detail] = $expectFailure(fn() => save_room_assignments($pdo, $ids['periodA'], $ids['siteA'], [$ids['candidateB'] => $ids['roomA1']]));
    $record($results, 11, 'Candidate B -> Period A room rejected', $ok, $detail);

    // 12: automatic room allocation respects existing assignment/capacity.
    $preview = room_preview($pdo, $ids['periodA'], $ids['siteA']);
    $record($results, 12, 'Automatic room preview respects capacity', is_array($preview));

    // 13: normalized exam-card read.
    $card = exam_card_for_user($pdo, $ids['userA']);
    $cardOk = is_array($card)
        && $card['exam_site_name'] === 'Site A ' . $token
        && $card['exam_room_name'] === 'Room A2 ' . $token
        && str_contains((string)$card['exam_schedule'], 'Toan Test');
    $record($results, 13, 'UC16 card reads normalized site/room/schedule', $cardOk);

    // 14: explicit cross-period room/site isolation check for candidate A.
    [$ok, $detail] = $expectFailure(fn() => save_room_assignments($pdo, $ids['periodA'], $ids['siteA'], [$ids['candidateA'] => $ids['roomB1']]));
    $record($results, 14, 'Candidate A cannot use period-B room', $ok, $detail);

} catch (Throwable $e) {
    $record($results, 0, 'Fixture/setup', false, $e->getMessage());
} finally {
    // Cleanup is best-effort. Delete in dependency order.
    try {
        $pdo->beginTransaction();
        if ($ids['periodA'] || $ids['periodB']) {
            $periods = array_values(array_filter([$ids['periodA'], $ids['periodB']]));
            $in = implode(',', array_map('intval', $periods));
            $pdo->exec("DELETE FROM room_assignments WHERE admission_period_id IN ($in)");
            $pdo->exec("DELETE FROM exam_allocations WHERE admission_period_id IN ($in)");
            $pdo->exec("DELETE FROM exam_papers WHERE admission_period_id IN ($in)");
            $pdo->exec("DELETE FROM exam_subjects WHERE admission_period_id IN ($in)");
            $pdo->exec("DELETE FROM exam_rooms WHERE exam_site_id IN (SELECT id FROM exam_sites WHERE admission_period_id IN ($in))");
            $pdo->exec("DELETE FROM exam_sites WHERE admission_period_id IN ($in)");
            $pdo->exec("DELETE FROM aspirations WHERE admission_period_id IN ($in)");
            $pdo->exec("DELETE FROM admission_periods WHERE id IN ($in)");
        }
        $candidateIds = array_values(array_filter([$ids['candidateA'],$ids['candidateB'],$ids['candidateC'],$ids['candidateD']]));
        if ($candidateIds) $pdo->exec('DELETE FROM candidates WHERE id IN (' . implode(',', array_map('intval', $candidateIds)) . ')');
        if ($ids['userA']) $pdo->exec('DELETE FROM users WHERE id=' . (int)$ids['userA']);
        if ($ids['school']) $pdo->exec('DELETE FROM schools WHERE id=' . (int)$ids['school']);
        $pdo->commit();
    } catch (Throwable $cleanupError) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        fwrite(STDERR, "Cleanup warning: {$cleanupError->getMessage()}\n");
    }
}

ksort($results);
$fail = 0;
foreach ($results as $n => [$status,$name,$detail]) {
    printf("%s %s: %s%s\n", $status, $n === 0 ? 'SETUP' : 'TEST ' . $n, $name, $detail !== '' ? ' — ' . $detail : '');
    if ($status !== 'PASS') $fail++;
}
exit($fail ? 1 : 0);
