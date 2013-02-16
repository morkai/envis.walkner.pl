<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['issue1']) || empty($_GET['issue2']));

$issues = fetch_all(sprintf('SELECT id, owner FROM issues WHERE id IN(%s)', implode(', ', array((int)$_GET['issue1'], (int)$_GET['issue2']))));

if (count($issues) !== 2) not_found();

if ($issues[0]->id == $_GET['issue1'])
{
  list ($issue1, $issue2) = $issues;
}
else
{
  list ($issue2, $issue1) = $issues;
}

$dual = !empty($_GET['dual']);

$currentUser = $_SESSION['user'];

no_access_if_not($currentUser->isSuper()
                 || $issue1->owner == $currentUser->getId()
                 || (!$issue1->owner && is_allowed_to('service/edit')));

if ($dual)
{
  no_access_if_not($currentUser->isSuper()
                   || $issue2->owner == $currentUser->getId()
                   || (!$issue2->owner && is_allowed_to('service/edit')));
}

$errors = 0;

try
{
  exec_insert('issue_relations', array('issue1' => $issue1->id, 'issue2' => $issue2->id));
}
catch (PDOException $x)
{
  ++$errors;
}

if ($dual)
{
  try
  {
    exec_insert('issue_relations', array('issue2' => $issue1->id, 'issue1' => $issue2->id));
  }
  catch (PDOException $x)
  {
    ++$errors;
  }
}

bad_request_if(($errors === 1 && !$dual) || ($errors === 2 && $dual));
