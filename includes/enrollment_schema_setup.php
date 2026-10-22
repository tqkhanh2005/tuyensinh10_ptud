<?php
declare(strict_types=1);
/** CLI setup only. Older confirmations retain their original date and have no invented actor/receipt. */
function enrollment_schema_apply(PDO $pdo): void {
    if(PHP_SAPI!=='cli')throw new RuntimeException('Chỉ cập nhật cấu trúc bằng công cụ dòng lệnh.');
    $columns=$pdo->query('SHOW COLUMNS FROM enrollment_confirmations')->fetchAll(PDO::FETCH_COLUMN);
    $new=['admission_period_id','actor_user_id','receipt_code'];$existing=array_intersect($new,$columns);
    if(!$existing)$pdo->exec((string)file_get_contents(__DIR__.'/../database/019_enrollment_confirmation.sql'));
    elseif(count($existing)!==count($new))throw new RuntimeException('Cấu trúc nhập học chưa đầy đủ; cần kiểm tra lần cập nhật trước.');
    $indexes=array_column($pdo->query('SHOW INDEX FROM enrollment_confirmations')->fetchAll(), 'Key_name');
    foreach(['uq_enrollment_period_candidate','uq_enrollment_receipt'] as $index)if(!in_array($index,$indexes,true))throw new RuntimeException('Thiếu khóa xác nhận theo kỳ hoặc mã biên nhận.');
    $pdo->exec('UPDATE enrollment_confirmations ec JOIN admission_results ar ON ar.id=ec.admission_result_id SET ec.admission_period_id=ar.admission_period_id WHERE ec.admission_period_id IS NULL');
    if($pdo->query('SELECT COUNT(*) FROM enrollment_confirmations ec JOIN admission_results ar ON ar.id=ec.admission_result_id WHERE ec.admission_period_id IS NULL OR ec.admission_period_id<>ar.admission_period_id OR ec.candidate_id<>ar.candidate_id OR ec.school_id<>ar.school_id')->fetchColumn())throw new RuntimeException('Bản ghi xác nhận không khớp kết quả gốc.');
}
