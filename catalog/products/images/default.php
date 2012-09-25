<?php

include __DIR__ . '/../../../_common.php';

if (empty($_REQUEST['product']) || empty($_REQUEST['id'])) bad_request();

no_access_if_not_allowed('catalog/manage');

$product = (int)$_REQUEST['product'];
$image = (int)$_REQUEST['id'];

try
{
  exec_update('catalog_products', array('image' => $image), "id={$product}");
}
catch (PDOException $x)
{
  bad_request($x->getMessage());
}

if (!is_ajax())
{
  go_to('/catalog/?product=' . $product);
}
