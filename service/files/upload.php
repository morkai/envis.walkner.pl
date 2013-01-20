<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_REQUEST['issue']) || !is_numeric($_REQUEST['issue']));

$file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim($_REQUEST['file'], '/\\');

bad_request_if(!file_exists($file));

$query = <<<SQL
SELECT i.id, i.owner, i.creator, i.relatedFactory, i.relatedMachine
FROM issues i
WHERE i.id=?
LIMIT 1
SQL;

$issue = fetch_one($query, array(1 => $_REQUEST['issue']));

not_found_if(empty($file));

$currentUser = $_SESSION['user'];

no_access_if_not(
  $currentUser->isSuper() ||
  is_issue_participant($currentUser, $issue) ||
  (!$issue->owner && is_allowed_to('service/edit')) ||
  !is_issue_docs_viewer($currentUser, $issue)
);

$dotPos = strrpos($_REQUEST['name'], '.');

exec_insert('issue_files', $bindings = array(
  'issue'        => $issue->id,
  'file'         => substr(strrchr($_REQUEST['file'], '/'), 1),
  'name'         => $dotPos === false ? $_REQUEST['name'] : substr($_REQUEST['name'], 0, $dotPos),
  'uploader'     => $currentUser->getId(),
  'uploadedAt'   => time()
));

$bindings['id']           = get_conn()->lastInsertId();
$bindings['uploadedAt']   = date('Y-m-d, H:i', $bindings['uploadedAt']);
$bindings['uploaderName'] = $currentUser->getName();

unset($bindings['issue'], $bindings['file']);

escape_vars($bindings['uploaderName'], $bindings['name']);

output_json($bindings);
