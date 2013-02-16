<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('catalog/manage');

$kinds = fetch_all('SELECT id, nr, name FROM catalog_product_kinds ORDER BY nr ASC');

$hasAnyKinds = !empty($kinds);

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <li><a href="<?= url_for("catalog/kinds/add.php") ?>">Dodaj nowy rodzaj produktów</a>
</ul>
<? append_slot() ?>

<? decorate("Rodzaje produktów - Katalog produktów") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Rodzaj produktów</h1>
  </div>
  <div class="block-body">
    <? if ($hasAnyKinds): ?>
    <table>
      <thead>
        <tr>
          <th>Nr
          <th>Nazwa
          <th>Akcje
        </tr>
      </thead>
      <tbody>
      <? foreach ($kinds as $kind): ?>
        <tr>
          <td><?= $kind->nr ?>
          <td><?= e($kind->name) ?>
          <td class="actions">
            <ul>
              <li><?= fff('Edytuj rodzaj produktu', 'pencil', "catalog/kinds/edit.php?id={$kind->id}") ?>
              <li><?= fff('Usuń rodzaj produktu', 'cross', "catalog/kinds/delete.php?id={$kind->id}") ?>
            </ul>
          </td>
        </tr>
      <? endforeach ?>
      </tbody>
    </table>
    <? else: ?>
    <p>Aktualnie nie ma żadnych rodzajów produktów.</p>
    <? endif ?>
  </div>
</div>
