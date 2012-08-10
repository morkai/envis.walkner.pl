<?php

$bypassAuth = true;

include '../_common.php';

if (isset($_SESSION['user']))
{
	log_info('Wylogowano.');
	
	$_SESSION['user'] = null;
}

session_destroy();

go_to('user/login.php');