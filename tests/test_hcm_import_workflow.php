<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
date_default_timezone_set('Asia/Ho_Chi_Minh');
$options=getopt('', ['session-dir:','report:','base-url:']);
$sessionDirectory=$options['session-dir']??'';
if(!is_dir($sessionDirectory)) throw new RuntimeException('Supply a dedicated session/temp directory.');
ini_set('session.save_path',$sessionDirectory);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/candidate_import_service.php';
require_once __DIR__.'/../includes/import_school_setup.php';
require_once __DIR__.'/../includes/test_data_cleanup_service.php';
$pdo=db(); $checks=[]; $temporary=[];
$assert=function(bool $condition,string $name) use(&$checks):void {
    if(!$condition) throw new RuntimeException('FAIL: '.$name);
    $checks[]=$name; echo 'PASS: '.$name,PHP_EOL;
};
$expectException=function(callable $call,string $name) use($assert):void {
    $caught=false;
    try{$call();}catch(Throwable $e){$caught=true;}
    $assert($caught,$name);
};
$csv=function(array $rows,?array $header=null) use(&$temporary,$sessionDirectory):string {
    $path=$sessionDirectory.'/qa_'.bin2hex(random_bytes(6)).'.csv';
    $handle=fopen($path,'wb'); fwrite($handle,"\xEF\xBB\xBF");
    fputcsv($handle,$header??CANDIDATE_IMPORT_HEADERS,',','"','');
    foreach($rows as $row) fputcsv($handle,array_map(static fn($key)=>$row[$key]??'',CANDIDATE_IMPORT_HEADERS),',','"','');
    fclose($handle); $temporary[]=$path; return $path;
};
$before=cleanup_snapshot($pdo);
$assert(count($before['rows']['candidates'])===0,'Begin from cleaned student database');
$schools=import_school_setup($pdo);
$files=[]; $allIds=[]; $raw=[];
foreach(json_decode((string)file_get_contents(__DIR__.'/../data/import/schools_hcm.json'),true) as $school){
    $file=__DIR__.'/../data/import/'.$school['slug'].'_360_hoc_sinh.csv';
    $rows=candidate_import_parse_csv($file);
    $assert(count($rows)===360,$school['school_code'].': 360 CSV rows');
    $classes=[];
    foreach($rows as $row){
        if(isset($allIds[$row['national_id']])) throw new RuntimeException('Duplicate national ID across files');
        $allIds[$row['national_id']]=true;
        if(!preg_match('/^079[23]11[0-9]{6}$/',$row['national_id']) || !preg_match('/^0[0-9]{9}$/',$row['phone'])) throw new RuntimeException('Identifier or phone format mismatch');
        if(!preg_match('/^Lớp (9A[1-9]),/u',$row['address'],$match) || !str_contains($row['address'],'TP.HCM')) throw new RuntimeException('Class or city mismatch');
        $classes[$match[1]]=($classes[$match[1]]??0)+1;
    }
    $assert(count($classes)===9 && array_values(array_unique(array_values($classes)))===[40],$school['school_code'].': 9 classes of 40');
    $validation=candidate_import_validate_batch($pdo,$rows);
    $assert($validation['can_import'] && $validation['valid_rows']===360,$school['school_code'].': all rows validate against live school codes');
    $files[]=['file'=>$file,'school'=>$school,'rows'=>$rows]; $raw=array_merge($raw,$rows);
}
$assert(count($allIds)===720,'720 distinct identifiers across the two schools');
$first=$raw[0];
foreach(['date_of_birth'=>'2011-02-30','full_name'=>'','phone'=>'abc','email'=>'not-an-email','middle_school_code'=>'TRUONG_KHONG_TON_TAI','eligible'=>'2','status'=>'unknown'] as $field=>$value){
    $bad=$first; $bad[$field]=$value;
    $result=candidate_import_validate_batch($pdo,candidate_import_parse_csv($csv([$bad])));
    $assert(!$result['can_import'] && in_array($field,array_column($result['errors'],'column'),true),'Reject invalid '.$field);
}
$duplicate=candidate_import_validate_batch($pdo,candidate_import_parse_csv($csv([$first,$first])));
$assert(!$duplicate['can_import'] && $duplicate['error_rows']===1,'Reject duplicate identifiers within one file');
$expectException(fn()=>candidate_import_parse_csv($csv([$first],['wrong_header'])),'Reject wrong header layout');
$empty=$sessionDirectory.'/empty.csv'; file_put_contents($empty,''); $temporary[]=$empty;
$expectException(fn()=>candidate_import_parse_csv($empty),'Reject empty file');
$expectException(fn()=>candidate_import_parse_csv($csv([])),'Reject header-only file');
$wrongColumns=$sessionDirectory.'/wrong_columns.csv'; file_put_contents($wrongColumns,implode(',',CANDIDATE_IMPORT_HEADERS)."\n1,2,3\n");$temporary[]=$wrongColumns;
$expectException(fn()=>candidate_import_parse_csv($wrongColumns),'Reject row with missing columns');
$quoted=$csv([$first]); $quotedText=file_get_contents($quoted); $quotedText=str_replace('national_id,','"national_id",',$quotedText); file_put_contents($quoted,$quotedText);
$assert(candidate_import_parse_csv($quoted)[0]['national_id']===$first['national_id'],'Accept UTF-8 BOM with quoted first header');
$invalidUtf8=$first; $invalidUtf8['full_name']="\xC3\x28";
$expectException(fn()=>candidate_import_parse_csv($csv([$invalidUtf8])),'Reject invalid UTF-8 text before database writes');
$formula=$first; $formula['full_name']='=1+1';
$assert(candidate_import_parse_csv($csv([$formula]))[0]['full_name']==="'=1+1",'Escape spreadsheet formula injection');
$expectException(fn()=>candidate_import_parse_csv($csv(array_fill(0,5001,$first))),'Enforce the 5000-row limit');
$assert((int)$pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn()===0,'All validation failures leave student database unchanged');

