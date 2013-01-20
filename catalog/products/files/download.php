<?php

if (!empty($_GET['docs']))
{
  $bypassAuth = true;
}

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT f.id, f.product, f.file, f.name
FROM catalog_product_files f
INNER JOIN catalog_products i ON i.id=f.product
WHERE f.id=?
LIMIT 1
SQL;

$file = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($file));

$file->path = __DIR__ . '/../../../_files_/products/' . $file->file;

if (!file_exists($file->path))
{
  exec_stmt('DELETE FROM catalog_product_files WHERE id=?', array(1 => $file->id));

  not_found();
}

$mimetypes = include __DIR__ . '/../../../_lib_/mimetypes.php';

$extension = substr(strrchr($file->file, '.'), 1);
$contentType = array_search($extension, $mimetypes, true);

if ($contentType === false)
{
  $contentType = 'application/octet-stream';
}

setlocale(LC_CTYPE, 'pl_PL.utf8');

$filename = preg_replace('/[^a-zA-Z_\-\.\']+/', '', str_replace(' ', '-', @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file->name))) . ($extension === '' ? '' : ('.' . $extension));

header(sprintf('Content-Type: %s', $contentType));
header(sprintf('Content-Disposition: inline; filename="%s"', $filename));

readfile($file->path);
