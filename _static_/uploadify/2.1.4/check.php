<?php

$fileArray = array();

foreach ($_POST as $key => $value)
{
  if ($key !== 'folder')
  {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_files_' . DIRECTORY_SEPARATOR . trim($_REQUEST['folder'], '/\\') . DIRECTORY_SEPARATOR . $value))
    {
      $fileArray[$key] = $value;
    }
  }
}

echo json_encode($fileArray);
