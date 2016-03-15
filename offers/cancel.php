<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT o.cancelled
FROM offers o
WHERE o.id=?
LIMIT 1
SQL;

$offer = fetch_one($query, array(1 => $_GET['id']));

bad_request_if(empty($offer));

exec_update('offers', array('updatedAt' => time(), 'cancelled' => $offer->cancelled ? 0 : 1), "id={$_GET['id']}");

go_to(get_referer());
