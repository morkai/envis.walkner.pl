<?php

include '../_common.php';

bad_request_if(empty($_POST['template']));

no_access_if_not_allowed('service/declare');

$tpl = $_POST['template'];

if (!empty($_POST['add']))
{
  exec_insert('declaration_templates', array(
    'name' => $tpl['name'],
    'pattern' => $tpl['pattern'],
    'code' => $tpl['code']
  ));
}

if (!empty($_POST['delete']))
{
  exec_stmt('DELETE FROM declaration_templates WHERE id=?', array(1 => $tpl['id']));
}

if (!empty($_POST['edit']))
{
  exec_update('declaration_templates', array(
    'name' => $tpl['name'],
    'pattern' => $tpl['pattern'],
    'code' => $tpl['code']
  ), 'id=' . (int)$tpl['id']);
}

go_to(empty($_SERVER['HTTP_REFERER']) ? get_referer() : $_SERVER['HTTP_REFERER']);
