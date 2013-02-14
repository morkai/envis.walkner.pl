<?php

include_once __DIR__ . '/_common.php';

$query = trim(@$_GET['q']);

if (empty($query))
{
  goto VIEW;
}

$bindings = array(
  ':query' => "%{$query}%"
);

$q = <<<SQL
SELECT
  p.id, p.name, p.nr, p.type, p.revision, p.productionDate,
  p.kind, k.nr AS kindNr, k.name AS kindName,
  p.manufacturer, m.nr AS manufacturerNr, m.name AS manufacturerName,
  p.category,
  i.file AS image, i.description AS imageDescription,
  IF(p.name LIKE :query, 1, 0)
  + IF(p.type LIKE :query, 1, 0)
  + IF(p.nr LIKE :query, 1, 0)
  + IF(p.productionDate LIKE :query, 1, 0)
  + IF(c.name LIKE :query, 1, 0)
  + IF(m.nr LIKE :query, 1, 0)
  + IF(m.name LIKE :query, 1, 0)
  + IF(k.nr LIKE :query, 1, 0)
  + IF(k.name LIKE :query, 1, 0)
    AS rank
FROM catalog_products p
INNER JOIN catalog_categories c ON c.id=p.category
LEFT JOIN catalog_product_kinds k ON k.id=p.kind
LEFT JOIN catalog_manufacturers m ON m.id=p.manufacturer
LEFT JOIN catalog_product_images i ON i.id=p.image
HAVING rank > 0
ORDER BY rank DESC
LIMIT 30
SQL;

$products = fetch_all($q, $bindings);

$results = array_map(function($product)
{
  if (!empty($product->image))
  {
    $product->imageFile = url_for("_files_/products/{$product->image}");
    $product->thumbFile = url_for("catalog/products/images/thumb.php?file={$product->image}");
  }

  return $product;
}, $products);

VIEW:

?>

<? begin_slot('head') ?>
<link rel="stylesheet" href="<?= url_for_media('jquery-plugins/lightbox/2.51/css/lightbox.css') ?>">
<link rel="stylesheet" href="<?= url_for("catalog/_static_/main.css") ?>">
<style>
#catalog-search-no-results {
  padding: 1em;
}
#catalog-search-results {
  padding: 0;
}
#catalog-search-results a {
  text-decoration: none;
}
#catalog-search-results img {
  max-width: 75px;
  max-height: 75px;
}
#catalog-search-results td {
  border: 0;
  vertical-align: middle;
}
#catalog-search-results tbody {
  border-top: 1px dotted #CCC;
}
#catalog-search-results tbody:first-child {
  border-top: 0;
}
#catalog-search-results tbody:hover td {
  background: #FEFEE5;
}
.catalog-search-result-image {
  width: 75px;
  padding: 0 1em;
  text-align: center;
}
.catalog-search-result-category {
  padding: 0;
}
.catalog-search-result-category h2 {
  font-size: 1em;
}
#catalog-search-results .catalog-search-result-properties td {
  padding-right: 1em;
  vertical-align: top;
}
.catalog-search-result-properties strong {
  white-space: nowrap;
}
.catalog-search-result-name {
  display: block;
}
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-plugins/lightbox/2.51/js/lightbox.js') ?>"></script>
<? append_slot() ?>

<? decorate('Wyszukiwanie - Katalog produktów') ?>

<? include __DIR__ . '/_breadcrumbs.php' ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Wyniki wyszukiwania produktów dla: <?= e($query) ?></h1>
  </div>
  <div id="catalog-search-results" class="block-body">
    <? if (empty($results)): ?>
    <p id="catalog-search-no-results">Brak wyników.</p>
    <? else: ?>
    <table>
      <? foreach ($results as $result): ?>
      <tbody>
        <tr>
          <td class="catalog-search-result-image" rowspan="3">
            <? if (empty($result->image)): ?>
            <img src="<?= url_for('_static_/img/no-image.png') ?>" alt="No image :(">
            <? else: ?>
            <a class="thumb" href="<?= $result->imageFile ?>" rel="lightbox[<?= $result->id ?>]" title="<?= e($result->imageDescription) ?>">
              <img src="<?= $result->thumbFile ?>" alt="<?= e($result->imageDescription) ?>">
            </a>
            <? endif ?>
          <td rowspan="1" colspan="7">
            <h1><a class="catalog-search-result-name" href="<?= url_for("catalog/?category={$result->category}&product={$result->id}") ?>"><?= e($result->name) ?></a></h1>
        </tr>
        <tr>
          <td class="catalog-search-result-category" rowspan="1" colspan="7">
            <h2><?= catalog_render_category_path($result->category) ?></h2>
        </tr>
        <tr class="catalog-search-result-properties">
          <td><strong>ID:</strong><br><?= $result->id ?>
          <td><strong>Nr:</strong><br><?= dash_if_empty($result->nr) ?>
          <td><strong>Typ:</strong><br><?= dash_if_empty($result->type) ?>
          <td><strong>Rodzaj:</strong><br><?= dash_if_empty($result->kindName) ?>
          <td><strong>Wykonawca:</strong><br><?= dash_if_empty($result->manufacturerName) ?>
          <td><strong>Rewizja:</strong><br><?= $result->revision ?>
          <td><strong>Data produkcji:</strong><br><?= dash_if_empty($result->productionDate) ?>
        </tr>
      </tbody>
      <? endforeach ?>
    </table>
    <? endif ?>
  </div>
</div>
