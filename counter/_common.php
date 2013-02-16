<?php

include_once __DIR__ .  '/../_common.php';

function get_counter_var()
{
  return array_key_exists('var', $_GET) && preg_match('/^[a-z0-9-_]+$/i', $_GET['var']) ? $_GET['var'] : ENVIS_COUNTER_VARIABLE;
}
