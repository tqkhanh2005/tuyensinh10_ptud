<?php
declare(strict_types=1);
ini_set('session.save_path',sys_get_temp_dir());
require_once __DIR__.'/../includes/publication_service.php';
// Exercise service guards with a PDO double; never connect to production data.
class PublicationGuardStatement extends PDOStatement {
    public function __construct(private mixed $value){}
    public function execute(?array $params=null): bool{return true;}
    public function fetchColumn(int $column=0): mixed{return $this->value;}
}
class PublicationGuardPDO extends PDO {
    public bool $transaction=false;public int $rollbacks=0;public int $commits=0;
    public function __construct(private string $scenario){}
    public function prepare(string $query,array $options=[]): PDOStatement|false {
        if(str_contains($query,'FROM users u'))return new PublicationGuardStatement($this->scenario!=='unauthorized');
        if(str_contains($query,'SELECT id FROM admission_periods'))return new PublicationGuardStatement($this->scenario!=='missing_period');
        if(str_contains($query,'FROM publication_events'))return new PublicationGuardStatement(true);
        throw new RuntimeException('Unexpected query in guard test: '.$query);
    }
    public function beginTransaction(): bool{$this->transaction=true;return true;}
    public function inTransaction(): bool{return $this->transaction;}
    public function rollBack(): bool{$this->transaction=false;$this->rollbacks++;return true;}
    public function commit(): bool{$this->transaction=false;$this->commits++;return true;}
}
$checks=0;
foreach(['unauthorized'=>'Không có quyền','missing_period'=>'không tồn tại','already_published'=>'đã được công bố'] as $scenario=>$message){
    $pdo=new PublicationGuardPDO($scenario);$caught=false;
    try{publish_admission_results($pdo,1,1,10);}catch(RuntimeException $e){$caught=str_contains($e->getMessage(),$message);}
    if(!$caught)throw new RuntimeException('Expected rejection: '.$scenario);$checks++;
    if($pdo->inTransaction() || $pdo->commits!==0 || $pdo->rollbacks!==($scenario==='unauthorized'?0:1))throw new RuntimeException('Incorrect rollback: '.$scenario);$checks++;
}
echo "Passed $checks admission publication guard checks (PDO double; no database writes).\n";
