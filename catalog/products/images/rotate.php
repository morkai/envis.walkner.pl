<?php

include_once __DIR__ . '/../../_common.php';
include_once __DIR__ . '/../../../_lib_/wideimage/lib/WideImage.php';

no_access_if_not_allowed('catalog/manage');

bad_request_if(empty($_GET['id']));

$image = fetch_one("SELECT id, file FROM catalog_product_images WHERE id=?", array(1 => $_GET['id']));

not_found_if(empty($image));

preg_match('/\.([a-z]+)$/i', $image->file, $matches);

$ext = strtolower($matches[1]);

$originalFile = ENVIS_UPLOADS_PATH . '/products/' . $image->file;
$thumbFile = ENVIS_UPLOADS_PATH . '/products/' . str_replace(".{$ext}", ".thumb.{$ext}", strtolower($image->file));

WideImage::loadFromFile($originalFile)->rotate(90)->saveToFile($originalFile);

if (file_exists($thumbFile))
{
  WideImage::loadFromFile($thumbFile)->rotate(90)->saveToFile($thumbFile);
}
