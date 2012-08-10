<?php

include '../_common.php';

if (empty($_GET['issue'])) bad_request();

no_access_if_not_allowed('service/templates*');

$issue = fetch_one('SELECT id, subject FROM issues WHERE id=?', array(1 => $_GET['issue']));

if (empty($issue)) not_found();

$referer = get_referer('service/view.php?id=' . $issue->id);
$errors  = array();

if (!empty($_POST['copy']))
{
  $copy = $_POST['copy'];

  if (empty($copy['tasks']))
  {
    $errors[] = 'Należy wybrać przynajmniej jedno zadanie do skopiowania.';
  }

  if (!empty($errors)) goto VIEW;

  $db = get_conn();

  try
  {
    $db->beginTransaction();

    $tasks = implode(', ', array_filter($copy['tasks'], 'is_numeric'));
    $tasks = fetch_all('SELECT summary, description FROM issue_template_tasks WHERE id IN(' . $tasks . ')');

    exec_stmt('UPDATE issue_tasks SET position=position+' . count($tasks) . ' WHERE issue=?', array(1 => $issue->id));
    
    $position = 0;

    $stmt = prepare_stmt('INSERT INTO issue_tasks SET issue=?, position=?, summary=?, description=?, createdAt=?, createdBy=?');

    $comment = "Skopiowano zadania:\n";

    foreach ($tasks as $task)
    {
      $comment .= "\n* {$task->summary}";
      
      $stmt->execute(array(
        $issue->id,
        $position++,
        $task->summary,
        $task->description,
        time(),
        $_SESSION['user']->getId(),
      ));
    }

    record_issue_change($issue->id, true, $comment);

    $db->commit();

    log_info(sprintf('Skopiowano zadania do zgłoszenia <%s>.', $issue->subject));

    set_flash('Zadania skopiowano pomyślnie.');

    go_to($referer);
  }
  catch (PDOException $x)
  {
    $db->rollBack();

    $errors[] = $x->getMessage();
  }
}
else
{
  $copy = array('template' => 0);
}

VIEW:

$templates = fetch_array('SELECT id AS `key`, name AS value FROM issue_templates ORDER BY name');

if (!empty($copy['template']))
{
  $tasks = fetch_array('SELECT id AS `key`, `summary` AS value FROM issue_template_tasks WHERE template=? ORDER BY summary', array(1 => $copy['template']));
}
else
{
  $tasks = array();
}

?>

<? begin_slot('js') ?>
<script>
$(function()
{
  var tpl   = $('#copyTemplate');
  var tasks = $('#copyTasks');

  tpl.change(function()
  {
    tasks.html('');

    if (this.value == 0) return;

    $.get('<?= url_for("service/templates/tasks/fetch.php") ?>?template=' + this.value, function(taskList)
    {
      if (!taskList) return;

      for (var i = 0; i < taskList.length; ++i)
      {
        tasks.append('<option value=' + taskList[i].id + ' selected>' + taskList[i].summary);
      }
    });
  });
});
</script>
<? append_slot() ?>

<? decorate("Kopiowanie zadań z szablonu do zgłoszenia") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Kopiowanie zadań z szablonu do zgłoszenia</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("service/templates/copy.php?issue={$issue->id}") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label>Zgłoszenie</label>
            <span><?= e($issue->subject) ?></span>
          <li>
            <?= label('copyTemplate', 'Szablon zadań') ?>
            <select id="copyTemplate" name="copy[template]" autofocus>
              <option value="0">
              <?= render_options($templates, $copy['template']) ?>
            </select>
          <li>
            <?= label('copyTasks', 'Zadania do skopiowania*') ?>
            <select id="copyTasks" name="copy[tasks][]" multiple>
              <?= render_options($tasks, $copy['tasks']) ?>
            </select>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Kopiuj zadania">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
