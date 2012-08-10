<?php

include '../_common.php';

function doc_features($doc)
{
	if (!empty($doc->device))
	{
		$result = sprintf('%s \ %s \ %s', $doc->factory, $doc->machine, $doc->device);
	}
	else
	{
		$result = sprintf('%s \ %s', $doc->factory, $doc->machine);
	}

	return escape($result);
}
