<?php

include_once __DIR__ . '/../_common.php';

$CATALOG_CATEGORIES_CACHE_FILE = __DIR__ . '/../_files_/catalog-categories.txt';

/**
 * @return array
 */
function catalog_set_categories_cache()
{
  global $CATALOG_CATEGORIES_CACHE_FILE;

  if (!file_exists($CATALOG_CATEGORIES_CACHE_FILE))
  {
    touch($CATALOG_CATEGORIES_CACHE_FILE);
  }

  if (!is_writable($CATALOG_CATEGORIES_CACHE_FILE))
  {
    chmod($CATALOG_CATEGORIES_CACHE_FILE, 0666);
  }

  $categories = fetch_all('SELECT * FROM catalog_categories ORDER BY parent ASC');
  $cache = array(
    'children' => array(),
    'categories' => array()
  );

  foreach ($categories as $category)
  {
    $cache['children'][$category->id] = array();
    $cache['categories'][$category->id] = $category;

    if (!isset($cache['children'][$category->parent]))
    {
      $cache['children'][$category->parent] = array();
    }

    $cache['children'][$category->parent][] = $category->id;
  }

  file_put_contents($CATALOG_CATEGORIES_CACHE_FILE, serialize($cache));

  return $cache;
}

/**
 * @return array
 */
function catalog_get_categories_cache()
{
  global $CATALOG_CATEGORIES_CACHE_FILE;
  static $cache = false;

  if ($cache !== false)
  {
    return $cache;
  }

  if (!file_exists($CATALOG_CATEGORIES_CACHE_FILE))
  {
    return catalog_set_categories_cache();
  }

  $cache = null;
  $data = @file_get_contents($CATALOG_CATEGORIES_CACHE_FILE);

  if (!empty($data))
  {
    $data = @unserialize($data);

    if (is_array($data))
    {
      $cache = $data;
    }
  }

  return $cache;
}

/**
 * @param int|null $parentId
 * @return array
 */
function catalog_get_categories($parentId = null)
{
  $result = array();
  $cache = catalog_get_categories_cache();

  if (empty($cache) || empty($cache['children'][$parentId]))
  {
    return $result;
  }

  $categories = $cache['children'][$parentId];

  foreach ($categories AS $categoryId)
  {
    $category = $cache['categories'][$categoryId];
    $category->children = $cache['children'][$categoryId];

    $result[] = $category;
  }

  return $result;
}

function catalog_get_category_path($categoryId)
{
  $result = array();
  $cache = catalog_get_categories_cache();

  if (empty($cache) || empty($cache['categories'][$categoryId]))
  {
    return $result;
  }

  do
  {
    $category = $cache['categories'][$categoryId];

    array_unshift($result, $category);

    $categoryId = $category->parent;
  }
  while ($categoryId !== null);

  return $result;
}

function catalog_get_product_markings()
{
  static $definitions = null;

  if ($definitions === null)
  {
    $definitions = include_once __DIR__ . '/products/markings/definitions.php';
  }

  $markings = array();

  foreach ($definitions as $marking => $definition)
  {
    $markings[$marking] = (object)array(
      'id' => $marking,
      'name' => $definition['name'],
      'file' => $definition['file'],
      'src' => url_for('catalog/products/markings/' . $definition['file'])
    );
  }

  return $markings;
}

function catalog_prepare_product_markings($productMarkings)
{
  $markings = catalog_get_product_markings();
  $result = array();

  foreach (explode(',', $productMarkings) as $marking)
  {
    $marking = trim($marking);

    if (empty($markings[$marking]))
    {
      continue;
    }

    $result[] = $markings[$marking];
  }

  return $result;
}

function catalog_get_product_kinds()
{
  $rows = fetch_all('SELECT id, nr, name FROM catalog_product_kinds ORDER BY nr ASC');
  $kinds = array();

  foreach ($rows as $row)
  {
    $kinds[$row->id] = e($row->nr) . ' - ' . e($row->name);
  }

  return $kinds;
}

function catalog_get_manufacturers()
{
  $rows = fetch_all('SELECT id, nr, name FROM catalog_manufacturers ORDER BY nr ASC');
  $kinds = array();

  foreach ($rows as $row)
  {
    $kinds[$row->id] = e($row->nr) . ' - ' . e($row->name);
  }

  return $kinds;
}

function catalog_generate_product_nr($product)
{
  $nr = '';

  if (empty($product['kind']))
  {
    $nr .= '00';
  }
  else
  {
    $kind = fetch_one('SELECT nr FROM catalog_product_kinds WHERE id=?', array(1 => $product['kind']));
    $nr .= str_pad(!$kind ? '00' : $kind->nr, 2, '0');
  }

  if (empty($product['manufacturer']))
  {
    $nr .= '00';
  }
  else
  {
    $manufacturer = fetch_one('SELECT nr FROM catalog_manufacturers WHERE id=?', array(1 => $product['manufacturer']));
    $nr .= str_pad(!$manufacturer ? '00' : $manufacturer->nr, 2, '0');
  }

  $nr .= $product['revision'];

  if (preg_match('/^[0-9]{2}([0-9]{2})-([0-9]{2})$/', $product['productionDate'], $matches))
  {
    $nr .= $matches[2] . $matches[1];
  }
  else
  {
    $nr .= '0000';
  }

  if (empty($product['id']))
  {
    $nr .= '000';
  }
  else
  {
    $nr .= str_pad($product['id'], 3, '0');
  }

  return $nr;
}

function catalog_render_categories_tree($selectedCategoryId = null)
{
  $cache = catalog_get_categories_cache();

  $expandedCategoryIds = array_map(
    function($category) { return $category->id; },
    catalog_get_category_path($selectedCategoryId)
  );

  return _catalog_render_categories($cache, $cache['children'][null], $expandedCategoryIds);
}

function _catalog_render_categories($cache, $categories, $expandedCategoryIds, $level = 0)
{
  $html = '<ol class="catalog-tree-categories catalog-tree-level-' . $level . '">';

  foreach ($categories as $categoryId)
  {
    $category = $cache['categories'][$categoryId];

    if (empty($category))
    {
      continue;
    }

    $hasChildren = !empty($cache['children'][$categoryId]);
    $expanded = in_array($categoryId, $expandedCategoryIds);

    $className = 'catalog-tree-category'
      . ' ' . ($hasChildren ? 'catalog-tree-with-children' : 'catalog-tree-without-children')
      . ' ' . ($expanded ? 'catalog-tree-expanded' : 'catalog-tree-collapsed');

    $id = 'catalog-tree-category-' . $categoryId;

    $html .= '<li class="' . $className . '">';
    $html .= '<input id="' . $id . '" class="catalog-tree-category-toggle" type="checkbox" ' . checked_if($expanded) . '>';
    $html .= '<label for="' . $id . '" class="catalog-tree-category-toggle"></label>';
    $html .= '<a class="catalog-tree-category-name" href="' . url_for("catalog/?category={$categoryId}") . '">' . e($category->name) . '</a>';

    if ($hasChildren)
    {
      $html .= _catalog_render_categories(
        $cache,
        $cache['children'][$categoryId],
        $expandedCategoryIds,
        $level + 1
      );
    }
  }

  $html .= '</ol>';

  return $html;
}
