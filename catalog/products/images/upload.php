<?php

include __DIR__ . '/../../_common.php';
include_once __DIR__ . '/../../../_lib_/wideimage/lib/WideImage.php';

no_access_if_not_allowed('catalog/manage');

bad_request_if(empty($_REQUEST['product']) || !is_numeric($_REQUEST['product']));

$file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim($_REQUEST['file'], '/\\');

bad_request_if(!file_exists($file));

$currentUser = $_SESSION['user'];

$dotPos = strrpos($_REQUEST['name'], '.');

exec_insert('catalog_product_images', $bindings = array(
  'product' => $_REQUEST['product'],
  'file' => substr(strrchr($_REQUEST['file'], DIRECTORY_SEPARATOR), 1),
  'description' => $dotPos === false ? $_REQUEST['name'] : substr($_REQUEST['name'], 0, $dotPos)
));

WideImage::loadFromFile($file)->resize(1920, 1080, 'inside', 'down')->saveToFile($file);

$bindings['id'] = (int)get_conn()->lastInsertId();

escape_vars($bindings['description']);

output_json($bindings);
