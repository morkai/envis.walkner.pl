<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['template']));

no_access_if_not_allowed('service/templates*');

$tpl = fetch_one('SELECT id, name FROM issue_templates WHERE id=?', array(1 => $_GET['template']));

$referer = get_referer("service/templates/view.php?id={$tpl->id}");
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
    exec_insert('issue_template_tasks', array('summary' => $task['summary'],
                                              'description' => $task['description'],
                                              'template' => $tpl->id));

    log_info('Dodano zadanie <%s> do szablonu <%s>.', $task['summary'], $tpl->name);

    set_flash(sprintf('Zadanie <%s> zostało dodane pomyślnie.', $task['summary']));

    go_to("service/templates/view.php?id={$tpl->id}");
  }
  catch (PDOException $x)
  {
    $errors[] = $x->getMessage();
  }
}
else
{
  $task = array(
    'summary' => '',
    'description' => '',
  );
}

VIEW:

escape_array($task);

?>

<? decorate("Dodawanie nowego zadania do szablonu <{$tpl->name}>") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowe zadanie</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("service/templates/tasks/add.php?template={$tpl->id}") ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label("taskSummary", 'Szablon') ?>
            <span><?= e($tpl->name) ?></span>
          <li>
            <?= label("taskSummary", 'Nazwa*') ?>
            <input id="taskSummary" name="task[summary]" type="text" value="<?= $task['summary'] ?>">
          <li>
            <?= label("taskDescription", 'Opis') ?>
            <textarea id="taskDescription" name="task[description]" class="markdown resizable"><?= $task['description'] ?></textarea>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj zadanie">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
