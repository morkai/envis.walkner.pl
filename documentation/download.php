<?php

if (!empty($_GET['docs']))
{
  $bypassAuth = true;
}

include './_common.php';

if (empty($_GET['id'])) bad_request();

$file = fetch_one('SELECT f.file, f.name, d.machine FROM documentation_files f INNER JOIN documentations d ON d.id=f.documentation WHERE f.id=:id', array(':id' => $_GET['id']));

if (empty($file) ) not_found();

if (empty($_GET['docs']))
{
  no_access_if_not(has_access_to_machine($file->machine));
}

$ext = substr(strrchr($file->file, '.'), 1);

//$filename = preg_replace('/[^A-Za-z0-9-_]/', '', str_replace(' ', '_', $file->name)) . '.' . $ext;
if (strpos($file->name, '.') === false)
{
	$filename = $file->name . '.' . $ext;
}
else
{
	$filename = $file->name;
}

$filepath = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/documentation/' . $file->file;

if (!file_exists($filepath))
{
	exec_stmt('DELETE FROM documentation_files WHERE id=:id', array(':id' => $_GET['id']));

	not_found();
}

$mimetypes = include '../_lib_/mimetypes.php';

$contentType = array_search($ext, $mimetypes, true);

if ($contentType === false)
{
	$contentType = 'application/octet-stream';
}

header(sprintf('Content-Type: %s', $contentType));
header(sprintf('Content-Disposition: inline; filename="%s"', $filename));

readfile($filepath);
