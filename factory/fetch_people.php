<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['term']));

no_access_if_not_allowed('factory*');

$people = fetch_all(
  'SELECT id AS `value`, name AS `label` FROM users WHERE name LIKE CONCAT("%", ?, "%") ORDER BY name ASC', array(1 => $_GET['term'])
);

output_json($people);
