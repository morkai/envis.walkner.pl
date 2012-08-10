<?php

$bypassAuth = true;

include './_common.php';

if (empty($_GET['device']) || empty($_GET['machine'])) bad_request();

$query = <<<SQL
SELECT `value`
FROM `values`
WHERE variable=:variable
  AND machine=:machine
  AND `engine`=:device
ORDER BY createdAt DESC
LIMIT 1
SQL;

$counter = fetch_one(
	$query,
	$bindings = array(
		':variable' => get_counter_var(),
		':machine'  => $_GET['machine'],
		':device'   => $_GET['device'],
	)
);

output_json(empty($counter) ? 0 : (int)$counter->value);

?>