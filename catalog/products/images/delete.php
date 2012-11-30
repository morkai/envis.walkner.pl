<?php

include __DIR__ . '/../../_common.php';

bad_request_if(empty($_REQUEST['product']) || empty($_REQUEST['id']));

no_access_if_not_allowed('catalog/manage');

$image = fetch_one('SELECT id, product, file FROM catalog_product_images WHERE id=?', array(1 => $_REQUEST['id']));

not_found_if(empty($image));

exec_stmt('DELETE FROM catalog_product_images WHERE id=?', array(1 => $_REQUEST['id']));

$path = __DIR__ . '/../../../_files_/products/' . $image->file;

if (file_exists($path))
{
  unlink($path);
}

if (!is_ajax())
{
  go_to('/catalog/?product=' . $image->product);
}
