<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_REQUEST['product']) || empty($_REQUEST['id']));

no_access_if_not_allowed('catalog/manage');

$file = fetch_one('SELECT id, product, file FROM catalog_product_files WHERE id=?', array(1 => $_REQUEST['id']));

not_found_if(empty($file));

exec_stmt('DELETE FROM catalog_product_files WHERE id=?', array(1 => $_REQUEST['id']));

$path = __DIR__ . '/../../../_files_/products/' . $file->file;

if (file_exists($path))
{
  unlink($path);
}

if (!is_ajax())
{
  go_to('/catalog/?product=' . $file->product);
}
