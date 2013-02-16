<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_POST['id']) || empty($_POST['properties']));

no_access_if_not(is_allowed_to('vis/factory/edit'), has_access_to_factory($_POST['id']));

$factory = fetch_one('SELECT v.factory FROM factories f LEFT JOIN vis_factories v ON v.factory=f.id WHERE f.id=?', array(1 => $_POST['id']));

bad_request_if(empty($factory));

if ($factory->factory === null)
{
  exec_stmt('INSERT INTO vis_factories SET factory=?', array(1 => $_POST['id']));
}

$availProps = array(
  'width', 'height', 'fg_color', 'bg_color', 'bg_image', 'bg_position', 'bg_repeat',
);

$match = array();

if (isset($_POST['properties']['bg_image'])
&& preg_match('/^[0-9]+_new\.(.*?)$/', $_POST['properties']['bg_image'], $match)
&& file_exists($file = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/vis-factory-bg/' . $_POST['properties']['bg_image']))
{
  $_POST['properties']['bg_image'] = (int)$_POST['id'] . '.' . $match[1];

  rename($file, dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/vis-factory-bg/' . $_POST['properties']['bg_image']);

  echo 'url(' . url_for(ENVIS_UPLOADS_DIR . '/vis-factory-bg/' . $_POST['properties']['bg_image']) . '?u=' . mt_rand() . ')';
}
elseif (isset($_POST['properties']['bg_image']))
{
  unset($_POST['properties']['bg_image']);
}

$bindings = array(':factory' => (int)$_POST['id']);

$sql = 'UPDATE vis_factories SET ';

foreach ((array)$_POST['properties'] as $name => $value)
{
  if (!in_array($name, $availProps)) continue;

  $sql .= $name . '=:' . $name . ', ';

  $bindings[':' . $name] = $value;
}

$sql = substr($sql, 0, -2) . ' WHERE factory=:factory';

exec_stmt($sql, $bindings);
