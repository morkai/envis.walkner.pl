<?php

$comment = !empty($_POST['comment']) ? trim($_POST['comment']) : '';
$tasks   = !empty($_POST['tasks']) && is_array($_POST['tasks']) ? implode(', ', array_filter($_POST['tasks'], 'is_numeric')) : array();

$timeSpent = !empty($_POST['timeSpent']) ? (int)$_POST['timeSpent'] : 0;
$reason    = !empty($_POST['reason']) ? trim($_POST['reason']) : '';

if (empty($comment) && empty($_POST['tasks']) && empty($timeSpent))
{
  go_to("service/view.php?id={$issue->id}");
}

no_access_if(!$role & ISSUE_ROLE_OWNER
          && !$role & ISSUE_ROLE_ASSIGNEE
          && !$role & ISSUE_ROLE_SUPER
          && !$role & ISSUE_ROLE_CREATOR
          && !$role & ISSUE_ROLE_DOCS_VIEWER);

$parent = empty($_GET['to']) ? null : (int)$_GET['to'];

if (!empty($tasks) && ($role & ISSUE_ROLE_OWNER || $role & ISSUE_ROLE_ASSIGNEE || $role & ISSUE_ROLE_SUPER))
{
  $completedTasks = fetch_array('SELECT id AS `key`, summary AS `value` FROM issue_tasks WHERE id IN(' . $tasks . ') AND completed=0 AND (assignedTo IS NULL OR assignedTo=' . $userId . ')');
}
else
{
  $completedTasks = array();
}

$db = get_conn();

try
{
  $db->beginTransaction();

  if (!empty($completedTasks))
  {
    $tasks = implode(',', array_keys($completedTasks));
    
    exec_stmt('UPDATE issue_tasks SET completed=1, completedAt=' . time() . ', completedBy=' . $userId . ' WHERE id IN(' . $tasks . ')');
  }

  if ($timeSpent)
  {
    exec_insert('issue_times', array(
      'issue'     => $issue->id,
      'user'      => $userId,
      'createdAt' => time(),
      'timeSpent' => $timeSpent,
      'comment'   => $reason
    ));
  }

  update_issue_completion_percent($issue->id);

  if (!empty($comment) || !empty($completedTasks))
  {
    $entry = record_issue_change($issue->id, false, $comment, array(), $completedTasks, $parent);
  }
  else
  {
    $entry = 0;
  }

  $db->commit();

  if (is_ajax() && $entry)
  {
?>
<div id=entry-<?= $entry ?> data-id=<?= $entry ?> class="entry quotable expanded">
  <h3><a href="<?= url_for("user/view.php?id={$userId}") ?>"><?= $_SESSION['user']->getName() ?></a>, <span title="<?= date('Y-m-d, H:i', time()) ?>"><?= format_time_ago(time()) ?></span>:</h3>
  <? if (!empty($completedTasks)): ?>
  <ul class="changes">
    <? foreach ($completedTasks as $task): ?>
    <li>Wykonano zadanie &lt;<span class="field"><?= $task ?></span>&gt;
    <? endforeach ?>
  </ul>
  <? endif ?>
  <? if (!empty($comment)): ?>
  <div class="comment">
    <a class="reply" href="<?= url_for("service/update.php?issue={$issue->id}&amp;what=reply") ?>"><?= fff('Cytuj', 'comment') ?></a>
    <?= markdown($comment) ?>
  </div>
  <? endif ?>
</div>
<?php

    exit;
  }
}
catch (PDOException $x)
{
  $db->rollBack();
  
  set_flash($x->getMessage(), 'error');
}

go_to("service/view.php?id={$issue->id}#entry-{$entry}");
