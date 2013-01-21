<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']) || empty($_GET['product']));

no_access_if_not_allowed('catalog/manage');

try
{
  exec_stmt('DELETE FROM catalog_product_documentations WHERE product=:product AND documentation=:documentation LIMIT 1', array(
    ':product' => $_GET['product'],
    ':documentation' => $_GET['id']
  ));
}
catch (PDOException $x)
{
  bad_request($x->getMessage());
}
