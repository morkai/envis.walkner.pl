<?php

include_once __DIR__ . '/../_common.php';

function doc_features($doc)
{
	if (!empty($doc->device))
	{
		$result = sprintf('%s \ %s \ %s', $doc->factory, $doc->machine, $doc->device);
	}
	else if (!empty($doc->machine))
	{
		$result = sprintf('%s \ %s', $doc->factory, $doc->machine);
	}
  else
  {
    $result = 'Nie przypisane';
  }

	return escape($result);
}
