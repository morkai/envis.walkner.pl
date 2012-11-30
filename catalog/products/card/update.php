<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_REQUEST['product']));

no_access_if_not_allowed('catalog/manage');

$product = fetch_one('SELECT id FROM catalog_products WHERE id=? LIMIT 1', array(1 => $_REQUEST['product']));

not_found_if(empty($product));

$db = get_conn();

try
{
  $db->beginTransaction();

  if (empty($_REQUEST['page']))
  {
    $page = null;
  }
  else
  {
    $page = fetch_one('SELECT * FROM catalog_card_pages WHERE id=? LIMIT 1', array(1 => $_REQUEST['page']));
  }

  if (empty($page))
  {
    $lastPage = fetch_one('SELECT position FROM catalog_card_pages WHERE product=? ORDER BY position DESC LIMIT 1', array(1 => $product->id));

    $bindings = array(
      'product' => $product->id,
      'layout' => empty($_REQUEST['layout']) ? 'simplePage' : $_REQUEST['layout'],
      'contents' => empty($_REQUEST['contents']) ? '' : $_REQUEST['contents']
    );

    if (empty($_REQUEST['position']) || !is_numeric($_REQUEST['position']))
    {
      $lastPage = fetch_one('SELECT position FROM catalog_card_pages WHERE product=? ORDER BY position DESC LIMIT 1', array(1 => $product->id));

      if (empty($lastPage))
      {
        $bindings['position'] = 1;
      }
      else
      {
        $bindings['position'] = $lastPage->position + 1;
      }
    }
    else
    {
      $bindings['position'] = (int)$_REQUEST['position'];

      exec_stmt('UPDATE catalog_card_pages SET position=position+1 WHERE product=:product AND position >= :position', array(
        ':product' => $product->id,
        ':position' => $bindings['position']
      ));
    }

    exec_insert('catalog_card_pages', $bindings);

    $page = (object)$bindings;
    $page->id = $db->lastInsertId();
  }
  else
  {
    $bindings = array();

    if (!empty($_REQUEST['position']) && $_REQUEST['position'] != $page->position)
    {
      $bindings['position'] = (int)$_REQUEST['position'];
    }

    if (!empty($_REQUEST['layout']) && $_REQUEST['layout'] != $page->layout)
    {
      $bindings['layout'] = (string)$_REQUEST['layout'];
    }

    if (array_key_exists('contents', $_REQUEST) && $_REQUEST['contents'] !== $page->contents)
    {
      $bindings['contents'] = (string)$_REQUEST['contents'];
    }

    if (!empty($bindings))
    {
      exec_update('catalog_card_pages', $bindings, "id={$page->id}");
    }
  }

  $db->commit();
}
catch (PDOException $x)
{
  $db->rollBack();

  bad_request($x->getMessage());
}

output_json(array('page' => (int)$page->id, 'product' => (int)$page->product));
