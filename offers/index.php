<?php

include_once './_common.php';

no_access_if_not_allowed('offers*');

include_once '../_lib_/PagedData.php';

$filters = array(
  'all' => 'Wszystkie',
  'unsent' => 'Niewysłane',
  'accepted' => 'Zaakceptowane',
  'no-order' => 'Brak zamówienia',
  'no-po' => 'Brak PO',
  'no-invoice' => 'Brak FV',
  'started' => 'Rozpoczęte',
  'finished' => 'Zakończone'
);

$page = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$q = empty($_GET['q']) ? '' : trim(preg_replace('/\s+/', ' ', $_GET['q']));
$f = empty($_GET['f']) || empty($filters[$_GET['f']]) ? 'all' : $_GET['f'];
$perPage = 23;

$where = '';

if ($q !== '' || $f !== 'all')
{
  $where .= 'WHERE 1=1';

  switch ($f)
  {
    case 'unsent':
      $where .= " AND o.cancelled=0 AND o.sentTo=''";
      break;

    case 'no-order':
      $where .= " AND o.cancelled=0 AND o.closedAt IS NOT NULL AND (o.issue IS NULL OR ((i.orderNumber='' OR i.orderNumber IS NULL) AND i.status<>3))";
      break;

    case 'no-po':
      $where .= " AND o.issue IS NOT NULL AND (i.orderNumber='' OR i.orderNumber IS NULL) AND i.status<>3";
      break;

    case 'no-invoice':
      $where .= " AND o.issue IS NOT NULL AND (i.orderInvoice='' OR i.orderInvoice IS NULL) AND i.status<>3";
      break;

    case 'accepted':
      $where .= " AND o.issue IS NOT NULL AND i.status IN(0, 1)";
      break;

    case 'started':
      $where .= " AND o.issue IS NOT NULL AND i.status IN(2, 5, 6, 7, 8, 9)";
      break;

    case 'finished':
      $where .= " AND o.issue IS NOT NULL AND i.status IN(3, 4)";
      break;
  }

  if ($q !== '')
  {
    $where .= ' AND ';

    if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $q))
    {
      $where .= " (o.createdAt='{$q}' OR o.closedAt='{$q}')";
    }
    else if (preg_match('/^SEK[0-9]/i', $q))
    {
      $where .= " o.number LIKE " . get_conn()->quote("%{$q}%");
    }
    else if (preg_match('/^([0-9]{5,6}|[0-9]{10})$/', $q))
    {
      $where .= " i.orderNumber LIKE " . get_conn()->quote("%{$q}%");
    }
    else if (preg_match('/^FV\s*[0-9]{1,4}\/[0-9]{4}/i', $q))
    {
      $fv = preg_replace('/^FV\s*/', '', $q);
      $where .= " i.orderInvoice LIKE " . get_conn()->quote("%{$fv}%");
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
}

$offers = new PagedData($page, $perPage);

$query = <<<SQL
SELECT COUNT(*) AS `count`
FROM offers o
LEFT JOIN issues i
  ON i.id=o.issue
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
  o.sentTo,
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
$currentYear = date('Y');

foreach ($items as $item)
{
  $offerIds[] = $item->id;

  $item->old = substr($item->createdAt, 0, 4) !== $currentYear;
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
$canEditIssues = is_allowed_to('service/edit');
$href = url_for("offers/") . "?" . http_build_query(array('f' => $f, 'q' => $q));

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
#offersList img {
  vertical-align: middle;
}
.is-cancelled {
  text-decoration: line-through;
}
#query {
  display: flex;
}
#query select {
  font-size: 1em;
  margin-left: 1em;
}
#query input {
  width: 200px;
}
.is-old {
  color: #999;
}
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#offersList').makeClickable();

  $('#filter').on('change', function()
  {
    $('#query').submit();
  });
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
        <select id="filter" name="f">
          <? foreach ($filters as $k => $v): ?>
          <option value="<?= $k ?>" <?= $k === $f ? 'selected' : '' ?>><?= $v ?></option>
          <? endforeach ?>
        </select>
      </form>
    </li>
  </ul>
  <div class="block-body">
    <? if (!$offers->isEmpty()): ?>
    <table>
      <thead>
        <tr>
          <th class="min">Numer
          <th class="min">Data
          <th class="min">Status
          <th class="min">PO
          <th class="min">FV
          <th>Tytuł
          <!-- <th class="min">Akcje //-->
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
          <td class="min <?= $offer->old ? 'is-old' : '' ?>"><?= $offer->closedAt ? $offer->closedAt : '-' ?>
          <td class="min">
            <? if ($offer->issue): ?>
              <? if (empty($offer->sentTo)): ?>
                <a href="<?= url_for("service/view.php?id={$offer->issue}") ?>" title="Otwórz zamówienie">Niewysłane</a> <?= fff('Wyślij', 'email', "offers/close.php?id={$offer->id}") ?>
              <? else: ?>
                <a href="<?= url_for("service/view.php?id={$offer->issue}") ?>" title="Otwórz zamówienie"><?= $statuses[$offer->status] ?></a>
              <? endif ?>
            <? elseif ($offer->closedAt): ?>
              <i>Brak zamówienia</i>
            <? else: ?>
              <i>Niewysłane</i>
            <? endif ?>
          <td class="min">
            <? if ($offer->issue): ?>
              <? if ($offer->orderNumber): ?>
                <?= fff('Edytuj zamówienie', 'pencil', "service/edit.php?id={$offer->issue}") ?>
                <a href="<?= url_for("service/view.php?id={$offer->issue}") ?>" title="Otwórz zamówienie"><?= $offer->orderNumber ?></a>
              <? elseif ($canEditIssues): ?>
                <?= fff_link('ustaw', 'pencil', "service/edit.php?id={$offer->issue}") ?>
              <? else: ?>
                -
              <? endif ?>
            <? elseif ($offer->closedAt): ?>
              <? if ($canEditIssues): ?>
                <?= fff_link('stwórz', 'add', "offers/order.php?offer={$offer->id}") ?>
              <? else: ?>
                -
              <? endif ?>
            <? elseif ($canClose): ?>
              <?= fff_link('wyślij', 'email', "offers/close.php?id={$offer->id}") ?>
            <? else: ?>
            -
            <? endif ?>
          <td class="min">
            <? if ($offer->issue): ?>
              <?= dash_if_empty($offer->orderInvoice) ?>
            <? else: ?>
              -
            <? endif ?>
          </td>
          <td class="clickable"><a href="<?= url_for("offers/view.php?id={$offer->id}") ?>"><?= $offer->title ?></a>
          <!--
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
          //-->
        <? endforeach ?>
      </tbody>
    </table>
    <? else: ?>
    <p>Nie znaleziono żadnych ofert dla zadanych kryteriów.</p>
    <? endif ?>
  </div>
</div>
