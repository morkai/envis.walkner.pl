<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['parent']) || empty($_GET['type']));

no_access_if_not_allowed('service*');

if ($_GET['type'] === '1')
{
  $query = 'SELECT id AS value, name AS label FROM machines WHERE factory=? %s ORDER BY name';

  $objects = fetch_all(sprintf($query, get_allowed_machines('AND id IN(%s)')), array(1 => $_GET['parent']));
}
else
{
  no_access_if_not(has_access_to_machine($_GET['parent']));

  $objects = fetch_all('SELECT id AS value, name AS label FROM engines WHERE machine=? ORDER BY name', array(1 => $_GET['parent']));
}

output_json($objects);
