<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('storage*');

$query = <<<SQL
SELECT *
FROM storages
WHERE id=:id
SQL;

$storage = fetch_one($query, array(':id' => $_GET['id']));

not_found_if(empty($storage));

no_access_if_not($_SESSION['user']->isSuper() || ($storage->owner == $_SESSION['user']->getId()));

escape_var($storage->name);

$products = fetch_all('SELECT * FROM storage_products WHERE storage=:storage ORDER BY name ASC', array(':storage' => $storage->id));

$hasAnyProducts = !empty($products);

?>
<? begin_slot('head') ?>
<style>
  .hovered td { color: #246; border-color: #246; }
  tr.expanded td { border-bottom: 0; }
  .more { cursor: pointer; }
  .info td { padding-top: 0; }
  .info { display: none; }
  dl, dl :last-child { margin-bottom: 0; }
</style>
<? append_slot() ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <li><a href="<?= url_for('storage/product/add.php?storage=' . $storage->id) ?>">Dodaj produkt</a>
  <li><a href="<?= url_for('storage/import.php?id=' . $storage->id) ?>">Importuj produkty</a>
  <li><a href="<?= url_for('storage/export.php?id=' . $storage->id) ?>">Eksportuj produkty</a>
  <li><a href="<?= url_for('storage/edit.php?id=' . $storage->id) ?>">Edytuj magazyn</a>
  <li><a href="<?= url_for('storage/delete.php?id=' . $storage->id) ?>">Usuń magazyn</a>
</ul>
<? append_slot() ?>

<? decorate("Magazyn") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Magazyn &lt;<?= $storage->name ?>&gt;</h1>
  </div>
  <div class="block-body">
    <? if ($hasAnyProducts): ?>
    <table>
      <thead>
        <tr>
          <th>Indeks
          <th>Nazwa
          <th>Ilość
          <th>Cena szt.
          <th>Akcje
      <tbody>
        <? foreach ($products as $product): ?>
        <tr class="summary">
          <td class="more"><?= $product->index ?>
          <td class="more"><?= $product->name ?>
          <td><?= $product->quantity ?>
          <td><?= $product->price ?>
          <td class="actions">
            <ul>
              <li><?= fff('Pokaż', 'shape_square', 'storage/product/view.php?id=' . $product->id) ?>
              <li><?= fff('Edytuj', 'shape_square_edit', 'storage/product/edit.php?id=' . $product->id) ?>
              <li><?= fff('Usuń', 'shape_square_delete', 'storage/product/delete.php?id=' . $product->id) ?>
            </ul>
        <tr class="info">
          <td colspan="5">
            <dl>
              <dt>Dostawca
              <dd><?= empty($product->supplier) ? '-' : nl2br($product->supplier) ?>
              <dt>Kontakt
              <dd><?= empty($product->contact) ? '-' : nl2br($product->contact) ?>
            </dl>
        <? endforeach ?>
    </table>
    <? else: ?>
    <p>Aktualnie nie ma żadnych produktów w tym magazynie.</p>
    <? endif ?>
  </div>
</div>
<? begin_slot('js') ?>
<script>
$(function()
{
  var expanded;

  function collapse(row)
  {
    row.removeClass('expanded').next().hide();
  }

  function expand(row)
  {
    //if (expanded) { collapse(expanded); expanded = null; }

    row.addClass('expanded').next().css('display', 'table-row');

    expanded = row;
  }

  function toggleHovered()
  {
    var row = $(this);

    (row.hasClass('summary') ? row.next() : row.prev()).toggleClass('hovered');
  }

  $('.summary').mouseover(toggleHovered).mouseout(toggleHovered);
  $('.info').mouseover(toggleHovered).mouseout(toggleHovered);

  $('.more').click(function()
  {
    var row = $(this).parent();

    if (row.hasClass('expanded'))
      collapse(row);
    else
      expand(row);
  });
});
</script>
<? append_slot() ?>
