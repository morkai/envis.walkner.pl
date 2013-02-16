<?php

bad_request_if(empty($_GET['task']));

$query = <<<SQL
SELECT t.id, t.summary, t.description, t.completed, t.completedBy, t.assignedTo,
       u.email AS completedByEmail,
       i.subject AS issue
FROM issue_tasks t
INNER JOIN issues i ON i.id=t.issue
LEFT JOIN users u ON u.id=t.completedBy
WHERE t.id=?
SQL;

$oldTask = (array)fetch_one($query, array(1 => $_GET['task']));

if (empty($oldTask)) not_found();

if (!empty($_POST['task']))
{
  $task = $_POST['task'];

  if ($oldTask['completed'])
  {
    $task['reopen'] = isset($task['reopen']) && $task['reopen'] == 1;
    $task['inform'] = $task['reopen']
                   && isset($task['inform'])
                   && $task['inform'] == 1
                   && $_SESSION['user']->getId() != $oldTask['completedBy']
                   && !empty($oldTask['completedByEmail']);
  }
  else
  {
    $task += array('reopen' => false, 'inform' => false);
  }

  if (is_empty($task['summary']))
  {
    $errors[] = 'Nazwa zadania jest wymagana.';
  }

  if (!empty($errors)) goto VIEW;

  $bindings = array(
    'summary' => $task['summary'],
    'description' => $task['description'],
    'assignedTo' => is_numeric($task['assignedTo']) ? (int)$task['assignedTo'] : null
  );

  if ($task['reopen'])
  {
    $bindings['completed'] = 0;
  }

  $db = get_conn();

  try
  {
    $db->beginTransaction();

    exec_update('issue_tasks', $bindings, 'id=' . $oldTask['id']);

    if ($task['reopen'])
    {
      update_issue_completion_percent($issue->id);

      $message = 'Ponownie otwarto zadanie: ' . $task['summary'];
    }
    else
    {
      $message = 'Zmodyfikowano zadanie: ' . $task['summary'];
    }

    record_issue_change($issue->id, true, $message);

    set_flash(sprintf('Zadanie <%s> zostało zmodyfikowane.', $task['summary']));

    $db->commit();

    if ($task['inform'])
    {
      $url = url_for('service/view.php?id=' . $issue->id, true);
      $creator = $_SESSION['user']->getName();
      $message = <<<MSG
Witaj!

{$creator} ponownie otworzył wykonane przez Ciebie zadanie '{$oldTask['summary']}' ze zgłoszenia '{$oldTask['issue']}' i prosił abyś został o tym poinformowany.

Kliknij poniższy odnośnik, aby wyświetlić to zgłoszenie:
{$url}

Pozdrawiam, enVis.

--

Ta wiadomość została wygenerowana automatycznie.
MSG;

      send_email($oldTask['completedByEmail'], 'Wykonane przez Ciebie zadanie zostało ponownie otwarte', $message);
    }

    if (is_ajax())
    {
      $assignee = '-';

      if ($task['assignedTo'])
      {
        $user = fetch_one('SELECT name FROM users WHERE id=? LIMIT 1', array(1 => (int)$task['assignedTo']));

        if ($user)
        {
          $assignee = $user->name;
        }
        else
        {
          $task['assignedTo'] = 0;
        }
      }

      output_json(array(
        'assignedTo' => (int)$task['assignedTo'],
        'assignee' => $assignee,
        'summary' => $task['summary'],
        'description' => markdown($task['description']),
        'completed' => $task['reopen']
      ));
    }
    else
    {
      go_to("service/view.php?id={$issue->id}");
    }
  }
  catch (PDOException $x)
  {
    $db->rollBack();

    $errors[] = $x->getMessage();
  }
}
else
{
  $task = $oldTask + array('reopen' => false, 'inform' => false);
  $errors = array();
}

VIEW:

bad_request_if(is_ajax() && !empty($errors));

escape_array($task);

$assignees = fetch_array('SELECT u.id AS `key`, u.name AS `value` FROM issue_assignees a INNER JOIN users u ON u.id=a.assignee WHERE a.issue=? ORDER BY u.name', array(1 => $issue->id));

if ($issue->owner)
{
  $assignees[$issue->owner] = $issue->ownerName;
}

$referer = get_referer('service/view.php?id=' . $issue->id);

?>

<? decorate("Edycja zadania ze zgłoszenia") ?>

<div id=editTaskBlock class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja zadania</h1>
  </div>
  <div class="block-body">
    <form id="task" class="form" method="post" action="<?= url_for("service/update.php?what=editTask&issue={$issue->id}&task={$oldTask['id']}") ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Edycja zadania</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label('taskSummary', 'Nazwa*') ?>
            <input id="taskSummary" name="task[summary]" type="text" maxlength="100" autofocus value="<?= $task['summary'] ?>">
          <li>
            <?= label('taskDescription', 'Opis') ?>
            <textarea id="taskDescription" class="markdown resizable" name="task[description]" rows="5"><?= $task['description'] ?></textarea>
          <li>
            <?= label('taskAssignedTo', 'Wykonawca') ?>
            <select id="taskAssignedTo" name="task[assignedTo]">
              <option value=-></option>
              <?= render_options($assignees, $task['assignedTo']) ?>
            </select>
          <? if ($oldTask['completed']): ?>
          <li>
            <input id="taskReopen" name="task[reopen]" type="checkbox" value="1" <?= checked_if($task['reopen']) ?>>
            <?= label('taskReopen', 'Zmień status z powrotem na niewykonane.') ?>
          <? if ($_SESSION['user']->getId() != $oldTask['completedBy']): ?>
          <li>
            <input id="taskInform" name="task[inform]" type="checkbox" value="1" <?= checked_if($task['inform']) ?>>
            <?= label('taskInform', 'Poinformuj wykonawcę o zmianie statusu.') ?>
          <? endif ?>
          <? endif ?>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Edytuj zadanie">
              <li><a class="modalFormCancel" href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
