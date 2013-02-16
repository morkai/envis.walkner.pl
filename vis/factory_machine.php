<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_POST['id']) || empty($_POST['properties']));

no_access_if_not(is_allowed_to('vis/factory/edit'), has_access_to_machine($_POST['id']));

$machine = fetch_one('SELECT m.id, v.machine FROM machines m LEFT JOIN vis_factory_machines v ON v.machine=m.id WHERE m.id=?', array(1 => $_POST['id']));

bad_request_if(empty($machine));

if ($machine->machine === null)
{
  exec_stmt('INSERT INTO vis_factory_machines SET machine=?', array(1 => $_POST['id']));
}

$availProps = array(
  'top', 'left', 'zindex', 'image', 'image_width', 'image_height', 'image_max_width', 'image_max_height', 'fg_color', 'bg_color',
);

if (isset($_POST['properties']['image'])
&& preg_match('/^[0-9]+_new\.(.*?)$/', $_POST['properties']['image'], $match)
&& file_exists($file = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/vis-machine/' . $_POST['properties']['image']))
{
  $_POST['properties']['image'] = (int)$_POST['id'] . '.' . $match[1];

  rename($file, $newFile = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/vis-machine/' . $_POST['properties']['image']);

  $img = getimagesize($newFile);

  $_POST['properties']['image_width'] = $_POST['properties']['image_max_width'] = $img[0];
  $_POST['properties']['image_height'] = $_POST['properties']['image_max_height'] = $img[1];

  echo json_encode(array(
    'src' => url_for(ENVIS_UPLOADS_DIR . '/vis-machine/' . $_POST['properties']['image']) . '?u=' . mt_rand(),
    'width' => $_POST['properties']['image_width'],
    'height' => $_POST['properties']['image_height']
  ));
}
elseif (isset($_POST['properties']['image']))
{
  unset($_POST['properties']['image']);

  echo '{}';
}
else
{
  echo '{}';
}

if (isset($_POST['zindex'][$machine->id]))
{
  $_POST['properties']['zindex'] = (int)$_POST['zindex'][$machine->id];

  unset($_POST['zindex'][$machine->id]);
}

$bindings = array(':machine' => $_POST['id']);

$sql = 'UPDATE vis_factory_machines SET ';

foreach ((array)$_POST['properties'] as $name => $value)
{
  if (!in_array($name, $availProps)) continue;

  $sql .= '`' . $name . '`=:' . $name . ', ';

  $bindings[':' . $name] = $value;
}

$sql = substr($sql, 0, -2) . ' WHERE machine=:machine';

exec_stmt($sql, $bindings);

if (!empty($_POST['zindex']))
{
  foreach ($_POST['zindex'] as $machine => $zindex)
  {
    exec_stmt('UPDATE vis_factory_machines SET zindex=? WHERE machine=?', array(1 => (int)$zindex, $machine));
  }
}
