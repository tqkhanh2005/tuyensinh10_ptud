<?php
declare(strict_types=1);

const CLEANUP_PROTECTED_TABLES = ['permissions','roles','role_permissions','schema_migrations'];
const CLEANUP_ROOT_TABLES = ['users','schools','candidates','staff','admission_periods'];

function cleanup_snapshot(PDO $pdo, bool $lock = false): array
{
    $tables=$pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
    $primary=[]; $rows=[];
    $keys=$pdo->query("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND CONSTRAINT_NAME='PRIMARY' ORDER BY TABLE_NAME,ORDINAL_POSITION")->fetchAll();
    foreach($keys as $key) $primary[$key['TABLE_NAME']][]=$key['COLUMN_NAME'];
    foreach($tables as $table) {
        if (!preg_match('/^[A-Za-z0-9_]+$/',$table)) throw new RuntimeException('Unexpected table name');
        $order=isset($primary[$table]) ? ' ORDER BY '.implode(',',array_map(static fn($k)=>"`$k`",$primary[$table])) : '';
        $rows[$table]=$pdo->query("SELECT * FROM `$table`".$order.($lock?' FOR UPDATE':''))->fetchAll();
        if ($rows[$table] && !isset($primary[$table])) throw new RuntimeException("Non-empty table $table has no primary key; refusing ambiguous cleanup.");
    }
    $foreign=[];
    $keys=$pdo->query("SELECT TABLE_NAME,CONSTRAINT_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION")->fetchAll();
    foreach($keys as $key) {
        $id=$key['TABLE_NAME'].'.'.$key['CONSTRAINT_NAME'];
        $foreign[$id]['child']=$key['TABLE_NAME']; $foreign[$id]['parent']=$key['REFERENCED_TABLE_NAME'];
        $foreign[$id]['columns'][$key['COLUMN_NAME']]=$key['REFERENCED_COLUMN_NAME'];
    }
    return ['database'=>$pdo->query('SELECT DATABASE()')->fetchColumn(),'rows'=>$rows,'primary'=>$primary,'foreign'=>array_values($foreign)];
}

function cleanup_fingerprints(array $snapshot): array
{
    $hashes=[];
    foreach($snapshot['rows'] as $table=>$rows) $hashes[$table]=hash('sha256',serialize($rows));
    return $hashes;
}

function cleanup_key(array $row, array $primary): string
{
    return json_encode(array_map(static fn($key)=>(string)$row[$key],$primary),JSON_THROW_ON_ERROR);
}

function cleanup_test_marker(string $value): bool
{
    return (bool)preg_match('/^(?:BATCH[A-Z0-9_ -]*|BTEST[-_]|G1_|TEST[-_])|(?:^|\s)Batch(?:\s|$)|(?:^|\s)Test(?:\s|$)/iu',$value);
}

