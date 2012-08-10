<?php

include_once __DIR__ . '/../_common.php';

if (empty($_GET['issue1']) || empty($_GET['issue2'])) bad_request();

$issue = fetch_all('SELECT id, owner FROM issues WHERE id=?', array(1 => $_GET['issue1']));

if (empty($issue)) not_found();

$currentUser = $_SESSION['user'];

no_access_if_not($currentUser->isSuper()
                 || $issue1->owner == $currentUser->getId()
                 || (!$issue1->owner && is_allowed_to('service/edit')));

$referer = get_referer("service/view.php?id={$issue->id}");

try
{
  exec_stmt('DELETE FROM issue_relations WHERE issue1=? AND issue2=?', array(1 => $_GET['issue1'], $_GET['issue2']));

  set_flash('Powiązanie zostało usunięte pomyślnie.');
}
catch (PDOException $x)
{
  set_flash($x->getMessage(), 'error');
}

go_to($referer);
