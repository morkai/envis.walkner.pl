<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(!is('post') || empty($_POST['template']) || empty($_GET['type']));

$referer = get_referer('offers/templates/');

$tpl = array_merge(array(
  'id' => 0,
  'name' => '',
  'type' => $_GET['type'],
), $_POST['template']);

if (isset($tpl['delete']) && !empty($tpl['id']))
{
  exec_stmt('DELETE FROM offer_templates WHERE id=?', array(1 => $tpl['id']));
  set_flash('Szablon został usunięty pomyślnie.');
  go_to($referer);
}

bad_request_if(!in_array($tpl['type'], array('client', 'intro', 'outro')));

if (is_empty($tpl['name']))
{
  set_flash('Nazwa szablonu jest wymagana.', 'error');
  go_to($referer);
}

$bindings = array(
  'type' => $tpl['type'],
  'name' => $tpl['name'],
);

switch ($tpl['type'])
{
  case 'intro':
  case 'outro':
    $bindings['template'] = serialize(array($tpl['type'] => $tpl[$tpl['type']]));
    break;

  case 'client':
    $bindings['template'] = serialize(array(
      'clientName' => $tpl['clientName'],
      'clientContact' => $tpl['clientContact']
    ));
    break;
}

if (empty($tpl['id']))
  exec_insert('offer_templates', $bindings);
else
  exec_update('offer_templates', $bindings, 'id=' . $tpl['id']);

set_flash('Szablon został zapisany pomyślnie.');
go_to($referer);
