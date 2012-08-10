<?php

no_access_if(!$role & ISSUE_ROLE_OWNER
          && !$role & ISSUE_ROLE_CREATOR
          && !$role & ISSUE_ROLE_ASSIGNEE
          && !$role & ISSUE_ROLE_SUPER);

$bindings = array(':issue' => $issue->id, ':user' => $_SESSION['user']->getId());

if (fetch_one('SELECT 1 FROM issue_subscribers WHERE issue=:issue AND user=:user', $bindings) === false)
{
  $bindings[':now'] = time();

  exec_stmt('INSERT INTO issue_subscribers SET issue=:issue, user=:user, recentlyNotifiedAt=:now', $bindings);
}
else
{
  exec_stmt('DELETE FROM issue_subscribers WHERE issue=:issue AND user=:user', $bindings);
}

go_to('service/view.php?id=' . $issue->id);