<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(2);
require_once __DIR__.'/../includes/db.php';
$directory=realpath($argv[1]??'');$root=strtolower(str_replace('\\','/',dirname(__DIR__))).'/';
if(!$directory || str_starts_with(strtolower(str_replace('\\','/',$directory)).'/',$root))throw new RuntimeException('Private session directory required.');
$pdo=db();$accounts=[];$queries=[
    'admin'=>'SELECT u.* FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.active=1 AND r.active=1 AND r.code IN ("admin","so_gdtd") ORDER BY u.id LIMIT 1',
    'teacher'=>'SELECT u.* FROM users u JOIN staff s ON s.user_id=u.id JOIN schools sc ON sc.id=s.school_id WHERE u.active=1 AND s.active=1 AND sc.active=1 ORDER BY u.id LIMIT 1',
    'student'=>'SELECT u.* FROM users u JOIN candidates c ON c.user_id=u.id WHERE u.active=1 ORDER BY c.id LIMIT 1'];
foreach($queries as $role=>$sql){$u=$pdo->query($sql)->fetch();if(!$u)throw new RuntimeException('Missing role fixture: '.$role);$q=$pdo->prepare('SELECT r.code FROM user_roles ur JOIN roles r ON r.id=ur.role_id AND r.active=1 WHERE ur.user_id=?');$q->execute([$u['id']]);$roles=$q->fetchAll(PDO::FETCH_COLUMN);$id=bin2hex(random_bytes(24));$data=['auth'=>['user_id'=>(int)$u['id'],'id'=>(int)$u['id'],'username'=>$u['username'],'full_name'=>$u['full_name'],'roles'=>$roles,'role'=>$roles[0]],'csrf_token'=>bin2hex(random_bytes(24))];$text='';foreach($data as $key=>$value)$text.=$key.'|'.serialize($value);file_put_contents($directory.'/sess_'.$id,$text);$accounts[$role]=$id;}
echo json_encode($accounts,JSON_THROW_ON_ERROR);
