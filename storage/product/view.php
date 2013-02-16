<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('storage*');

$query = <<<SQL
SELECT
  s.name AS storageName,
  s.owner,
  p.*,
  p.quantity * p.price AS value
FROM storage_products p
INNER JOIN storages s
  ON s.id=p.storage
WHERE p.id=:id
SQL;

$product = fetch_one($query, array(':id' => $_GET['id']));

not_found_if(empty($product));

no_access_if_not($_SESSION['user']->isSuper() || ($product->owner == $_SESSION['user']->getId()));

escape_vars($product->name, $product->storageName, $product->supplier, $product->contact);

$product->supplier = empty($product->supplier) ? '-' : nl2br($product->supplier);
$product->contact = empty($product->contact) ? '-' : nl2br($product->contact);

?>
<? begin_slot('head') ?>
<style>

</style>
<? append_slot() ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <li><a href="<?= url_for('storage/product/edit.php?id=' . $product->id) ?>">Edytuj produkt</a>
  <li><a href="<?= url_for('storage/product/delete.php?id=' . $product->id) ?>">Usuń produkt</a>
</ul>
<? append_slot() ?>

<? decorate("Produkt z magazynu") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Produkt &lt;<?= $product->name ?>&gt; z magazynu &lt;<?= $product->storageName ?>&gt;</h1>
  </div>
  <div class="block-body">
    <dl>
      <dt>Magazyn
      <dd><a href="<?= url_for("storage/view.php?id={$product->storage}") ?>"><?= $product->storageName ?></a>
      <dt>Produkt
      <dd><?= $product->name ?>
      <dt>Indeks
      <dd><?= $product->index ?>
      <dt>Cena za sztukę
      <dd><?= $product->price ?> zł
      <dt>Ilość
      <dd><?= $product->quantity ?>
      <dt>Wartość
      <dd><?= $product->value ?> zł
      <dt>Dostawca
      <dd><?= $product->supplier ?>
      <dt>Kontakt
      <dd><?= $product->contact ?>
    </dl>
  </div>
</div>
