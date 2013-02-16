<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['id']) || empty($_GET['template']));

no_access_if_not_allowed('service/templates*');

$query = <<<SQL
SELECT t.id, t.summary, t.description, t.template, tpl.name AS templateName
FROM issue_template_tasks t
INNER JOIN issue_templates tpl ON tpl.id=t.template
WHERE t.id=?
SQL;

$oldTask = (array)fetch_one($query, array(1 => $_GET['id']));

$referer = get_referer("service/templates/view.php?id={$oldTask['template']}");
$errors = array();

if (!empty($_POST['task']))
{
  $task = $_POST['task'];

  if (!between(1, $task['summary'], 255))
  {
    $errors[] = 'Nazwa jest wymagana.';
  }

  if (!empty($errors)) goto VIEW;

  try
  {
    exec_update('issue_template_tasks',
                array('summary' => $task['summary'],
                      'description' => $task['description']),
                'id=' . $oldTask['id']);

    log_info('Zmodyfikowano zadanie <%s> z szablonu <%s>.', $task['summary'], $oldTask['templateName']);

    set_flash(sprintf('Zadanie <%s> zostało zmodyfikowane pomyślnie.', $task['summary']));

    go_to($referer);
  }
  catch (PDOException $x)
  {
    $errors[] = $x->getMessage();
  }
}
else
{
  $task = $oldTask;
}

VIEW:

escape_array($task);

?>

<? decorate("Edycja zadania") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja zadania</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("service/templates/tasks/edit.php?id={$oldTask['id']}&template={$oldTask['template']}") ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label("taskSummary", 'Szablon') ?>
            <span><?= e($oldTask['templateName']) ?></span>
          <li>
            <?= label("taskSummary", 'Nazwa*') ?>
            <input id="taskSummary" name="task[summary]" type="text" value="<?= $task['summary'] ?>">
          <li>
            <?= label("taskDescription", 'Opis') ?>
            <textarea id="taskDescription" name="task[description]" class="markdown resizable"><?= $task['description'] ?></textarea>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Edytuj zadanie">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
