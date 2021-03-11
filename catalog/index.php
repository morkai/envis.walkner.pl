<?php

include_once __DIR__ . '/_common.php';
include_once __DIR__ . '/../service/_common.php';
include_once __DIR__ . '/../_lib_/PagedData.php';

$category = empty($_GET['category']) || !is_numeric($_GET['category']) ? null : (int)$_GET['category'];
$product = empty($_GET['product']) || !is_numeric($_GET['product']) ? null : (int)$_GET['product'];

$page = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$perPage = 24;

$categoryPath = array();
$subcategories = array();
$products = array();

if ($category !== null)
{
  $categoryPath = catalog_get_category_path($category);

  not_found_if(empty($categoryPath));

  $category = $categoryPath[count($categoryPath) - 1];
  $subcategories = catalog_get_categories($category->id);

  $pagedProducts = new PagedData($page, $perPage);

  $q = <<<SQL
SELECT COUNT(*) AS totalCount
FROM catalog_products
WHERE category=:category
SQL;

  $totalProductCount = fetch_one($q, array(':category' => $category->id))->totalCount;

  $q = <<<SQL
SELECT p.id, p.category, p.name, p.type, p.nr, p.image
FROM catalog_products p
WHERE p.category=:category
ORDER BY p.name ASC
LIMIT {$pagedProducts->getOffset()}, {$pagedProducts->getPerPage()}
SQL;

  $products = fetch_all($q, array(':category' => $category->id));

  $productMap = array();

  foreach ($products as $categoryProduct)
  {
    $categoryProduct->thumb = null;
    $productMap[$categoryProduct->id] = $categoryProduct;
  }

  if (!empty($products))
  {
    $productIds = join(',', array_keys($productMap));

    $q = <<<SQL
SELECT * FROM catalog_product_images WHERE product IN($productIds)
SQL;

    $images = fetch_all($q);

    foreach ($images as $image)
    {
      $categoryProduct = $productMap[$image->product];

      if ($categoryProduct->image === $image->id || !$categoryProduct->thumb)
      {
        $categoryProduct->thumb = $image;
      }
    }
  }

  $pagedProducts->fill($totalProductCount, $products);
}
else if ($product === null)
{
  $subcategories = catalog_get_categories();
}

if ($product !== null)
{
  $q = <<<SQL
SELECT
  p.*,
  m.name AS manufacturerName, m.nr AS manufacturerNr,
  k.name AS kindName, k.nr AS kindNr
FROM catalog_products p
LEFT JOIN catalog_manufacturers m ON m.id=p.manufacturer
LEFT JOIN catalog_product_kinds k ON k.id=p.kind
WHERE p.id=:product
LIMIT 1
SQL;

  $product = fetch_one($q, array(':product' => $product));

  not_found_if(empty($product));

  $product->images = fetch_all('SELECT * FROM catalog_product_images WHERE product=?', array(1 => $product->id));

  $product->issues = fetch_all('SELECT id, subject, status, orderNumber, orderInvoice FROM issues WHERE relatedProduct=? ORDER BY updatedAt DESC', array(1 => $product->id));

  if (empty($categoryPath))
  {
    $categoryPath = catalog_get_category_path($product->category);
  }

  $product->markings = catalog_prepare_product_markings($product->markings);

  $product->files = fetch_all('SELECT f.*, u.name AS uploaderName FROM catalog_product_files f INNER JOIN users u ON u.id=f.uploader WHERE f.product=? ORDER BY f.name ASC', array(1 => $product->id));
  $product->files = array_map(function($file)
  {
    $file->type = get_file_type_from_name($file->file);

    return $file;
  }, $product->files);

  $q = <<<SQL
SELECT d.id, d.title, d.description
FROM documentations d
WHERE d.id IN(SELECT p.documentation FROM catalog_product_documentations p WHERE p.product=:product)
ORDER BY d.title ASC
SQL;

  $docs = fetch_all($q, array(':product' => $product->id));

  $docsIds = array_map(function($doc) { return $doc->id; }, $docs);

  $files = empty($docsIds)
    ? array()
    : fetch_all('SELECT id, documentation, name FROM documentation_files WHERE documentation IN(' . implode(',', $docsIds) . ') ORDER BY name ASC');

  $product->docs = array();

  foreach ($docs as $doc)
  {
    $doc->files = array();

    $product->docs[$doc->id] = $doc;
  }

  foreach ($files as $file)
  {
    $product->docs[$file->documentation]->files[] = $file;
  }
}

