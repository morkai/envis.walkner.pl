<?php

bad_request_if(empty($_POST['newTask']));

no_access_if(!$role & ISSUE_ROLE_SUPER && !$role & ISSUE_ROLE_OWNER);

$bindings = array(
  'issue'       => $issue->id,
  'summary'     => trim($_POST['newTask']['summary']),
  'description' => trim($_POST['newTask']['description']),
  'assignedTo'  => null,
  'createdAt'   => time(),
  'createdBy'   => $_SESSION['user']->getId(),
  'position'    => 0
);

if (!empty($bindings['summary']))
{
  if (empty($bindings['description']))
  {
    $bindings['description'] = $bindings['summary'];
  }

  $assignee = 0;
  
  if (is_numeric($_POST['newTask']['assignee']))
  {
    $assignee = (int)$_POST['newTask']['assignee'];
    $assigneeName = '-';

    if ($issue->owner == $assignee)
    {
      $bindings['assignedTo'] = $assignee;
      $assigneeName = $issue->ownerName;
    }
    else
    {
      $user = fetch_one('SELECT u.name FROM issue_assignees a INNER JOIN users u ON u.id=a.assignee WHERE a.issue=? AND a.assignee=?', array(1 => $issue->id, $assignee));

      if ($user)
      {
        $assigneeName = $user->name;
      }
    }
  }

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    exec_stmt('UPDATE issue_tasks SET position=position+1 WHERE issue=?', array(1 => $issue->id));
    exec_insert('issue_tasks', $bindings);

    $id = $conn->lastInsertId();

    update_issue_completion_percent($issue->id);
    record_issue_change($issue->id, true, 'Dodano zadanie: ' . $bindings['summary']);

    $conn->commit();

    if (is_ajax())
    {
      output_json(array(
        'id'           => $id,
        'summary'      => $bindings['summary'],
        'description'  => markdown($bindings['description']),
        'assigneeId'   => $assignee,
        'assigneeName' => empty($assigneeName) ? '-' : $assigneeName,
        'createdBy'    => $_SESSION['user']->getName() . ' @ ' . date('Y-m-d, H:i', $bindings['createdAt']),
        'classNames'   => ($issue->owner == $assignee ? 'own' : '') . ' unresolved'
      ));
    }
  }
  catch (PDOException $x)
  {
    $conn->rollBack();
    
    set_flash($x->getMessage(), 'error');
  }
}

go_to("service/view.php?id={$issue->id}");
