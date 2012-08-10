<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$entry = fetch_one('SELECT h.comment, u.name AS author FROM issue_history h INNER JOIN users u ON u.id=h.createdBy WHERE h.id=?', array(1 => $_GET['id']));

not_found_if(empty($entry));

output_json($entry->author . " napisał:\n> " . str_replace("\n", "\n> ", $entry->comment));
