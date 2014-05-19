<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('service/delete*');

$issue = fetch_one('SELECT id, owner, creator, subject, relatedFactory, relatedMachine FROM issues WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($issue));

if (!$_SESSION['user']->isSuper())
{
  $canDeleteAll = is_allowed_to('service/delete/all');
  $isOwner = $_SESSION['user']->getId() == $issue->owner
               || $_SESSION['user']->getId() == $issue->creator;

  no_access_if(!$isOwner && !$canDeleteAll);

  if ($issue->relatedFactory === null)
    no_access_if_not($canDeleteAll);
  else
    no_access_if_not(has_access_to_factory($issue->relatedFactory));

  if ($issue->relatedMachine !== null)
    no_access_if_not(has_access_to_machine($issue->relatedMachine));
}

if (count($_POST))
{
  $files = fetch_all('SELECT file FROM issue_files WHERE issue=?', array(1 => $issue->id));

  foreach ($files as $file)
  {
    unlink(ENVIS_UPLOADS_PATH . '/issues/' . $file->file);
  }

  exec_stmt('DELETE FROM issues WHERE id=?', array(1 => $issue->id));

  send_issue_removal_email($issue);

  log_info('Usunięto zgłoszenie <%s>.', $issue->subject);

  set_flash(sprintf('Zgłoszenie <%s> zostało usunięte pomyślnie.', $issue->subject));

  go_to('service/');
}

$referer = get_referer('service/view.php?id=' . $issue->id);
$errors = array();

?>

<? decorate("Usuwanie zgłoszenia") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie zgłoszenia</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("service/delete.php?id={$issue->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Usuwanie zgłoszenia</legend>
        <p>Na pewno chcesz usunąć zgłoszenie &lt;<?= e($issue->subject) ?>&gt;?</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń zgłoszenie">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
