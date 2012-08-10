<?php

$referer = get_referer('service/view.php?id=' . (int)$_REQUEST['issue']);

if (empty($_REQUEST['who'])) go_to($referer);

no_access_if(!$role & ISSUE_ROLE_OWNER
          && !$role & ISSUE_ROLE_ASSIGNER
          && !$role & ISSUE_ROLE_SUPER);

$assignee = fetch_one('SELECT id, name, email FROM users WHERE name=? LIMIT 1', array(1 => $_REQUEST['who']));

if (empty($assignee))
{
  set_flash(sprintf('Osoba <%s> nie istnieje w systemie.', $_REQUEST['who']), 'error');
  go_to($referer);
}

if ($issue->owner == $assignee->id) go_to($referer);

$conn = get_conn();

try
{
  $conn->beginTransaction();

  exec_stmt('INSERT INTO issue_assignees SET issue=?, assignee=?', array(1 => $issue->id, $assignee->id));

  record_issue_change($issue->id, 1, 'Przypisano nową osobę: ' . $assignee->name);

  if (!empty($_REQUEST['inform']))
  {
    send_assign_email($assignee->email, $issue->subject, $issue->id);
  }

  $conn->commit();
}
catch (Exception $x)
{
  $conn->rollBack();

  set_flash($x->getMessage());
}

go_to($referer);