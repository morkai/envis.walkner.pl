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
