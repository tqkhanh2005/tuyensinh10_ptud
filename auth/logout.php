<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
auth_logout();
header('Location: '.url('/auth/login'));
exit;
