<?php

include_once __DIR__ . '/../_common.php';
include_once __DIR__ . '/../../service/_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('catalog*');

$product = fetch_one('SELECT * FROM catalog_products WHERE id=? LIMIT 1',
                     array(1 => $_GET['id']));

if (empty($product)) not_found();

$product->images = fetch_all('SELECT * FROM catalog_product_images WHERE product=?', array(1 => $product->id));

$product->issues = fetch_all('SELECT id, subject, status, orderNumber, orderInvoice FROM issues WHERE relatedProduct=? ORDER BY updatedAt DESC', array(1 => $product->id));

$canManageProducts = is_allowed_to('catalog/manage');

?>

<? decorate('Karta produktu') ?>

<div class="block">
  <ul class="block-header">
    <li><h1 class="block-name">Karta produktu</h1>
    <li><?=  fff('Pokaż kartę katalogową', 'page', "catalog/products/card/?id={$product->id}", 'showProductCardLink') ?>
    <? if ($canManageProducts): ?>
    <li><?=  fff('Edytuj produkt', 'page_edit', "catalog/products/edit.php?id={$product->id}", 'editProductLink') ?>
    <li><?=  fff('Usuń produkt', 'page_delete', "catalog/products/delete.php?id={$product->id}", 'deleteProductLink') ?>
    <? endif ?>
  </ul>
  <div class="block-body">
    <dl>
      <dt>Nr
      <dd><?= e($product->nr) ?>
      <dt>Nazwa
      <dd><?= e($product->name) ?>
      <dt>Typ
      <dd><?= dash_if_empty($product->type) ?>
      <dt>Publiczny
      <dd><?= $product->public ? 'Tak' : 'Nie' ?>
    </dl>
    <?= markdown($product->description) ?>
  </div>
</div>

<div id="productTabs">
  <ul>
    <li><a href="#issues">Powiązane zgłoszenia</a>
    <li><a href="#gallery">Galeria</a>
    <li><a href="#docs">Dokumentacje</a>
    <li><a href="#files">Pliki</a>
  </ul>
  <div id="issues">
    <table>
      <thead>
        <tr>
          <th>Temat</th>
          <th>Status</th>
          <th>Nr zamówienia</th>
          <th>Nr faktury</th>
        </tr>
      </thead>
      <tbody>
        <? foreach ($product->issues as $issue): ?>
        <tr>
          <td class="clickable"><a href="<?= url_for("service/view.php?id={$issue->id}") ?>"><?= e($issue->subject) ?></a></td>
          <td><?= $statuses[$issue->status] ?></td>
          <td><?= dash_if_empty($issue->orderNumber) ?></td>
          <td><?= dash_if_empty($issue->orderInvoice) ?></td>
        </tr>
        <? endforeach ?>
      </tbody>
    </table>
  </div>
  <div id="gallery">
    <ul id=productImages>
      <? foreach ($product->images as $image): ?>
      <li>
        <a class="thumb <?= $image->id === $product->image ? 'default' : '' ?>" href="<?= url_for("/_files_/products/{$image->file}") ?>" rel="lightbox[<?= $product->id ?>]" title="<?= e($image->description) ?>" data-id="<?= $image->id ?>">
          <img src="<?= url_for("/_files_/products/{$image->file}") ?>" alt="">
        </a>
        <? if ($canManageProducts): ?>
        <div class="actions">
          <!-- <?= fff('Edytuj opis obrazu', 'pencil', "catalog/products/images/edit.php?product={$product->id}&id={$image->id}") ?> //-->
          <?= fff('Ustaw jako domyślne', 'bullet_tick', "catalog/products/images/default.php?product={$product->id}&id={$image->id}", null, 'default') ?>
          <?= fff('Usuń obraz', 'bullet_cross', "catalog/products/images/delete.php?product={$product->id}&id={$image->id}", null, 'delete') ?>
        </div>
        <? endif ?>
      <? endforeach ?>
    </ul>
    <? if ($canManageProducts): ?>
    <input id="productImageFile" name=file type=file>
    <? endif ?>
  </div>
  <div id="docs">
    <p>TODO</p>
  </div>
  <div id="files">
    <p>TODO</p>
  </div>
</div>
