<?php

include __DIR__ . '/../../_common.php';

bad_request_if(empty($_REQUEST['product']) || empty($_REQUEST['id']));

no_access_if_not_allowed('catalog/manage');

$image = fetch_one('SELECT id, product, file FROM catalog_product_images WHERE id=?', array(1 => $_REQUEST['id']));

not_found_if(empty($image));

exec_stmt('DELETE FROM catalog_product_images WHERE id=?', array(1 => $_REQUEST['id']));

$path = ENVIS_UPLOADS_PATH . '/products/' . $image->file;
$thumbPath = preg_replace('/([a-z0-9]{32})\.([a-zA-Z]+)$/', '$1.thumb.$2', $path);

if (file_exists($path))
{
  unlink($path);

  if (file_exists($thumbPath))
  {
    unlink($thumbPath);
  }
}

if (!is_ajax())
{
  go_to('/catalog/?product=' . $image->product);
}
