<?php

include __DIR__ . '/../../_common.php';

bad_request_if(empty($_FILES)
  || empty($_FILES['file'])
  || empty($_REQUEST['folder'])
  || strpos($_REQUEST['folder'], '..') !== false);

$folder = trim($_REQUEST['folder'], '/\\');
$file = $_FILES['file'];

no_access_if_not_allowed('*');

$blacklist = array(
  'php', 'inc', 'swf', 'exe', 'htaccess'
);

$info = pathinfo($file['name']);

bad_request_if(in_array($info['extension'], $blacklist));

$tempFile = $file['tmp_name'];
$targetPath = __DIR__ . '/../../_files_/' . $folder . '/';
$targetFile = str_replace(array('//', '\\\\'), DIRECTORY_SEPARATOR, $targetPath) . md5(uniqid(microtime())) . '.' . $info['extension'];

if (move_uploaded_file($tempFile, $targetFile))
{
  echo json_encode(array(
    'file' => substr($targetFile, strlen($_SERVER['DOCUMENT_ROOT'])),
    'name' => $file['name'],
    'type' => $info['extension']
  ));
}
else
{
  @unlink($tempFile);
  bad_request();
}
