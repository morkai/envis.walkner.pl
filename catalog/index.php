<?php

include_once __DIR__ . '/_common.php';
include_once __DIR__ . '/../service/_common.php';

$category = empty($_GET['category']) || !is_numeric($_GET['category']) ? null : (int)$_GET['category'];
$product = empty($_GET['product']) || !is_numeric($_GET['product']) ? null : (int)$_GET['product'];

include_once '../_lib_/PagedData.php';

$page = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$perPage = 10;

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
SELECT id, category, name, type, nr
FROM catalog_products
WHERE category=:category
ORDER BY name ASC
LIMIT {$pagedProducts->getOffset()}, {$pagedProducts->getPerPage()}
SQL;

  $products = fetch_all($q, array(':category' => $category->id));

  $pagedProducts->fill($totalProductCount, $products);
}
else if ($product === null)
{
  $subcategories = catalog_get_categories();
}

if ($product !== null)
{
  $product = fetch_one('SELECT * FROM catalog_products WHERE id=:product LIMIT 1', array(':product' => $product));

  not_found_if(empty($product));

  $product->images = fetch_all('SELECT * FROM catalog_product_images WHERE product=?', array(1 => $product->id));

  $product->issues = fetch_all('SELECT id, subject, status, orderNumber, orderInvoice FROM issues WHERE relatedProduct=? ORDER BY updatedAt DESC', array(1 => $product->id));

  if (empty($categoryPath))
  {
    $categoryPath = catalog_get_category_path($product->category);
  }
}

$canManageProducts = is_allowed_to('catalog/manage');

$isRoot = $category === null && $product === null;
$showCatalog = !empty($category) || !empty($subcategories) || empty($product);
$showProduct = !empty($product);

$categoryId = empty($category) ? '' : $category->id;
$productId = empty($product) ? '' : $product->id;

?>
<? begin_slot('head') ?>
<? if ($canManageProducts): ?>
<link rel="stylesheet" href="<?= url_for_media("uploadify/2.1.4/uploadify.css", true) ?>">
<? endif ?>
<link rel="stylesheet" href="<?= url_for_media('jquery-plugins/lightbox/2.51/css/lightbox.css') ?>">
<link rel="stylesheet" href="<?= url_for("catalog/_static_/main.css") ?>">
<style type="text/css">
  <? if (!empty($subcategories) && !empty($pagedProducts) && $pagedProducts->getPage() === 1): ?>
  #products tr:first-child td {
    border-top: 0;
  }
  <? endif ?>
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<? if ($canManageProducts && $showProduct): ?>
<script>
var PRODUCT_IMAGE_UPLOADER_CONFIG = {
  uploader: '<?= url_for_media("uploadify/2.1.4/uploadify.swf", true) ?>',
  script: '<?= url_for_media("uploadify/2.1.4/uploadify.php", true) ?>',
  cancelImg: '<?= url_for_media("uploadify/2.1.4/cancel.png", true) ?>',
  uploadImageUrl: '<?= url_for("catalog/products/images/upload.php") ?>',
  currentProduct: <?= $product->id ?>
};
</script>
<script src="<?= url_for_media("uploadify/2.1.4/swfobject.js", true) ?>"></script>
<script src="<?= url_for_media("uploadify/2.1.4/jquery.uploadify.min.js", true) ?>"></script>
<? endif ?>
<script src="<?= url_for_media('jquery-plugins/lightbox/2.51/js/lightbox.js') ?>"></script>
<script src="<?= url_for("catalog/_static_/main.js") ?>"></script>
<? append_slot() ?>

<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($isRoot): ?>
  <li><a href="<?= url_for("catalog/categories/add.php") ?>">Dodaj kategorię</a>
  <? endif ?>
  <? if (!empty($category)): ?>
  <li><a href="<?= url_for("catalog/categories/add.php?parent={$category->id}") ?>">Dodaj podkategorię</a>
  <li><a href="<?= url_for("catalog/categories/edit.php?id={$category->id}") ?>">Edytuj kategorię</a>
  <li><a href="<?= url_for("catalog/categories/delete.php?id={$category->id}") ?>">Usuń kategorię</a>
  <li><a href="<?= url_for("catalog/products/add.php?category={$category->id}") ?>">Dodaj produkt</a>
  <? endif ?>
  <? if ($showProduct): ?>
  <li><a href="<?= url_for("catalog/products/edit.php?id={$product->id}") ?>">Edytuj produkt</a>
  <li><a href="<?= url_for("catalog/products/delete.php?id={$product->id}") ?>">Usuń produkt</a>
  <li><a href="<?= url_for("catalog/products/card/?id={$product->id}") ?>">Pokaż kartę katalogową</a>
  <? endif ?>
</ul>
<? append_slot() ?>

<? decorate('Katalog produktów') ?>

<? if (!$isRoot): ?>
<ul id="breadcrumbs">
  <li><a href="<?= url_for("catalog/") ?>">Katalog produktów</a>
  <? foreach ($categoryPath as $pathCategory): ?>
  <li>&nbsp;&gt; <a href="<?= url_for("catalog/?category={$pathCategory->id}") ?>"><?= e($pathCategory->name) ?></a>
  <? endforeach ?>
  <? if ($showProduct): ?>
  <li>&nbsp;&gt; <a href="<?= url_for("catalog/?product={$product->id}") ?>"><?= e($product->name) ?></a>
  <? endif ?>
</ul>
<? endif ?>

<? if ($showCatalog): ?>
<? include __DIR__ . '/_catalog.php' ?>
<? endif ?>

<? if ($showProduct): ?>
<? include __DIR__ . '/_product.php' ?>
<? endif ?>
