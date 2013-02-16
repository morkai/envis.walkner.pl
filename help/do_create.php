<?php

include_once __DIR__ . '/_common.php';

no_access_if_not_allowed('help*');

$parent = empty($_POST['parent']) ? null : (int)$_POST['parent'];

$conn = get_conn();

try
{
  $conn->beginTransaction();

  $lastPage = fetch_one(sprintf('SELECT MAX(position) AS position FROM help WHERE parent %s', $parent ? '= ' . $parent : 'IS NULL'));

  $bindings = array(':parent' => $parent, ':position' => $lastPage->position + 1);

  exec_stmt("INSERT INTO help (parent, position, title, contents) VALUES(:parent, :position, 'Pusta strona', '')", $bindings);

  $id = (int)get_conn()->lastInsertId();

  $conn->commit();

  output_json(array('status' => true, 'id' => $id, 'position' => $bindings[':position']));
}
catch (PDOException $x)
{
  $conn->rollBack();

  output_json(array('status' => false, 'error' => $x->getMessage()));
}
