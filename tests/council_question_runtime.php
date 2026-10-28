<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(2);
require_once __DIR__.'/../includes/council_service.php';
$pdo=db();$checks=0;
function check_council(bool $ok,string $label): void {global $checks;if(!$ok)throw new RuntimeException($label);echo 'PASS ',++$checks,': ',$label,PHP_EOL;}
function reject_council(callable $action,string $message): void {
    try{$action();}catch(InvalidArgumentException $e){check_council(str_contains($e->getMessage(),$message),'Reject: '.$message);return;}
    throw new RuntimeException('Expected rejection: '.$message);
}
// Connection-local tables shadow application tables. No real records are changed.
foreach([
'admission_periods'=>'id INT PRIMARY KEY',
'staff'=>'id BIGINT UNSIGNED PRIMARY KEY,active INT',
'exam_subjects'=>'id INT PRIMARY KEY,admission_period_id INT',
'councils'=>'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,admission_period_id INT,council_type VARCHAR(30),name VARCHAR(200),exam_site_id INT NULL,start_at DATETIME NULL,end_at DATETIME NULL,location VARCHAR(255) NULL,status VARCHAR(30),UNIQUE KEY(admission_period_id,council_type,name)',
'council_subjects'=>'council_id BIGINT UNSIGNED,exam_subject_id INT,PRIMARY KEY(council_id,exam_subject_id)',
'council_members'=>'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,council_id BIGINT UNSIGNED,staff_id BIGINT UNSIGNED,role_name VARCHAR(100),task_name VARCHAR(255),UNIQUE KEY(council_id,staff_id)'
] as $table=>$ddl)$pdo->exec("CREATE TEMPORARY TABLE `$table` ($ddl) ENGINE=InnoDB");
$pdo->exec('INSERT INTO admission_periods VALUES(1)');
$pdo->exec('INSERT INTO staff VALUES(1,1),(2,1),(3,1),(4,0)');
$pdo->exec('INSERT INTO exam_subjects VALUES(1,1),(2,1),(3,2)');
$base=['admission_period_id'=>1,'council_type'=>'question','name'=>'Hội đồng ra đề mô phỏng TP.HCM','exam_subject_ids'=>[1],'members'=>[['staff_id'=>1,'role_name'=>'Ra đề','task_name'=>'Soạn đề mô phỏng'],['staff_id'=>2,'role_name'=>'phản biện'],['staff_id'=>3,'role_name'=>'thẩm định']],'start_at'=>'2030-01-01T08:00','end_at'=>'2030-01-01T10:00'];
$id=council_create($pdo,$base);
check_council($id>0,'Save council');
check_council((int)$pdo->query('SELECT COUNT(*) FROM council_members')->fetchColumn()===3,'Save all members');
check_council($pdo->query('SELECT role_name FROM council_members ORDER BY id LIMIT 1')->fetchColumn()==='ra đề','Normalize role case');
check_council($pdo->query('SELECT start_at FROM councils LIMIT 1')->fetchColumn()==='2030-01-01 08:00:00','Normalize datetime');
reject_council(fn()=>council_create($pdo,$base),'đã tồn tại');
$base['name']='Hội đồng kiểm tra';
foreach([
['exam_subject_ids',[], 'gắn với môn'],['exam_subject_ids',[1,2],'đúng một môn'],['exam_subject_ids',[3],'không thuộc kỳ'],['exam_site_id',1,'Chỉ hội đồng coi thi'],
['admission_period_id',9,'không tồn tại'],['name',str_repeat('a',201),'200 ký tự'],['location',str_repeat('a',256),'255 ký tự'],
['start_at','2030-02-30T08:00','Ngày giờ'],['end_at','2030-01-01T08:00','phải sau'],['end_at','','cả thời gian'],
['members',[],'ít nhất một'],['members',[['staff_id'=>1,'role_name'=>'ra đề'],['staff_id'=>1,'role_name'=>'phản biện']],'trùng thành viên'],
['members',[['staff_id'=>4,'role_name'=>'ra đề']],'không hoạt động'],['members',[['staff_id'=>99,'role_name'=>'ra đề']],'không tồn tại'],
['members',[['staff_id'=>1,'role_name'=>'']],'phân công vai trò'],['members',[['staff_id'=>1,'role_name'=>'khác']],'Vai trò'],
['members',[['staff_id'=>1,'role_name'=>'ra đề','task_name'=>str_repeat('a',256)]],'255 ký tự']
] as [$key,$value,$message]){$data=$base;$data[$key]=$value;reject_council(fn()=>council_create($pdo,$data),$message);check_council(!$pdo->inTransaction(),'Transaction closed after rejection');}
check_council((int)$pdo->query('SELECT COUNT(*) FROM councils')->fetchColumn()===1,'Failures do not create partial councils');
// Force a late insert failure after council and subject inserts, and verify rollback.
$pdo->exec('DROP TEMPORARY TABLE council_members');
$pdo->exec('CREATE TEMPORARY TABLE council_members(id INT PRIMARY KEY) ENGINE=InnoDB');
try{council_create($pdo,$base);throw new RuntimeException('Expected late failure');}catch(PDOException $e){check_council(!$pdo->inTransaction(),'Late database failure rolls back');}
check_council((int)$pdo->query('SELECT COUNT(*) FROM councils')->fetchColumn()===1,'Council insert rolled back');
check_council((int)$pdo->query('SELECT COUNT(*) FROM council_subjects')->fetchColumn()===1,'Subject insert rolled back');
echo "Passed $checks checks on connection-local temporary tables.\n";
