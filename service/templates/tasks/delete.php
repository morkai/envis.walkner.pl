<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['id']) || empty($_GET['template']));

no_access_if_not_allowed('service/templates*');

$task = fetch_one('SELECT t.id, t.summary, t.template, tpl.name AS templateName FROM issue_template_tasks t INNER JOIN issue_templates tpl ON tpl.id=t.template WHERE t.id=:id', array(':id' => $_GET['id']));

not_found_if(empty($task));

$referer = get_referer('service/templates/view.php?id=' . $task->template);

if (count($_POST))
{
  exec_stmt('DELETE FROM issue_template_tasks WHERE id=:id', array(':id' => $task->id));

  log_info('Usunięto zadanie <%s> z szablonu <%s>.', $task->summary, $task->templateName);

  set_flash(sprintf('Zadanie <%s> zostało usunięte.', $task->summary));

  go_to('service/templates/view.php?id=' . $task->template);
}

?>

<? decorate("Usuwanie zadania") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie zadania</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("service/templates/tasks/delete.php?id={$task->id}&template={$task->template}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <p>Na pewno chcesz usunąć zadanie &lt;<?= e($task->summary) ?>&gt; z szablonu &lt;<?= e($task->templateName) ?>&gt;?</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń zadanie">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
