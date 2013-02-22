<?php

$bypassAuth = false;

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

$product = catalog_get_card_product($_GET['id']);

not_found_if(empty($product));

$product->pages = fetch_all('SELECT * FROM catalog_card_pages WHERE product=? ORDER BY position ASC', array(1 => $product->id));

if (empty($product->pages))
{
  $frontPage = array(
    'product' => $product->id,
    'position' => 1,
    'layout' => 'qrFrontPage',
    'contents' => markdown($product->description)
  );

  exec_insert('catalog_card_pages', $frontPage);

  $frontPage['id'] = get_conn()->lastInsertId();

  $product->pages[] = (object)$frontPage;
}

$canManageProducts = is_allowed_to('catalog/manage');

$layouts = catalog_get_card_layouts();

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= e($product->name) ?> - Karta katalogowa</title>
  <? if ($canManageProducts): ?>
  <link rel="stylesheet" href="<?= url_for_media('jquery-ui/1.8.23/css/smoothness/jquery-ui.css') ?>">
  <link rel="stylesheet" href="<?= url_for("/catalog/products/card/_static_/ckeditor.css") ?>">
  <? endif ?>
  <link rel="stylesheet" href="<?= url_for("/catalog/products/card/_static_/main.css") ?>">
  <link rel="stylesheet" href="<?= url_for("/catalog/products/card/_static_/print.css") ?>" media="print">
  <style>
    div.message {
      margin: 1em 0;
      padding: 0.75em 0.75em 0.75em 3.5em;
      background: transparent 0.5em 50% no-repeat;
      border: 1px solid transparent;
    }
    div.message.success
    {
      background-color: #EAF7D9;
      background-image: url(../../../_static_/img/success.png);
      border-color: #BBDF8D;
      background-position: .75em 50%;
    }
    div.message.error
    {
      background-color: #FFD1D1;
      background-image: url(../../../_static_/img/error.png);
      border-color: #F8ACAC;
      background-position: .75em 50%;
    }
    div.message.warning
    {
      background-color: #FFF5CC;
      background-image: url(../../../_static_/img/warning.png);
      background-position: 1em 50%;
      border-color: #F2DD8C;
    }
    div.message.info
    {
      background-color: #E8F6FF;
      background-image: url(../../../_static_/img/info.png);
      background-position: 1em 50%;
      border-color: #B8E2FB;
    }
  </style>
</head>
<body>

<div id="pages">
<? foreach ($product->pages as $page): ?>
  <div class="pageContainer" data-page="<?= $page->id ?>">
    <? if ($canManageProducts): ?>
    <ul class="actions">
      <li><?= fff('Przesuń stronę wyżej', 'arrow_up', '/catalog/products/card/moveUp.php', null, 'movePageUp') ?>
      <li><?= fff('Przesuń stronę niżej', 'arrow_down', '/catalog/products/card/moveDown.php', null, 'movePageDown') ?>
      <li><?= fff('Importuj szablon zawartości', 'page_white_edit', '/catalog/products/card/templates/import.php', null, 'importTemplate') ?>
      <li><?= fff('Eksportuj szablon zawartości', 'page_white_save', '/catalog/products/card/templates/export.php', null, 'exportTemplate') ?>
      <li><?= fff('Usuń stronę', 'page_white_delete', '/catalog/products/card/delete.php', null, 'deletePage') ?>
    </ul>
    <? endif ?>
    <? include $layouts[$page->layout]->templateFile ?>
    <? if ($canManageProducts): ?>
    <p class="addPage" title="Wstaw nową stronę"><?= fff('Wstaw nową stronę', 'page_white_add') ?></p>
    <? endif ?>
  </div>
<? endforeach ?>
</div>

<ol id="toc">
  <? foreach ($product->pages as $i => $_): ?>
  <li><?= $i + 1 ?>
  <? endforeach ?>
</ol>

<script src="<?= url_for_media('jquery/1.8.1/jquery.min.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/scrollTo/1.4.3.1/jquery.scrollTo.min.js') ?>"></script>
<script>
$(function()
{
  $('#toc').on('click', 'li', function()
  {
    var pageIndex = parseInt(this.innerHTML) - 1;
    var pageEl = document.getElementById('pages').children[pageIndex];

    $.scrollTo(pageEl, 400);
  });
});
</script>

<? if ($canManageProducts): ?>
<div id="layouts">
  <select id="layout">
    <? foreach ($layouts as $layoutName => $layout): ?>
    <option value="<?= $layoutName ?>" data-image="<?= url_for("/catalog/products/card/layouts/{$layoutName}.png") ?>" data-description="<?= e($layout->description) ?>"><?= e($layout->title) ?></option>
    <? endforeach ?>
  </select>
  <img id="layoutImage" src="<?= url_for_media("/img/141x200.gif", true) ?>" alt="Szablon strony" width="141" height="200">
  <p id="layoutDescription">Wybierz szablon...</p>
</div>
<div id="importTemplate"></div>
<div id="exportTemplate"></div>
<script src="<?= url_for_media('jquery-ui/1.8.23/js/jquery-ui.min.js') ?>"></script>
<script src="<?= url_for_media('/ckeditor/4.0beta/ckeditor.js') ?>"></script>
<script src="<?= url_for("/catalog/products/card/_static_/ckeditor.js") ?>"></script>
<script src="<?= url_for("/catalog/products/card/_static_/actions.js") ?>"></script>
<script src="<?= url_for("/catalog/products/card/_static_/partsPage.js") ?>"></script>
<script>
$(function()
{
  var options = {
    productId: <?= $product->id ?>,
    updateUrl: '<?= url_for("/catalog/products/card/update.php") ?>',
    renderUrl: '<?= url_for("/catalog/products/card/layouts/render.php") ?>',
    importTemplateUrl: '<?= url_for("/catalog/products/card/templates/import.php") ?>',
    exportTemplateUrl: '<?= url_for("/catalog/products/card/templates/export.php") ?>'
  };

  setUpCkeditor(options);
  setUpActions(options);
  setUpPartsPage(options);

  var $pages = $('#pages');

  fitProductName();

  $pages.on('pageDeleted', rebuildToc);

  $pages.on('pageAdded', function(e, $pageContainer)
  {
    rebuildToc();

    var layout = $pageContainer.find('.page').attr('data-layout');

    if (layout === 'frontPage')
    {
      fitProductName();
    }
  });

  function fitProductName()
  {
    $pages.find('.page[data-layout="frontPage"] .productName').each(function()
    {
      var $productName = $(this);
      var productFontSize = parseInt($productName.css('font-size'));

      while ($productName.height() > 175)
      {
        $productName.css('font-size', --productFontSize + 'px');
      }
    });
  }

  function rebuildToc()
  {
    var $toc = $('#toc').empty();

    var p = 1;
    var c = $pages.children().length;

    for (; p <= c; ++p)
    {
      $toc.append('<li>' + p);
    }
  }
});
</script>
<? endif ?>
</body>
</html>
