<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('service/templates*');

$referer = get_referer('service/templates');
$errors = array();

if (!empty($_POST['template']))
{
  $template = $_POST['template'] + array('tasks' => array());

  if (!between(1, $template['name'], 255))
  {
    $errors[] = 'Nazwa jest wymagana.';
  }

  if (!empty($errors)) goto VIEW;

  $db = get_conn();

  try
  {
    $db->beginTransaction();

    exec_insert('issue_templates', array('createdBy' => $_SESSION['user']->getId(),
                                         'createdAt' => time(),
                                         'name' => $template['name']));

    $template['id'] = $db->lastInsertId();

    if (!empty($template['tasks']))
    {
      $stmt = prepare_stmt('INSERT INTO issue_template_tasks SET template=?, summary=?, description=?');

      foreach ($template['tasks'] as $task)
      {
        if (is_empty($task['summary'])) continue;

        $stmt->execute(array($template['id'], $task['summary'], $task['description']));
      }
    }

    log_info('Dodano szablon zadań <%s>.', $template['name']);

    set_flash(sprintf('Szablon zadań <%s> został dodany pomyślnie.', $template['name']));

    $db->commit();

    go_to("service/templates/view.php?id={$template['id']}");
  }
  catch (PDOException $x)
  {
    $db->rollBack();

    $errors[] = $x->getMessage();
  }
}
else
{
  $template = array(
    'name' => '',
    'tasks' => array(),
  );
}

VIEW:

$template['tasks'] = array_values($template['tasks']);

escape_array($template);

?>

<? begin_slot('head') ?>
<style>
  label img { vertical-align: top; cursor: pointer; }
  #tasks > li { position: relative; }
  .deleteTask
  {
    position: absolute;
    top: 0.5em;
    right: 1em;
    cursor: pointer;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  var tasks = $('#tasks');
  var taskCount = '<?= count($template['tasks']) ?>';
  var taskTemplate = $('#taskTemplate').hide().detach();

  $('#addTask').click(function()
  {
    var tpl = taskTemplate.clone();

    tpl.find('label').each(function()
    {
      this.htmlFor = this.htmlFor.replace('_k_', taskCount);
    });
    tpl.find('input, textarea').each(function()
    {
      this.id = this.id.replace('_k_', taskCount);
      this.name = this.name.replace('_k_', taskCount);
    });

    tasks.append(tpl);

    tpl.fadeIn();

    $('#taskSummary' + taskCount).focus();

    ++taskCount;
  });

  $('.deleteTask').live('click', function()
  {
    $(this).parent().fadeOut(function()
    {
      $(this).remove();
    });
  });
});
</script>
<? append_slot() ?>

<? decorate("Dodawanie nowego szablonu zadań - Serwis") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowy szablon zadań</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("service/templates/add.php") ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label('templateName', 'Nazwa*') ?>
            <input id="templateName" name="template[name]" type="text" maxlength="255" value="<?= $template['name'] ?>">
          <li>
            <label>Zadania <?= fff('dodaj kolejne zadanie', 'add', null, 'addTask') ?></label>
            <ol id="tasks" class="form-fields">
              <li id="taskTemplate">
                <img class="deleteTask" src="<?= url_for_media('fff/delete.png') ?>" alt="usuń" title="usuń">
                <fieldset>
                <ol class="form-fields">
                  <li>
                    <?= label("taskSummary_k_", 'Nazwa*') ?>
                    <input id="taskSummary_k_" name="template[tasks][_k_][summary]" type="text" value="">
                  <li>
                    <?= label("taskDescription_k_", 'Opis') ?>
                    <textarea id="taskDescription_k_" name="template[tasks][_k_][description]" class="markdown"></textarea>
                </ol>
                </fieldset>
              <? foreach ($template['tasks'] as $k => $task): ?>
              <li>
                <img class="deleteTask" src="<?= url_for_media('fff/delete.png') ?>" alt="usuń" title="usuń">
                <fieldset>
                <ol class="form-fields">
                  <li>
                    <?= label("taskSummary{$k}", 'Nazwa*') ?>
                    <input id="taskSummary<?= $k ?>" name="template[tasks][<?= $k ?>][summary]" type="text" value="<?= $task['summary'] ?>">
                  <li>
                    <?= label("taskDescription{$k}", 'Opis') ?>
                    <textarea id="taskDescription<?= $k ?>" name="template[tasks][<?= $k ?>][description]" class="markdown"><?= $task['description'] ?></textarea>
                </ol>
                </fieldset>
              <? endforeach ?>
            </ol>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj szablon zadań">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
