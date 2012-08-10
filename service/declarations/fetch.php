<?php

include '../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('service/declare');

$template = fetch_one('SELECT * FROM declaration_templates WHERE id=? LIMIT 1', array(1 => $_GET['id']));

not_found_if(empty($template));

output_json($template);

?>
