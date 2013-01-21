<?php

if (!empty($_GET['docs']))
{
  $bypassAuth = true;
}

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

$file = fetch_one('SELECT f.file, f.name, d.machine FROM documentation_files f INNER JOIN documentations d ON d.id=f.documentation WHERE f.id=:id', array(':id' => $_GET['id']));

not_found_if(empty($file));

if (empty($_GET['docs']) && !empty($file->machine))
{
  no_access_if_not(has_access_to_machine($file->machine));
}

$ext = substr(strrchr($file->file, '.'), 1);

if (strpos($file->name, '.') === false)
{
	$filename = $file->name . '.' . $ext;
}
else
{
	$filename = $file->name;
}

$filepath = ENVIS_UPLOADS_PATH . '/documentation/' . $file->file;

if (!file_exists($filepath))
{
	exec_stmt('DELETE FROM documentation_files WHERE id=:id', array(':id' => $_GET['id']));

	not_found();
}

$mimetypes = include __DIR__ . '/../_lib_/mimetypes.php';

$contentType = array_search($ext, $mimetypes, true);

if ($contentType === false)
{
	$contentType = 'application/octet-stream';
}

header(sprintf('Content-Type: %s', $contentType));
header(sprintf('Content-Disposition: inline; filename="%s"', $filename));

readfile($filepath);
