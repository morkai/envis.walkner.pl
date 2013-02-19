<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT f.id, f.issue, f.uploader, f.file, f.name, i.owner, i.creator
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

$file->path = ENVIS_UPLOADS_PATH . '/issues/' . $file->file;

$referer = get_referer("service/view.php?id={$file->issue}#files");

$conn = get_conn();

try
{
  $conn->beginTransaction();

  exec_stmt('DELETE FROM issue_files WHERE id=?', array(1 => $file->id));

  if (file_exists($file->path))
  {
    unlink($file->path);
  }

  $conn->commit();

  if (!is_ajax())
  {
    set_flash(sprintf('Plik <%s> został usunięty.', $file->name));
    go_to($referer);
  }
}
catch (PDOException $x)
{
  $conn->rollBack();

  bad_request_if(is_ajax());

  set_flash($x->getMessage(), 'error');
  go_to($referer);
}
