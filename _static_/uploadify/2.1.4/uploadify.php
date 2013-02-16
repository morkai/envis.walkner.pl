<?php

if (empty($_FILES))
{
  exit;
}

$blacklist = array(
  'php', 'swf', 'exe', 'htaccess'
);

$tempFile = $_FILES['Filedata']['tmp_name'];
$targetPath = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . '_files_' . DIRECTORY_SEPARATOR . trim($_REQUEST['folder'], '/\\') . DIRECTORY_SEPARATOR;
$info = pathinfo($_FILES['Filedata']['name']);
$targetFile = str_replace(array('//', '\\\\'), DIRECTORY_SEPARATOR, $targetPath) . md5(uniqid(microtime())) . '.' . $info['extension'];

if (in_array($info['extension'], $blacklist))
{
  exit;
}

if (move_uploaded_file($tempFile, $targetFile))
{
  echo substr($targetFile, strlen($_SERVER['DOCUMENT_ROOT']));
}
