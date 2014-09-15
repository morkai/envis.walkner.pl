<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

exec_update('offers', array('cancelled' => 1), "id={$_GET['id']}");

go_to(get_referer());
