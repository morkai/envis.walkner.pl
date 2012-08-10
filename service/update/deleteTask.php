<?php

bad_request_if(empty($_REQUEST['task']));

$task = fetch_one('SELECT id, summary, position FROM issue_tasks WHERE id=?', array(1 => $_REQUEST['task']));

if (empty($task)) not_found();

if (count($_POST))
{
  $db = get_conn();

  try
  {
    $db->beginTransaction();

    exec_stmt('DELETE FROM issue_tasks WHERE id=?', array(1 => $task->id));
    exec_stmt('UPDATE issue_tasks SET position=position-1 WHERE issue=? AND position > ?', array(1 => $issue->id, $task->position));

    update_issue_completion_percent($issue->id);

    record_issue_change($issue->id, true, 'Usunięto zadanie: ' . $task->summary);

    set_flash(sprintf('Zadanie <%s> zostało usunięte pomyślnie.', $task->summary));

    $db->commit();
  }
  catch (PDOException $x)
  {
    set_flash($x->getMessage(), 'error');
  }

  go_to("service/view.php?id={$issue->id}");
}

$referer = get_referer('service/view.php?id=' . $task->id);
$errors  = array();

?>

<? decorate("Usuwanie zadania ze zgłoszenia") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Usuwanie zadania</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("service/update.php?what=deleteTask&issue={$issue->id}&task={$task->id}") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Usuwanie zadania</legend>
				<p>Na pewno chcesz usunąć zadanie &lt;<?= e($task->summary) ?>&gt;?</p>
				<ol class="form-actions">
					<li><input type="submit" value="Usuń zadanie">
					<li><a href="<?= $referer ?>">Anuluj</a>
				</ol>
			</fieldset>
		</form>
	</div>
</div>
