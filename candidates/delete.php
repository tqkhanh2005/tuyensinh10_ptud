<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/candidate_service.php';
require_permission('candidate.delete');if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method Not Allowed');}require_csrf();$id=(int)($_POST['id']??0);
try{candidate_deactivate($id);header('Location: '.url('/candidates/index.php'));exit;}catch(Throwable $e){http_response_code(409);exit($e->getMessage());}
