<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_POST['machine']) || empty($_POST['id']) || empty($_POST['properties']));

no_access_if_not_allowed('vis/machine/edit');

$device = fetch_one('SELECT d.id, v.device, d.machine FROM engines d LEFT JOIN vis_machine_devices v ON v.machine=d.machine AND v.device=d.id WHERE d.id=? AND d.machine=?', array(1 => $_POST['id'], $_POST['machine']));

bad_request_if(empty($device));

no_access_if_not(has_access_to_machine($device->machine));

if ($device->device === null)
{
  exec_stmt('INSERT INTO vis_machine_devices SET device=?, machine=?', array(1 => $_POST['id'], $_POST['machine']));
}

$availProps = array(
  'top', 'left', 'zindex', 'image', 'image_width', 'image_height', 'image_max_width', 'image_max_height', 'fg_color', 'bg_color', 'variables_fg_color', 'variables_bg_color',
);

$match = array();

if (isset($_POST['properties']['image'])
&& preg_match('/^[A-Za-z0-9_-]+_new\.(.*?)$/', $_POST['properties']['image'], $match)
&& file_exists($file = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/vis-device/' . $_POST['properties']['image']))
{
  $_POST['properties']['image'] = $_POST['id'] . '.' . $match[1];

  rename($file, $newFile = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/vis-device/' . $_POST['properties']['image']);

  $img = getimagesize($newFile);

  $_POST['properties']['image_width'] = $_POST['properties']['image_max_width'] = $img[0];
  $_POST['properties']['image_height'] = $_POST['properties']['image_max_height'] = $img[1];

  echo json_encode(array(
    'src' => url_for(ENVIS_UPLOADS_DIR . '/vis-device/' . $_POST['properties']['image']) . '?u=' . mt_rand(),
    'width' => $_POST['properties']['image_width'],
    'height' => $_POST['properties']['image_height'],
    'status' => 1,
  ));
}
elseif (isset($_POST['properties']['image']))
{
  unset($_POST['properties']['image']);

  echo '{status: 2}';
}
elseif (isset($_POST['variables']))
{
  exec_stmt('DELETE FROM vis_machine_device_variables WHERE machine=:machine AND device=:device', array(':machine' => $_POST['machine'], ':device' => $_POST['id']));

  unset($_POST['variables']['_']);

  foreach ($_POST['variables'] as $variable)
  {
    try
    {
      exec_stmt('INSERT INTO vis_machine_device_variables SET machine=:machine, device=:device, variable=:variable', array(':machine' => $_POST['machine'], ':device' => $_POST['id'], ':variable' => $variable));
    }
    catch (PDOException $x) {}
  }

  echo json_encode(fetch_all('
SELECT
  var.name,
  var.id,
  (SELECT val.value FROM `values` val WHERE val.variable=dv.variable AND val.machine=:machine AND val.engine=:device ORDER BY val.id DESC LIMIT 1) AS value
FROM vis_machine_device_variables dv
INNER JOIN variables var ON var.id=dv.variable
WHERE dv.machine=:machine
  AND dv.device=:device
ORDER BY var.name ASC
',  array(':machine' => $_POST['machine'], ':device' => $_POST['id'])));
}
else
{
  echo '{status: 3}';
}

if (isset($_POST['zindex'][$device->id]))
{
  $_POST['properties']['zindex'] = (int)$_POST['zindex'][$device->id];

  unset($_POST['zindex'][$device->id]);
}

$bindings = array(':machine' => $_POST['machine'], ':device' => $_POST['id']);

$sql = 'UPDATE vis_machine_devices SET ';

foreach ((array)$_POST['properties'] as $name => $value)
{
  if (!in_array($name, $availProps)) continue;

  $sql .= '`' . $name . '`=:' . $name . ', ';

  $bindings[':' . $name] = $value;
}

$sql = substr($sql, 0, -2) . ' WHERE machine=:machine AND device=:device';

exec_stmt($sql, $bindings);

if (!empty($_POST['zindex']))
{
  foreach ($_POST['zindex'] as $device => $zindex)
  {
    exec_stmt('UPDATE vis_machine_devices SET zindex=? WHERE machine=? AND device=?', array(1 => (int)$zindex, $_POST['machine'], $device));
  }
}
