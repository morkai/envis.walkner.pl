<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT f.id, f.issue, i.uploader, i.owner
FROM issue_files f
INNER JOIN issues i ON i.id=f.issue
WHERE f.id=?
LIMIT 1
SQL;

$file = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($file));

$currentUser = $_SESSION['user'];

$participant = $file->owner == $currentUser->getId() || $file->uploader == $currentUser->getId();

no_access_if_not($currentUser->isSuper() || $participant || (!$file->owner && is_allowed_to('service/edit')));

$referer = get_referer("service/view.php?id={$file->issue}#files");

try
{
  exec_update('issue_files', array('name' => $_REQUEST['name']), 'id=' . $file->id);

  if (!is_ajax())
  {
    set_flash(sprintf('Plik <%s> został zmieniony.', $file->name));
    go_to($referer);
  }
}
catch (PDOException $x)
{
  if (is_ajax())
  {
    bad_request();
  }
  else
  {
    set_flash($x->getMessage(), 'error');
    go_to($referer);
  }
}
