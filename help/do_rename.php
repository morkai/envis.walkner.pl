<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_POST['id']) || empty($_POST['title']));

no_access_if_not_allowed('help*');

exec_stmt('UPDATE help SET title=:title WHERE id=:id', array(':id' => $_POST['id'], ':title' => $_POST['title']));

output_json(true);
