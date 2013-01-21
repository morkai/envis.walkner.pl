<?php

include_once __DIR__ . '/_common.php';

no_access_if_not_allowed('documentation*');

$where = 'WHERE 1=1';

if (!$_SESSION['user']->isSuper())
{
	$where = ' AND doc.machine IN(null,' . list_quoted($_SESSION['user']->getAllowedMachineIds()) . ')';
}

$product = !empty($_GET['product']) && is_numeric($_GET['product']) ? $_GET['product'] : 0;

if ($product)
{
  $product = fetch_one('SELECT id, name FROM catalog_products WHERE id=? LIMIT 1', array(1 => $product));

  bad_request_if(empty($product));

  $product->docs = fetch_array('SELECT documentation AS `key`, 1 AS `value` FROM catalog_product_documentations WHERE product=?', array(1 => $product->id));
}

$query = <<<SQL
SELECT
  doc.id,
  doc.title,
  dev.id AS deviceId,
  m.id AS machineId,
  f.id AS factoryId,
  dev.name AS device,
  m.name AS machine,
  f.name AS factory,
  (SELECT DISTINCT GROUP_CONCAT(i.orderNumber) FROM issues i WHERE i.relatedFactory=f.id AND i.relatedMachine=m.id AND i.relatedDevice=dev.id) AS orders
FROM documentations doc
LEFT JOIN engines dev
	ON dev.id=doc.device
LEFT JOIN machines m
	ON m.id=doc.machine
LEFT JOIN factories f
	ON f.id=m.factory
{$where}
ORDER BY factory, machine, device ASC
SQL;

$docs = fetch_all($query);

$hasAnyDocs = !empty($docs);

$canAdd = is_allowed_to('documentation/add');
$canEdit = is_allowed_to('documentation/edit');
$canDelete = is_allowed_to('documentation/delete');
$canManageProducts = is_allowed_to('catalog/manage');

$prev = null;

$orderLinkTpl = http_build_query(array(
  'v' => array(
    'c' => array('subject', 'orderNumber', 'orderInvoice'),
    'o' => array(
      'f' => array('updatedAt'),
      'd' => array('-1'),
    ),
    'p' => '20',
    'f' => array(
      'j' => '0',
      'c' => array('orderNumber'),
      'i' => array('equals'),
      'v' => array(''),
    )
  )
));
$orderLinks = function($orders) use($orderLinkTpl)
{
  $result = '';
  $orders = explode(',', trim($orders));

  foreach ($orders as $order)
  {
    $result .= ', <a href="/service/?docs=1&amp;' . $orderLinkTpl . trim($order) . '">' . $order . '</a>';
  }

  if ($result === '')
  {
    return '-';
  }

  return substr($result, 2);
};

?>
<? if ($canAdd): ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<li><a href="<?= url_for('documentation/add.php') ?>">Dodaj nową dokumentację</a>
</ul>
<? append_slot() ?>
<? endif ?>

