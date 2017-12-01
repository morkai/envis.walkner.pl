<?php

include_once './_common.php';

no_access_if_not_allowed('offers*');

include_once '../_lib_/PagedData.php';

$page = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$q = empty($_GET['q']) ? '' : trim(preg_replace('/\s+/', ' ', $_GET['q']));
$perPage = 23;

$where = '';

if ($q !== '')
{
  $where .= 'WHERE';

  if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $q))
  {
    $where .= " o.createdAt='{$q}' OR o.closedAt='{$q}'";
  }
  else if (preg_match('/^SEK[0-9]/i', $q))
  {
    $where .= " o.number LIKE " . get_conn()->quote("%{$q}%");
  }
  else
  {
    $words = array_filter(explode(' ', $q), function($word) { return strlen($word) >= 3; });

    if (empty($words))
    {
      $where = '';
    }
    else
    {
      $words = implode(
        ' ',
        array_map(
          function($word) { return (preg_match('/^[A-Za-z0-9]/', $word) ? '+' : '') . $word; },
          $words
        )
      );

      $where .= " MATCH(o.search) AGAINST(" . get_conn()->quote($words) . " IN BOOLEAN MODE)";
    }
  }
}

$offers = new PagedData($page, $perPage);

$query = <<<SQL
SELECT COUNT(*) AS `count`
FROM offers o
{$where}
SQL;

$totalOffers = fetch_one($query)->count;

$query = <<<SQL
SELECT
  o.id,
  o.title,
  o.number,
  o.createdAt,
  o.closedAt,
  o.issue,
  o.cancelled,
  i.status,
  i.orderNumber,
  i.orderInvoice
FROM offers o
LEFT JOIN issues i
  ON i.id=o.issue
{$where}
ORDER BY o.createdAt DESC
SQL;

$items = fetch_all(sprintf("%s LIMIT %s,%s", $query, $offers->getOffset(), $offers->getPerPage()));

$offers->fill($totalOffers, $items);

$offerIds = array();

foreach ($items as $item)
{
  $offerIds[] = $item->id;
}

$offerIds = join(',', $offerIds);

$query = <<<SQL
SELECT o.offer, o.issue, i.status, i.orderNumber, i.orderInvoice
FROM offer_items o
INNER JOIN issues i ON i.id=o.issue
WHERE o.offer IN({$offerIds})
GROUP BY o.offer
SQL;

$issueList = empty($offerIds) ? array() : fetch_all($query);
$issueMap = array();

foreach ($issueList as $issue)
{
  $issue->status = $statuses[$issue->status];
  $issueMap[$issue->offer] = $issue;
}

$canAdd = is_allowed_to('offers/add');
$canDelete = is_allowed_to('offers/delete');
$canClose = is_allowed_to('offers/close');
$canManageTemplates = is_allowed_to('offers/templates');
$href = url_for("offers/") . "?" . http_build_query(array('q' => $q));

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
#offersList a {
  text-decoration: none;
}
.is-cancelled {
  text-decoration: line-through;
}
#query input {
  width: 200px;
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
    <li>
      <h1 class="block-name">Oferty</h1>
    </li>
    <li>
      <form id="query" action="<?= url_for("/offers/") ?>">
        <input type="text" name="q" value="<?= e(isset($_GET['q']) ? $_GET['q'] : '') ?>" autofocus placeholder="Szukaj...">
      </form>
    </li>
  </ul>
  <div class="block-body">
    <? if (!$offers->isEmpty()): ?>
    <table>
      <thead>
        <tr>
          <th class="min">Numer
          <th>Tytuł
          <th class="min">Data wysłania
          <th class="min">Zamówienie
          <th class="min">Faktura
          <th class="min">Akcje
      </thead>
      <tfoot>
        <tr>
          <td colspan="99" class="table-options">
            <?= $offers->render($href) ?>
      </tfoot>
      <tbody id="offersList">
        <? foreach ($offers as $offer): ?>
        <tr class="<?= $offer->cancelled ? 'is-cancelled' : '' ?>">
          <td class="min"><?= $offer->number ?>
          <td class="clickable"><a href="<?= url_for("offers/view.php?id={$offer->id}") ?>"><?= $offer->title ?></a>
          <td class="min"><?= $offer->closedAt ? $offer->closedAt : '-' ?>
          <td <? if ($offer->issue || !empty($issueMap[$offer->id])): ?>class="min clickable" title="Pokaż zgłoszenie"<? endif ?>>
            <? if ($offer->issue): ?>
            <a href="<?= url_for("service/view.php?id={$offer->issue}") ?>">
              <?= $offer->orderNumber ?> (<?= strtolower($statuses[$offer->status]) ?>)
            </a>
            <? elseif (!empty($issueMap[$offer->id])): ?>
            <a href="<?= url_for("service/view.php?id={$issueMap[$offer->id]->issue}") ?>">
              <?= $issueMap[$offer->id]->orderNumber ?> (<?= strtolower($issueMap[$offer->id]->status) ?>)
            </a>
            <? else: ?>
            -
            <? endif ?>
          <td class="min">
            <? if ($offer->issue): ?>
              <?= dash_if_empty($offer->orderInvoice) ?>
            <? elseif (!empty($issueMap[$offer->id])): ?>
              <?= dash_if_empty($issueMap[$offer->id]->orderInvoice) ?>
            <? else: ?>
              -
            <? endif ?>
          </td>
          <td class="min actions">
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
