<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

no_access_if_not_allowed('catalog/manage');

$query = <<<SQL
SELECT f.id, f.product, f.name
FROM catalog_product_files f
WHERE f.id=?
LIMIT 1
SQL;

$file = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($file));

$referer = get_referer("catalog/?product={$file->product}#files");

try
{
  exec_update('catalog_product_files', array('name' => $_REQUEST['name']), "id={$file->id}");

  if (!is_ajax())
  {
    set_flash(sprintf('Plik <%s> został zmieniony.', $file->name));
    go_to($referer);
  }
}
catch (PDOException $x)
{
  bad_request_if(is_ajax());

  set_flash($x->getMessage(), 'error');
  go_to($referer);
}
