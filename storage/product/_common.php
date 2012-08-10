<?php

include '../../_common.php';

function storage_format_price($price)
{
	return (float)str_replace(',', '.', $price);
}