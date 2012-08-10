<?php

include '../../_common.php';

if (empty($_GET['template'])) bad_request();

no_access_if_not_allowed('service/templates*');

output_json(fetch_all('SELECT id, summary FROM issue_template_tasks WHERE template=? ORDER BY summary', array(1 => $_GET['template'])));