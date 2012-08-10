<?php

include '../../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('catalog*');

$product = fetch_one('SELECT * FROM catalog_products WHERE id=? LIMIT 1',
                     array(1 => $_GET['id']));

if (empty($product)) not_found();

$product->images = fetch_all('SELECT * FROM catalog_product_images WHERE product=?', array(1 => $product->id));

$canManageProducts = is_allowed_to('catalog/manage');

?>

<? decorate('Karta produktu') ?>

<div class="block">
  <ul class="block-header">
    <li><h1 class="block-name">Karta produktu</h1>
    <? if ($canManageProducts): ?>
    <li><?=  fff('Edytuj produkt', 'page_edit', "catalog/products/edit.php?id={$product->id}", 'editProductLink') ?>
    <li><?=  fff('Usuń produkt', 'page_delete', "catalog/products/delete.php?id={$product->id}", 'deleteProductLink') ?>
    <? endif ?>
  </ul>
  <div class="block-body">
    <dl>
      <dt>Nazwa
      <dd><?= e($product->name) ?>
      <dt>Typ
      <dd><?= dash_if_empty($product->type) ?>
      <dt>Marka
      <dd><?= dash_if_empty($product->manufacturer) ?>
      <dt>Publiczny
      <dd><?= $product->public ? 'Tak' : 'Nie' ?>
    </dl>
    <?= markdown($product->description) ?>
    <ul id=productImages>
      <? foreach ($product->images as $image): ?>
      <li>
        <a class="thumb" href="/_files_/products/<?= $image->file ?>" rel="lightbox[<?= $product->id ?>]" title="<?= e($image->description) ?>" data-id="<?= $image->id ?>">
          <img src="/_files_/products/<?= $image->file ?>" alt="">
        </a>
        <? if ($canManageProducts): ?>
        <div class="actions">
          <!-- <?= fff('Edytuj opis obrazu', 'pencil', "catalog/products/images/edit.php?product={$product->id}&id={$image->id}") ?> //-->
          <?= fff('Usuń obraz', 'bullet_cross', "catalog/products/images/delete.php?product={$product->id}&id={$image->id}", null, 'delete') ?>
        </div>
        <? endif ?>
      <? endforeach ?>
    </ul>
    <? if ($canManageProducts): ?>
    <input id="productImageFile" name=file type=file>
    <? endif ?>
  </div>
</div>