<? begin_slot('head') ?>
<style>
  tbody th
  {
    padding-top: 1.5em;
    border-bottom: 2px solid #246;
    font-weight: normal;
    font-size: 1em;
  }
  #filters td
  {
    padding: .25em .5em .5em 0;
  }
  #filters > :last-child
  {
    padding-right: 0;
  }
  #filters input
  {
    width: 100%;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  var $docs = $('.docs');

  $docs.makeClickable();

  $('#filter').bind('submit', function()
  {
    var title = $.trim($('#filter-title').val()).toLowerCase();
    var issues = $.trim($('#filter-issues').val());

    if (title === '' && issues === '')
    {
      $('tr.doc').show();
      $docs.show();
    }
    else
    {
      $docs.each(function()
      {
        var tbody = $(this);
        var docs = tbody.find('tr.doc');
        var visibleDocs = docs.length;

        docs.each(function()
        {
          var docEl = $(this);

          if (title !== '' && docEl.find('td.title').text().toLowerCase().indexOf(title) !== -1)
          {
            return docEl.show();
          }

          if (issues !== '' && docEl.find('td.issues').text().indexOf(issues) !== -1)
          {
            return docEl.show();
          }

          visibleDocs -= 1;
          docEl.hide();
        });

        tbody[visibleDocs ? 'show' : 'hide']();
      });
    }

    return false;
  });

  $docs.on('click', '.linkProduct a', function()
  {
    var $linkProduct = $(this).parent();

    $.ajax({
      type: 'post',
      url: this.href,
      success: function()
      {
        $linkProduct.fadeOut(function()
        {
          $linkProduct.siblings('.unlinkProduct').fadeIn();
        });
      }
    });

    return false;
  });

  $docs.on('click', '.unlinkProduct a', function()
  {
    var $unlinkProduct = $(this).parent();

    $.ajax({
      type: 'post',
      url: this.href,
      success: function()
      {
        $unlinkProduct.fadeOut(function()
        {
          $unlinkProduct.siblings('.linkProduct').fadeIn();
        });
      }
    });

    return false;
  });
});
</script>
<? append_slot() ?>

<? decorate("Lista dokumentacji") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">
      <? if ($product): ?>
      Przypisywanie dokumentacji do produktu: <?= e($product->name) ?>
      <? else: ?>
      Dokumentacje
      <? endif ?>
		</h1>
	</div>
	<div class="block-body">
		<? if ($hasAnyDocs): ?>
    <form id="filter" method="post" action="">
		<table>
			<thead>
				<tr>
					<th>Tytuł
					<th>Powiązane zlecenia
					<th>Akcje
        </tr>
        <tr id="filters">
          <td><input id="filter-title" type="text">
          <td><input id="filter-issues" type="text">
          <td><input type="submit" value="Filtruj"></td>
        </tr>
      <? foreach ($docs as $doc): ?>
      <? if (!$prev || ($prev->factoryId !== $doc->factoryId || $prev->machineId !== $doc->machineId || $prev->deviceId || $doc->deviceId)): ?>
      <tbody class="docs">
        <tr>
          <th colspan="3"><?= doc_features($doc) ?>
      <? endif ?>
        <tr class="doc">
          <td class="title clickable"><a href="<?= url_for("documentation/view.php?id={$doc->id}") ?>"><?= $doc->title ?></a>
          <td class="issues"><?= $orderLinks($doc->orders) ?>
          <td class="actions">
            <ul>
              <? if ($canManageProducts && $product): ?>
              <li class="linkProduct" style="<?= isset($product->docs[$doc->id]) ? 'display: none' : '' ?>"><?= fff('Przypisz do produktu', 'link_add', "documentation/link.php?product={$product->id}&id={$doc->id}") ?>
              <li class="unlinkProduct" style="<?= isset($product->docs[$doc->id]) ? '' : 'display: none' ?>"><?= fff('Usuń przypisanie do produktu', 'link_delete', "documentation/unlink.php?product={$product->id}&id={$doc->id}") ?>
              <? endif ?>
              <li><?= fff('Pokaż', 'book', "documentation/view.php?id={$doc->id}") ?>
              <? if ($canEdit): ?><li><?= fff('Edytuj', 'book_edit', "documentation/edit.php?id={$doc->id}") ?><? endif ?>
              <? if ($canDelete): ?><li><?= fff('Usuń', 'book_delete', "documentation/delete.php?id={$doc->id}") ?><? endif ?>
            </ul>
      <? $prev = $doc ?>
      <? endforeach ?>
		</table>
    </form>
		<? else: ?>
		<p>Aktualnie nie ma żadnych dokumentacji.</p>
		<? if ($canAdd): ?><p><a href="<?= url_for('documentation/add.php') ?>">Dodaj nową dokumentację</a>.</p><? endif ?>
		<? endif ?>
	</div>
</div>
