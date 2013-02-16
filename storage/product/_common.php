<?php

include_once __DIR__ . '/../../_common.php';

function storage_format_price($price)
{
  return (float)str_replace(',', '.', $price);
}
