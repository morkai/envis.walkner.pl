<?php

$fileArray = array();
foreach ($_POST as $key => $value) {
	if ($key != 'folder') {
		if (file_exists(dirname(__FILE__) . str_replace('//','/', rtrim($_POST['folder'], '\\/')) . '/' . $value)) {
			$fileArray[$key] = $value;
		}
	}
}
echo json_encode($fileArray);