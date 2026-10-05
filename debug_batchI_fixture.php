<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/appeal_service.php';
require_once __DIR__ . '/includes/excel_service.php';

$pdo = db();
$tag = 'DEBUGI_' . date('YmdHis');

function run_step(PDO $pdo, string $name, string $sql, array $params = []): void {
    echo PHP_EOL . "=== $name ===" . PHP_EOL;
    echo $sql . PHP_EOL;
    try {
        $q = $pdo->prepare($sql);
        $q->execute($params);
        echo "PASS" . PHP_EOL;
        echo "lastInsertId=" . $pdo->lastInsertId() . PHP_EOL;
    } catch (Throwable $e) {
        echo "FAIL" . PHP_EOL;
        echo "SQLSTATE=" . $e->getCode() . PHP_EOL;
        echo "MESSAGE=" . $e->getMessage() . PHP_EOL;
        throw $e;
    }
}

try {
    $pdo->beginTransaction();

    run_step(
        $pdo,
        '1 admission_periods',
        'INSERT INTO admission_periods(name,academic_year,registration_start,registration_end,status)
         VALUES(:n,"2035","2035-01-01 00:00:00","2035-12-31 23:59:59","published")',
        ['n' => $tag . ' Period']
    );
    $period = (int)$pdo->lastInsertId();

    run_step(
        $pdo,
        '2 schools',
        'INSERT INTO schools(school_code,name,level,active,admission_area)
         VALUES(:c,:n,"THCS",1,"TEST")',
        ['c' => $tag . '_S', 'n' => $tag . ' School']
    );
    $school = (int)$pdo->lastInsertId();

    run_step(
        $pdo,
        '3 exam_subjects',
        'INSERT INTO exam_subjects(admission_period_id,subject_code,name,exam_date,start_time,end_time,max_score,component_count,status)
         VALUES(:p,:c,:n,"2035-06-01","08:00:00","10:00:00",10,1,"scheduled")',
        ['p' => $period, 'c' => $tag . '_M', 'n' => $tag . ' Subject']
    );
    $subject = (int)$pdo->lastInsertId();

    run_step(
        $pdo,
        '4 exam_sites',
        'INSERT INTO exam_sites(admission_period_id,school_id,name,address,status)
         VALUES(:p,:s,:n,"TEST","active")',
        ['p' => $period, 's' => $school, 'n' => $tag . ' Site']
    );
    $site = (int)$pdo->lastInsertId();

    run_step(
        $pdo,
        '5 exam_rooms',
        'INSERT INTO exam_rooms(exam_site_id,admission_period_id,room_code,capacity,status)
         VALUES(:s,:p,:c,30,"ready")',
        ['s' => $site, 'p' => $period, 'c' => $tag . '_R']
    );

    run_step(
        $pdo,
        '6 councils',
        'INSERT INTO councils(admission_period_id,council_type,name,status)
         VALUES(:p,"appeal",:n,"established")',
        ['p' => $period, 'n' => $tag . ' Appeal Council']
    );

    $pdo->rollBack();
    echo PHP_EOL . "ALL STEPS PASSED" . PHP_EOL;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo PHP_EOL . "STOPPED AT FIRST FAILURE" . PHP_EOL;
    exit(1);
}

