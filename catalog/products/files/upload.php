<?php

include_once __DIR__ . '/../../_common.php';

no_access_if_not_allowed('catalog/manage');

bad_request_if(empty($_REQUEST['product']) || !is_numeric($_REQUEST['product']));

$file = $_REQUEST['file'];

if (strpos($file, '://') === false)
{
  $file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim($file, '/\\');

  bad_request_if(!file_exists($file) || !is_file($file));

  $file = substr(strrchr($_REQUEST['file'], DIRECTORY_SEPARATOR), 1);
}

$currentUser = $_SESSION['user'];

$name = $_REQUEST['name'];

exec_insert('catalog_product_files', $bindings = array(
  'product' => $_REQUEST['product'],
  'uploader' => $_SESSION['user']->getId(),
  'uploadedAt' => time(),
  'file' => $file,
  'name' => $name
));

$bindings['id'] = (int)get_conn()->lastInsertId();
$bindings['uploadedAt'] = date('Y-m-d H:i', $bindings['uploadedAt']);
$bindings['uploaderName'] = e($_SESSION['user']->getName());
$bindings['type'] = get_file_type_from_name($bindings['file']);

escape_vars($bindings['name']);

output_json($bindings);
