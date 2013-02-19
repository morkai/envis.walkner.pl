<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['file']));

preg_match('/^[a-z0-9]{32}\.(jpe?g|gif|png)$/i', $_GET['file'], $matches);

bad_request_if(empty($matches));

$ext = strtolower($matches[1]);
$type = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);

header("Content-Type: {$type}");

$originalFile = ENVIS_UPLOADS_PATH . '/products/' . $_GET['file'];
$thumbFile = ENVIS_UPLOADS_PATH . '/products/' . str_replace(".{$ext}", ".thumb.{$ext}", strtolower($_GET['file']));

if (!file_exists($thumbFile))
{
  not_found_if(!file_exists($originalFile));

  include_once __DIR__ . '/../../../_lib_/wideimage/lib/WideImage.php';

  WideImage::loadFromFile($originalFile)->resize(178, 100, 'inside', 'down')->saveToFile($thumbFile);
}

readfile($thumbFile);
