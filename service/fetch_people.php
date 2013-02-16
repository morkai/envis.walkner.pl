<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['term']));

no_access_if_not_allowed('service*');

$people = array_values(fetch_array(
  'SELECT id AS `key`, name AS `value` FROM users WHERE name LIKE CONCAT("%", ?, "%") ORDER BY name ASC', array(1 => $_GET['term'])
));

output_json($people);
