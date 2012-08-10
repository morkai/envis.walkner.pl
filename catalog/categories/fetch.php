<?php

include '../../_common.php';

no_access_if_not_allowed('catalog*');

$root = isset($_GET['id']) ? (int)preg_replace('/[^\d]/', '', $_GET['id']) : 0;

$query = <<<SQL
SELECT c.id,
       c.name,
       (SELECT COUNT(*) FROM catalog_products WHERE category=c.id)
FROM catalog_categories c
WHERE COALESCE(c.parent, 0)=?
ORDER BY c.name
SQL;
$categories = fetch_all($query, array(1 => $root));

$products = fetch_all('SELECT id, name FROM catalog_products WHERE category=? ORDER BY name',
                      array(1 => $root));

$tree = array();

foreach ($categories as $category)
{
  $node = array(
    'data' => array(
      'title' => $category->name,
      'attr'  => array('class' => 'category', 'data-id' => $category->id)
    ),
    'attr' => array(
      'id' => 'category-' . $category->id
    ),
    'state' => 'closed',
  );

  $tree[] = $node;
}

foreach ($products as $product)
{
  $node = array(
    'data' => array(
      'title' => $product->name,
      'attr' => array('class' => 'product', 'data-id' => $product->id)
    ),
    'attr' => array(
      'id' => 'product-' . $product->id,
    ),
  );

  $tree[] = $node;
}

output_json($tree);