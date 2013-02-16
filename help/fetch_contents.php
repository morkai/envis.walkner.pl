<?php

$bypassAuth = true;

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

$page = help_fetch_page($id);

not_found_if(empty($page));

help_render_page($page);
