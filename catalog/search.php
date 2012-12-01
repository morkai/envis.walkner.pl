<?php

include_once __DIR__ . '/_common.php';

$q = trim(@$_GET['q']);

if (empty($q))
{
  goto VIEW;
}

$bindings = array();

$q = <<<SQL
SELECT
  p.id, p.name, p.nr, p.type,
  p.kind, k.nr AS kindNr, k.name AS kindName,
  p.manufacturer, m.nr AS manufacturerNr, m.name AS manufacturerName,
  p.category,
  i.file AS image
FROM catalog_products p
LEFT JOIN catalog_product_kinds k ON k.id=p.kind
LEFT JOIN catalog_manufacturers m ON m.id=p.manufacturer
LEFT JOIN catalog_product_images i ON i.id=p.image
ORDER BY p.id DESC
LIMIT 20
SQL;

$products = fetch_all($q, $bindings);

$results = array_map(function($product)
{
  $product->image = empty($product->image)
    ? url_for('_static_/img/no-image.png')
    : url_for('_files_/products/' . $product->image);

  return $product;
}, $products);

VIEW:

?>

<? begin_slot('head') ?>
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
}
.catalog-search-result-category {
  padding: 0;
}
.catalog-search-result-category h2 {
  font-size: 1em;
}
</style>
<? append_slot() ?>

<? decorate('Wyszukiwanie - Katalog produktów') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Wyniki wyszukiwania produktów</h1>
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
            <img src="<?= $result->image ?>">
          <td rowspan="1" colspan="4">
            <h1><a href="<?= url_for("catalog/?category={$result->category}&product={$result->id}") ?>"><?= e($result->name) ?></a></h1>
        </tr>
        <tr>
          <td class="catalog-search-result-category" rowspan="1" colspan="4">
            <h2><?= catalog_render_category_path($result->category) ?></h2>
        </tr>
        <tr>
          <td><strong>Nr:</strong><br><?= dash_if_empty($result->nr) ?>
          <td><strong>Typ:</strong><br><?= dash_if_empty($result->type) ?>
          <td><strong>Rodzaj:</strong><br><?= dash_if_empty($result->kindName) ?>
          <td><strong>Wykonawca:</strong><br><?= dash_if_empty($result->manufacturerName) ?>
        </tr>
      </tbody>
      <? endforeach ?>
    </table>
    <? endif ?>
  </div>
</div>
