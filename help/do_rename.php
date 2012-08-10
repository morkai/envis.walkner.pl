<?php

include './_common.php';

if (empty($_POST['id']) || empty($_POST['title'])) bad_request();

no_access_if_not_allowed('help*');

exec_stmt('UPDATE help SET title=:title WHERE id=:id', array(':id' => $_POST['id'], ':title' => $_POST['title']));

output_json(true);