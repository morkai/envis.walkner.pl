<?php

include '../_common.php';

if (empty($_GET['machine']) || empty($_GET['since'])) bad_request();

no_access_if_not(is_allowed_to('vis/machine'), has_access_to_machine($_GET['machine']));

$result = array('data' => array());

$rows = fetch_all('SELECT v.device, v.variable FROM vis_machine_device_variables v INNER JOIN engines e ON e.machine=v.machine AND e.id=v.device WHERE e.machine=:machine', array(':machine' => $_GET['machine']));

$devices = array();

foreach ($rows as $row)
{
	if (!isset($devices[$row->device]))
	{
		$devices[$row->device] = array();
	}

	$devices[$row->device][] = $row->variable;
}

$varStmt = prepare_stmt('
SELECT
	var.name,
	var.id,
	(SELECT val.value FROM `values` val WHERE val.variable=dv.variable AND val.machine=:machine AND val.engine=:device ORDER BY val.id DESC LIMIT 1) AS value
FROM vis_machine_device_variables dv
INNER JOIN variables var ON var.id=dv.variable
WHERE dv.machine=:machine
  AND dv.device=:device
ORDER BY var.name ASC
');

foreach ($devices as $device => $variables)
{
	$result['data'][prep_js_id($device)] = fetch_all($varStmt, array(':machine' => $_GET['machine'], ':device' => $device));
}

echo json_encode($result + array('lastUpdateTime' => time()));