function cleanup_plan(array $snapshot): array
{
    $rows=$snapshot['rows']; $primary=$snapshot['primary']; $selected=[]; $protectedUsers=[2=>true];
    $adminRoles=[];
    foreach($rows['roles'] as $role) if(in_array($role['code'],['admin','so_gdtd'],true)) $adminRoles[(int)$role['id']]=true;
    $usersById=[];
    foreach($rows['users'] as $user) $usersById[(int)$user['id']]=$user;
    foreach($rows['user_roles'] as $role) if(isset($adminRoles[(int)$role['role_id']])) {
        $user=$usersById[(int)$role['user_id']];
        if(!cleanup_test_marker((string)$user['username']) && !cleanup_test_marker((string)($user['full_name']??''))) $protectedUsers[(int)$role['user_id']]=true;
    }
    foreach($rows['users'] as $user) if(in_array($user['role'],['admin','so_gdtd'],true) && !cleanup_test_marker((string)$user['username']) && !cleanup_test_marker((string)($user['full_name']??''))) $protectedUsers[(int)$user['id']]=true;
    foreach(CLEANUP_ROOT_TABLES as $table) {
        foreach($rows[$table] as $row) {
            $test=false;
            switch($table) {
                case 'users':
                    $test=cleanup_test_marker((string)$row['username']) || cleanup_test_marker((string)($row['full_name']??'')) || in_array($row['username'],['candidate_test','teacher_thcs_test','teacher_thpt_test'],true);
                    if(isset($protectedUsers[(int)$row['id']])) $test=false;
                    break;
                case 'schools':
                    $test=cleanup_test_marker((string)$row['school_code']) || in_array($row['school_code'],['THCS_TEST_A','THCS_TEST_B'],true);
                    break;
                case 'candidates':
                    $test=cleanup_test_marker((string)$row['national_id']) || cleanup_test_marker((string)$row['full_name']) || (bool)preg_match('/^NID-[A-C]-B[0-9a-f]+$/i',(string)$row['national_id']);
                    break;
                case 'staff':
                    $test=cleanup_test_marker((string)$row['full_name']) || $row['staff_role']==='TEST' || cleanup_test_marker((string)$row['workplace']);
                    break;
                case 'admission_periods':
                    $test=cleanup_test_marker((string)$row['name']);
            }
            if($test) $selected[$table][cleanup_key($row,$primary[$table])]=true;
        }
    }
    // Propagate only to dependent records. A genuine person or school is never inferred to be test data.
    do {
        $changed=false;
        foreach($snapshot['foreign'] as $foreign) {
            $child=$foreign['child']; $parent=$foreign['parent'];
            if(in_array($child,CLEANUP_PROTECTED_TABLES,true) || in_array($child,CLEANUP_ROOT_TABLES,true) || empty($selected[$parent])) continue;
            $parentValues=[];
            foreach($rows[$parent] as $row) if(isset($selected[$parent][cleanup_key($row,$primary[$parent])])) {
                $parentValues[json_encode(array_map(static fn($column)=>(string)$row[$column],array_values($foreign['columns'])))]=true;
            }
            foreach($rows[$child] as $row) {
                $values=[]; $nullable=false;
                foreach($foreign['columns'] as $column=>$reference) { if($row[$column]===null) $nullable=true; $values[]=(string)$row[$column]; }
                $key=cleanup_key($row,$primary[$child]);
                if(!$nullable && isset($parentValues[json_encode($values)]) && !isset($selected[$child][$key])) {
                    $selected[$child][$key]=true; $changed=true;
                }
            }
        }
    } while($changed);
    // Every reference to a selected parent must be included, including composite foreign keys.
    foreach($snapshot['foreign'] as $foreign) {
        $child=$foreign['child']; $parent=$foreign['parent'];
        if(empty($selected[$parent])) continue;
        foreach($rows[$child] as $childRow) {
            if(isset($selected[$child][cleanup_key($childRow,$primary[$child])])) continue;
            foreach($rows[$parent] as $parentRow) {
                if(!isset($selected[$parent][cleanup_key($parentRow,$primary[$parent])])) continue;
                $match=true;
                foreach($foreign['columns'] as $column=>$reference) if($childRow[$column]===null || (string)$childRow[$column] !== (string)$parentRow[$reference]) $match=false;
                if($match) throw new RuntimeException("Preserved $child record references test $parent; manual classification is required before deletion.");
            }
        }
    }
    $order=[]; $visiting=[]; $done=[];
    $visit=function(string $table) use (&$visit,&$order,&$visiting,&$done,$snapshot,$selected):void {
        if(isset($done[$table])) return;
        if(isset($visiting[$table])) throw new RuntimeException('Selected tables have a foreign-key cycle; refusing unsafe deletion.');
        $visiting[$table]=true;
        foreach($snapshot['foreign'] as $fk) if($fk['parent']===$table && !empty($selected[$fk['child']])) $visit($fk['child']);
        unset($visiting[$table]); $done[$table]=true; $order[]=$table;
    };
    // Test publication events must be removed first: score-delete triggers enforce their lock.
    // This never disables a trigger or removes publication records outside the selected test graph.
    if(!empty($selected['publication_events'])) $visit('publication_events');
    foreach(array_keys($selected) as $table) $visit($table);
    return ['selected'=>$selected,'order'=>$order,'protected_users'=>array_keys($protectedUsers),'counts'=>array_map('count',$selected)];
}

function cleanup_execute(PDO $pdo, array $backupMetadata): array
{
    if($pdo->inTransaction()) throw new RuntimeException('Cleanup requires its own transaction.');
    $pdo->beginTransaction();
    try {
        $before=cleanup_snapshot($pdo,true);
        if(($backupMetadata['database']??null)!==$before['database'] || ($backupMetadata['fingerprints']??[])!==cleanup_fingerprints($before)) throw new RuntimeException('Database has changed since the verified backup. Take and verify a new backup.');
        $plan=cleanup_plan($before);
        foreach($plan['order'] as $table) {
            if(in_array($table,CLEANUP_PROTECTED_TABLES,true)) throw new RuntimeException('Protected table selected');
            $where=implode(' AND ',array_map(static fn($column)=>"`$column`=?",$before['primary'][$table]));
            $delete=$pdo->prepare("DELETE FROM `$table` WHERE $where");
            foreach($before['rows'][$table] as $row) if(isset($plan['selected'][$table][cleanup_key($row,$before['primary'][$table])])) {
                $delete->execute(array_map(static fn($column)=>$row[$column],$before['primary'][$table]));
                if($delete->rowCount()!==1) throw new RuntimeException("Unexpected deletion count in $table");
            }
        }
        $after=cleanup_snapshot($pdo);
        foreach($before['rows'] as $table=>$rows) {
            $expected=array_values(array_filter($rows,static fn($row)=>!isset($plan['selected'][$table][cleanup_key($row,$before['primary'][$table])])));
            if($expected!==$after['rows'][$table]) throw new RuntimeException("Unexpected change to preserved records in $table");
        }
        $pdo->commit();
        return ['deleted'=>$plan['counts'],'total_deleted'=>array_sum($plan['counts']),'protected_user_ids'=>$plan['protected_users'],'remaining_counts'=>array_map('count',$after['rows'])];
    } catch(Throwable $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