$canManageProducts = is_allowed_to('catalog/manage');
$canAddDocumentation = is_allowed_to('documentation/add');
$canEditDocumentation = is_allowed_to('documentation/edit');
$canDeleteDocumentation = is_allowed_to('documentation/delete');

$isRoot = $category === null && $product === null;
$showCatalog = empty($product) && (!empty($category) || !empty($subcategories));
$showProduct = !empty($product);
$showTree = $isRoot || !empty($category);

$categoryId = empty($category) ? '' : $category->id;
$productId = empty($product) ? '' : $product->id;

?>
<? begin_slot('head') ?>
<? if ($canManageProducts): ?>
<link rel="stylesheet" href="<?= url_for_media('uppy/uppy.min.css', true) ?>">
<? endif ?>
<link rel="stylesheet" href="<?= url_for_media('jquery-plugins/lightbox/2.51/css/lightbox.css') ?>">
<link rel="stylesheet" href="<?= url_for("catalog/_static_/main.css") ?>">
<style type="text/css">

</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<? if ($canManageProducts && $showProduct): ?>
<script>
var PRODUCT_FILE_UPLOADER_CONFIG = {
  script: '<?= url_for_media("uppy/uppy.php", true) ?>',
  uploadImageUrl: '<?= url_for("catalog/products/images/upload.php") ?>',
  uploadFileUrl: '<?= url_for("catalog/products/files/upload.php") ?>',
  currentProduct: <?= $product->id ?>
};
</script>
<script src="<?= url_for_media("uppy/uppy.min.js", true) ?>"></script>
<? endif ?>
<script src="<?= url_for_media('jquery-plugins/lightbox/2.51/js/lightbox.js') ?>"></script>
<script src="<?= url_for("catalog/_static_/main.js") ?>"></script>
<? append_slot() ?>

<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($isRoot && $canManageProducts): ?>
  <li><a href="<?= url_for("catalog/kinds/") ?>">Zarządzaj rodzajami</a>
  <li><a href="<?= url_for("catalog/manufacturers/") ?>">Zarządzaj wykonawcami</a>
  <li><a href="<?= url_for("catalog/categories/add.php") ?>">Dodaj kategorię</a>
  <? endif ?>
  <? if (!empty($category) && $canManageProducts): ?>
  <li><a href="<?= url_for("catalog/categories/add.php?parent={$category->id}") ?>">Dodaj podkategorię</a>
  <li><a href="<?= url_for("catalog/categories/edit.php?id={$category->id}") ?>">Edytuj kategorię</a>
  <li><a href="<?= url_for("catalog/categories/delete.php?id={$category->id}") ?>">Usuń kategorię</a>
  <li><a href="<?= url_for("catalog/products/add.php?category={$category->id}") ?>">Dodaj produkt</a>
  <? endif ?>
  <? if ($showProduct): ?>
  <? if ($canManageProducts): ?>
  <li><a href="<?= url_for("catalog/products/edit.php?id={$product->id}") ?>">Edytuj produkt</a>
  <li><a href="<?= url_for("catalog/products/delete.php?id={$product->id}") ?>">Usuń produkt</a>
  <? endif ?>
  <li><a href="<?= url_for("catalog/products/card/?id={$product->id}") ?>">Pokaż kartę katalogową</a>
  <? endif ?>
</ul>
<? append_slot() ?>

<? decorate('Katalog produktów') ?>

<? include __DIR__ . '/_breadcrumbs.php' ?>

<? if ($showTree): ?>
<div id="catalog-container">
  <div id="catalog-tree-container">
    <? include __DIR__ . '/_tree.php' ?>
  </div>
  <div id="catalog-contents-container">
    <? if ($showCatalog): ?>
    <? include __DIR__ . '/_catalog.php' ?>
    <? endif ?>
    <? if ($showProduct): ?>
    <? include __DIR__ . '/_product.php' ?>
    <? endif ?>
  </div>
</div>
<? else: ?>
<? if ($showCatalog): ?>
<? include __DIR__ . '/_catalog.php' ?>
<? endif ?>
<? if ($showProduct): ?>
<? include __DIR__ . '/_product.php' ?>
<? endif ?>
<? endif ?>
