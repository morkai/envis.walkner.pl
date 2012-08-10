<?php

if (empty($_POST['updateTime'])) bad_request();

no_access_if(!$role & ISSUE_ROLE_OWNER
          && !$role & ISSUE_ROLE_ASSIGNEE);

$updateTime = $_POST['updateTime'];

if ($role & ISSUE_ROLE_OWNER)
{
  $condition = "id={$issue->id}";
  $table     = 'issues';
  $fields    = array(
    'ownerStakes'     => (float)$updateTime['stakes'],
    'ownerStakesType' => (int)$updateTime['stakesType']
  );
}
else
{
  $condition = "issue={$issue->id} AND assignee={$userId}";
  $table     = 'issue_assignees';
  $fields    = array(
    'stakes'     => (float)$updateTime['stakes'],
    'stakesType' => (int)$updateTime['stakesType']
  );
}

$conn = get_conn();

try
{
  $conn->beginTransaction();

  exec_update($table, $fields, $condition);
  
  if ((int)$updateTime['timeSpent'] >= 1)
  {
    exec_insert('issue_times', array(
      'issue'     => $issue->id,
      'user'      => $userId,
      'createdAt' => time(),
      'timeSpent' => (int)$updateTime['timeSpent'],
      'comment'   => (string)$updateTime['comment']
    ));
  }
  
  $conn->commit();
  
  set_flash('Czas pracy został zaktualizowany pomyślnie.');
}
catch (Exception $x)
{
  $conn->rollBack();

  set_flash($x->getMessage(), 'error');
}

go_to(get_referer('service/view.php?id=' . $issue->id));