<?php

$bypassAuth = true;

include_once __DIR__ . '/../_common.php';

if (isset($_SESSION['user']))
{
  log_info('Wylogowano.');

  $_SESSION['user'] = null;
}

session_destroy();

go_to('user/login.php');
