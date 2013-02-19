<?php

if (!empty($_GET['docs']))
{
  $bypassAuth = true;
}

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT f.id, f.issue, f.file, f.name, i.owner, i.creator, i.relatedFactory, i.relatedMachine
FROM issue_files f
INNER JOIN issues i ON i.id=f.issue
WHERE f.id=?
LIMIT 1
SQL;

$file = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($file));

if (empty($_GET['docs']))
{
  $currentUser = $_SESSION['user'];

  no_access_if_not($currentUser->isSuper() || is_issue_participant($currentUser, $file) || (!$file->owner && is_allowed_to('service/edit')) || is_issue_docs_viewer($currentUser, $file));
}

$file->path = ENVIS_UPLOADS_PATH . '/issues/' . $file->file;

if (!file_exists($file->path))
{
  exec_stmt('DELETE FROM issue_files WHERE id=?', array(1 => $file->id));

  not_found();
}

$mimetypes = include __DIR__ . '/../../_lib_/mimetypes.php';

$extension = substr(strrchr($file->file, '.'), 1);
$contentType = array_search($extension, $mimetypes, true);

if ($contentType === false)
{
  $contentType = 'application/octet-stream';
}

setlocale(LC_CTYPE, 'pl_PL.utf8');

$filename = preg_replace('/[^a-zA-Z_\-\.\']+/', '', str_replace(' ', '-', @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file->name))) . ($extension === '' ? '' : ('.' . $extension));

header(sprintf('Content-Type: %s', $contentType));
header(sprintf('Content-Disposition: inline; filename="%s"', $filename));

readfile($file->path);
