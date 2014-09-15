<?php

include_once './_common.php';

no_access_if_not_allowed('offers*');

include_once '../_lib_/PagedData.php';

$page = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$perPage = 15;

$offers = new PagedData($page, $perPage);

$totalItems = fetch_one('SELECT COUNT(*) AS `count` FROM offers')->count;

$query = <<<SQL
SELECT
  o.id,
  o.title,
  o.number,
  o.createdAt,
  o.closedAt,
  o.issue,
  o.cancelled,
  i.status
FROM offers o
LEFT JOIN issues i
  ON i.id=o.issue
ORDER BY id DESC
SQL;

$items = fetch_all(sprintf("%s LIMIT %s,%s", $query, $offers->getOffset(), $offers->getPerPage()));

$offers->fill($totalItems, $items);

$canAdd = is_allowed_to('offers/add');
$canDelete = is_allowed_to('offers/delete');
$canClose = is_allowed_to('offers/close');
$canManageTemplates = is_allowed_to('offers/templates');

?>

<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($canAdd): ?>
  <li><a href="<?= url_for('offers/add.php') ?>">Dodaj nową ofertę</a>
  <? endif ?>
  <? if ($canManageTemplates): ?>
  <li><a href="<?= url_for('offers/templates') ?>">Zarządzaj szablonami</a>
  <? endif ?>
</ul>
<? append_slot() ?>

<? begin_slot('head') ?>
<style>
.is-cancelled {
  text-decoration: line-through;
}
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#offersList').makeClickable();
});
</script>
<? append_slot() ?>

<? decorate("Oferty") ?>

<div class="block">
  <ul class="block-header">
    <h1 class="block-name">Oferty</h1>
  </ul>
  <div class="block-body">
    <? if (!$offers->isEmpty()): ?>
    <table>
      <thead>
        <tr>
          <th>Numer
          <th>Tytuł
          <th>Data stworzenia
          <th>Data wysłania
          <!-- <th>Status zgłoszenia //-->
          <th>Akcje
      </thead>
      <tfoot>
        <tr>
          <td colspan="99" class="table-options">
            <?= $offers->render(url_for("offers/?")) ?>
      </tfoot>
      <tbody id="offersList">
        <? foreach ($offers as $offer): ?>
        <tr class="<?= $offer->cancelled ? 'is-cancelled' : '' ?>">
          <td><?= $offer->number ?>
          <td class="clickable"><a href="<?= url_for("offers/view.php?id={$offer->id}") ?>"><?= $offer->title ?></a>
          <td><?= $offer->createdAt ?>
          <td><?= $offer->closedAt ? $offer->closedAt : '-' ?>
          <!--
          <td <? if ($offer->issue): ?>class="clickable" title="Pokaż zgłoszenie"<? endif ?>>
            <? if ($offer->issue): ?>
            <a href="<?= url_for("service/view.php?id={$offer->issue}") ?>"><?= $statuses[$offer->status] ?></a>
            <? else: ?>
            -
            <? endif ?>
          //-->
          <td class="actions">
            <ul>
              <li><?= fff('Pokaż', 'page_white', "offers/view.php?id={$offer->id}") ?>
              <? if ($canClose && !$offer->closedAt && !$offer->cancelled): ?>
              <li><?= fff('Wyślij', 'page_white_go', "offers/close.php?id={$offer->id}") ?>
              <? endif ?>
              <? if ($canAdd): ?>
              <li><?= fff('Kopiuj', 'page_white_copy', "offers/copy.php?id={$offer->id}") ?>
              <? endif ?>
              <li><?= fff('Eksportuj do HTML', 'page_white_world', "offers/export.php?id={$offer->id}&format=html") ?>
              <? if ($offer->closedAt): ?>
              <li><?= fff('Eksportuj do PDF', 'page_white_acrobat', "offers/export.php?id={$offer->id}&format=pdf") ?>
              <? endif ?>
              <? if ($canDelete): ?>
              <li><?= fff('Usuń', 'page_white_delete', 'offers/delete.php?id=' . $offer->id) ?>
              <? endif ?>
            </ul>
        <? endforeach ?>
      </tbody>
    </table>
    <? else: ?>
    <p>Nie znaleziono żadnych ofert dla zadanych kryteriów.</p>
    <? endif ?>
  </div>
</div>
