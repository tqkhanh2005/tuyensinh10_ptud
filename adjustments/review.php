<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/candidate_service.php';
require_permission('adjustment.review');if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method Not Allowed');}require_csrf();$u=auth_user();$id=(int)($_POST['id']??0);$approve=($_POST['action']??'')==='approve';$reason=trim((string)($_POST['reason']??''));
try{adjustment_review($id,(int)$u['id'],$approve,$reason);header('Location: '.url('/adjustments/index.php?saved=1'));exit;}catch(InvalidArgumentException $e){http_response_code(422);exit(e($e->getMessage()));}catch(Throwable $e){http_response_code(409);exit('Không thể xử lý yêu cầu.');}
