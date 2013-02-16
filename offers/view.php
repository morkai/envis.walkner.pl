<?php

include_once './_common.php';

no_access_if_not_allowed('offers*');

bad_request_if(empty($_GET['id']));

$query = <<<SQL
SELECT o.*
FROM offers o
WHERE o.id=?
LIMIT 1
SQL;

$offer = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($offer));

$offer->items = fetch_all('SELECT * FROM offer_items WHERE offer=? ORDER BY position ASC', array(1 => $offer->id));
$offer->closed = !empty($offer->closedAt);

$canAdd = is_allowed_to('offers/add');
$canDelete = is_allowed_to('offers/delete');
$canEdit = is_allowed_to('offers/edit');
$canClose = is_allowed_to('offers/close') && !$offer->closed;

?>

<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($canClose): ?>
  <li><a href="<?= url_for("offers/close.php?id={$offer->id}") ?>">Wyślij ofertę</a>
  <? endif ?>
  <? if ($canAdd): ?>
  <li><a href="<?= url_for("offers/copy.php?id={$offer->id}") ?>">Kopiuj ofertę</a>
  <? endif ?>
  <? if ($offer->closed): ?>
  <li><a href="<?= url_for("offers/export.php?id={$offer->id}&format=pdf") ?>">Eksportuj do PDF</a>
  <? endif ?>
  <li><a href="<?= url_for("offers/export.php?id={$offer->id}&format=html") ?>">Eksportuj do HTML</a>
  <? if ($canDelete): ?>
  <li><a href="<?= url_for("offers/delete.php?id={$offer->id}") ?>">Usuń ofertę</a>
  <? endif ?>
</ul>
<? append_slot() ?>

<? include 'view_' . ($offer->closed ? 'closed' : 'open') . '.php' ?>
