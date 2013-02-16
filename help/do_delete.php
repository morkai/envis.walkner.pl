<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_POST['id']));

no_access_if_not_allowed('help*');

$page = fetch_one('SELECT id, parent, position, title FROM help WHERE id=:id', array(':id' => $_POST['id']));

if (empty($page)) output_json(true);

$conn = get_conn();

try
{
  $conn->beginTransaction();

  exec_stmt(sprintf(
    'UPDATE help SET position=position-1 WHERE parent %s AND position > %d', ($page->parent ? ' = ' . $page->parent : 'IS NULL'), $page->position
  ));

  exec_stmt('DELETE FROM help WHERE id=:id', array(':id' => $page->id));

  $conn->commit();

  output_json(true);
}
catch (PDOException $x)
{
  $conn->rollBack();

  output_json(array('status' => false, 'error' => $x->getMessage()));
}
