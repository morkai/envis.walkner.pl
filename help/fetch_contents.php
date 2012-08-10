<?php

$bypassAuth = true;

include './_common.php';

if (empty($_GET['id'])) bad_request();

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

$page = help_fetch_page($id);

if (empty($page)) not_found();

help_render_page($page);