// Exercise the real upload -> preview -> confirm controller using a short-lived local test session.
$admin=$pdo->query('SELECT id,username,full_name FROM users WHERE id=2')->fetch();
$roleStmt=$pdo->prepare('SELECT r.code FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=? AND r.active=1'); $roleStmt->execute([2]);
$_SESSION['auth']=['user_id'=>2,'id'=>2,'username'=>$admin['username'],'full_name'=>$admin['full_name'],'role'=>'so_gdtd','roles'=>$roleStmt->fetchAll(PDO::FETCH_COLUMN)];
$_SESSION['csrf_token']=bin2hex(random_bytes(32)); $csrf=$_SESSION['csrf_token']; $sessionId=session_id(); session_write_close();
$base=$options['base-url']??'http://127.0.0.1:4012';
if(!preg_match('#^http://127\.0\.0\.1:[0-9]+$#',$base)) throw new RuntimeException('HTTP workflow checks are restricted to the local test server.');
$request=function(?array $post=null) use($base,$sessionId):array {
    $curl=curl_init($base.'/candidates/import.php');
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_COOKIE=>'PHPSESSID='.$sessionId,CURLOPT_TIMEOUT=>60,CURLOPT_FOLLOWLOCATION=>false]);
    if($post!==null) curl_setopt($curl,CURLOPT_POSTFIELDS,$post);
    $response=curl_exec($curl);
    if($response===false) throw new RuntimeException(curl_error($curl));
    $status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE); $headerSize=(int)curl_getinfo($curl,CURLINFO_HEADER_SIZE);curl_close($curl);
    return ['status'=>$status,'headers'=>substr($response,0,$headerSize),'body'=>substr($response,$headerSize)];
};
$response=$request(); $assert($response['status']===200,'Authorized admin opens actual import controller');
$response=$request(['action'=>'upload','csrf_token'=>$csrf,'import_file'=>new CURLFile($wrongColumns,'text/csv','wrong.csv')]);
$assert($response['status']===200 && !str_contains($response['body'],'name="confirm_token"'),'Controller blocks malformed CSV without a confirm form');
foreach($files as $entry){
    $upload=$request(['action'=>'upload','csrf_token'=>$csrf,'import_file'=>new CURLFile($entry['file'],'text/csv',basename($entry['file']))]);
    $batchFound=preg_match('/name="batch_id"\s+value="([a-f0-9]+)"/',$upload['body'],$batch);
    $tokenFound=preg_match('/name="confirm_token"\s+value="([a-f0-9]+)"/',$upload['body'],$confirm);
    $assert($upload['status']===200 && $batchFound===1 && $tokenFound===1,$entry['school']['school_code'].': preview permits a valid batch');
    $saved=$request(['action'=>'confirm','csrf_token'=>$csrf,'batch_id'=>$batch[1],'confirm_token'=>$confirm[1]]);
    $assert($saved['status']===302 && str_contains($saved['headers'],'imported=360'),$entry['school']['school_code'].': controller commits all 360 students');
    $replay=$request(['action'=>'confirm','csrf_token'=>$csrf,'batch_id'=>$batch[1],'confirm_token'=>$confirm[1]]);
    $assert($replay['status']===200 && !str_contains($replay['headers'],'imported='),$entry['school']['school_code'].': consumed confirmation cannot be replayed');
}
$stmt=$pdo->query('SELECT c.*,s.school_code,u.username,u.full_name AS account_name,u.password_hash,u.active AS account_active FROM candidates c JOIN schools s ON s.id=c.middle_school_id JOIN users u ON u.id=c.user_id ORDER BY c.national_id');
$saved=[]; foreach($stmt->fetchAll() as $row) $saved[$row['national_id']]=$row;
$assert(count($saved)===720,'Exactly 720 saved student profiles with linked accounts');
$perSchool=[]; $perClass=[];
foreach($raw as $row){
    $actual=$saved[$row['national_id']]??[];
    foreach(['full_name','date_of_birth','gender','address','phone','email','status'] as $field) if(($actual[$field]??null)!==$row[$field]) throw new RuntimeException('Round-trip mismatch: '.$field);
    if($actual['username']!==$row['national_id'] || $actual['account_name']!==$row['full_name'] || $actual['school_code']!==$row['middle_school_code'] || (int)$actual['eligible']!==(int)$row['eligible'] || (int)$actual['account_active']!==1) throw new RuntimeException('Account or school linkage mismatch');
    $perSchool[$actual['school_code']]=($perSchool[$actual['school_code']]??0)+1;
    preg_match('/^Lớp (9A[1-9]),/u',$actual['address'],$class);
    $key=$actual['school_code'].'/'.$class[1];$perClass[$key]=($perClass[$key]??0)+1;
}
$assert(count($perSchool)===2 && array_values(array_unique(array_values($perSchool)))===[360],'Database has 360 students per school');
$assert(count($perClass)===18 && array_values(array_unique(array_values($perClass)))===[40],'Database retains all 18 class labels, 40 students each');
$assert((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()===721,'Exactly 720 student accounts plus the retained admin');
$assert((int)$pdo->query('SELECT COUNT(*) FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.code="candidate"')->fetchColumn()===720,'All 720 accounts have the candidate role');
$assert(password_verify('1111',$saved[$first['national_id']]['password_hash']),'Imported account uses the application default password');
$repeat=candidate_import_validate_batch($pdo,candidate_import_parse_csv($files[0]['file']));
$assert(!$repeat['can_import'] && $repeat['error_rows']===360,'Reimporting an existing school reports 360 duplicate rows');
$repeatHttp=$request(['action'=>'upload','csrf_token'=>$csrf,'import_file'=>new CURLFile($files[0]['file'],'text/csv','duplicate.csv')]);
$assert($repeatHttp['status']===200 && !str_contains($repeatHttp['body'],'name="confirm_token"'),'HTTP preview blocks duplicate reimport');
$fresh=$repeat['normalized_rows'][0]; $fresh['national_id']='079211999990'; $fresh['full_name']='Nguyễn Minh An'; $fresh['is_valid']=true;
$collision=$repeat['normalized_rows'][0]; $collision['is_valid']=true;
$expectException(fn()=>candidate_import_execute($pdo,[$fresh,$collision]),'A mid-batch database collision rolls back the whole import');
$assert((int)$pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn()===720 && (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()===721,'Rollback leaves no partial student or account');
$assert((int)$pdo->query("SELECT COUNT(*) FROM users WHERE username='079211999990'")->fetchColumn()===0,'Rollback leaves no orphan account for the first row');
$after=cleanup_snapshot($pdo);
foreach(CLEANUP_PROTECTED_TABLES as $table) if(isset($before['rows'][$table])) $assert($before['rows'][$table]===$after['rows'][$table],'Preserve '.$table);
$assert($before['rows']['users'][0]===$pdo->query('SELECT * FROM users WHERE id=2')->fetch(),'Admin account remains byte-for-byte unchanged');
foreach($before['rows']['schools'] as $school) {
    $find=$pdo->prepare('SELECT * FROM schools WHERE id=?');$find->execute([$school['id']]);
    $assert($find->fetch()===$school,'Preserve existing school '.$school['school_code']);
}
$assert(array_sum(cleanup_plan($after)['counts'])===0,'No old batch-test markers remain after import');
$orphanCount=0;
foreach($after['foreign'] as $foreign){
    $join=[];$nonnull=[];
    foreach($foreign['columns'] as $column=>$reference){$join[]="c.`$column`=p.`$reference`";$nonnull[]="c.`$column` IS NOT NULL";}
    $firstReference=array_values($foreign['columns'])[0];
    $orphanCount+=(int)$pdo->query('SELECT COUNT(*) FROM `'.$foreign['child'].'` c LEFT JOIN `'.$foreign['parent'].'` p ON '.implode(' AND ',$join).' WHERE '.implode(' AND ',$nonnull)." AND p.`$firstReference` IS NULL")->fetchColumn();
}
$assert($orphanCount===0,'All database foreign-key links remain intact');
$report=['completed_at'=>date(DATE_ATOM),'checks_passed'=>count($checks),'checks'=>$checks,'students'=>720,'accounts_created'=>720,'per_school'=>$perSchool,'per_class'=>$perClass,'default_student_password'=>'1111','school_setup'=>$schools,'orphan_accounts_created'=>false];
if(isset($options['report'])) file_put_contents($options['report'],json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
foreach($temporary as $file) if(is_file($file)) unlink($file);
if(is_file($sessionDirectory.'/sess_'.$sessionId)) unlink($sessionDirectory.'/sess_'.$sessionId);
echo 'COMPLETE: ',count($checks),' checks passed; 720 students imported.',PHP_EOL;
