<?php

include_once __DIR__ . '/../../../_common.php';

bad_request_if(empty($_REQUEST['page']));

no_access_if_not_allowed('catalog/manage');

$db = get_conn();

try
{
  $db->beginTransaction();

  $page = fetch_one('SELECT id, product, position FROM catalog_card_pages WHERE id=? LIMIT 1', array(1 => $_REQUEST['page']));

  not_found_if(empty($page));

  exec_stmt('DELETE FROM catalog_card_pages WHERE id=? LIMIT 1', array(1 => $page->id));

  exec_stmt('UPDATE catalog_card_pages SET position=position-1 WHERE product=? AND position > ?', array(1 => $page->product, $page->position));

  $db->commit();
}
catch (PDOException $x)
{
  $db->rollBack();

  bad_request($x->getMessage());
}

if (!is_ajax())
{
  go_to(get_referer("/catalog/products/card/?id={$page->product}"));
}
