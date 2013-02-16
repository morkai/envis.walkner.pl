<?php

bad_request_if(empty($_REQUEST['who']));

no_access_if(!$role & ISSUE_ROLE_OWNER
          && !$role & ISSUE_ROLE_ASSIGNER
          && !$role & ISSUE_ROLE_SUPER);

$bindings = array(':id' => $issue->id, 'who' => $_REQUEST['who']);

$assignee = fetch_one('SELECT id, name FROM users WHERE id=?', array(1 => $_REQUEST['who']));

bad_request_if(empty($assignee));

$conn = get_conn();

try
{
  $conn->beginTransaction();

  exec_stmt('DELETE FROM issue_assignees WHERE issue=:id AND assignee=:who', $bindings);
  exec_stmt('DELETE FROM issue_times WHERE user=?', array(1 => $assignee->id));
  exec_update('issue_tasks', array('assignedTo' => NULL), "issue={$issue->id} AND assignedTo={$assignee->id}");

  record_issue_change($issue->id, 1, 'Usunięto przypisaną osobę: ' . $assignee->name);

  $conn->commit();
}
catch (PDOException $x)
{
  $conn->rollBack();

  set_flash($x->getMessage());
}

go_to('service/view.php?id=' . $issue->id);
