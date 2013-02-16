<?php

bad_request_if(empty($_POST['task']) || !isset($_POST['pos']));

no_access_if(!$role & ISSUE_ROLE_SUPER && !$role & ISSUE_ROLE_OWNER);

$task = fetch_one('SELECT id, issue, position AS oldPosition FROM issue_tasks WHERE id=? LIMIT 1', array(1 => $_POST['task']));

not_found_if(empty($task));

$task->newPosition = (int)$_POST['pos'];

if ($task->newPosition == $task->oldPosition)
  output_json(array('status' => false, 'newPosition' => $task->newPosition, 'oldPosition' => $task->oldPosition));

$conn = get_conn();

try
{
  $conn->beginTransaction();

  $bindings = array(':issue' => $task->issue,
                    ':oldPosition' => $task->oldPosition,
                    ':newPosition' => $task->newPosition);

  if ($task->oldPosition < $task->newPosition)
  {
    exec_stmt('UPDATE issue_tasks SET position=position-1 WHERE issue=:issue AND position > :oldPosition AND position <= :newPosition',
              $bindings);
  }
  else
  {
    exec_stmt('UPDATE issue_tasks SET position=position+1 WHERE issue=:issue AND position >= :newPosition AND position < :oldPosition',
              $bindings);
  }

  unset($bindings[':oldPosition']);

  $bindings[':task'] = $task->id;

  exec_stmt('UPDATE issue_tasks SET position=:newPosition WHERE issue=:issue AND id=:task', $bindings);

  $conn->commit();

  output_json(array('status' => true));
}
catch (PDOException $x)
{
  $conn->rollBack();

  output_json(array('status' => false, 'error' => $x->getMessage()));
}
