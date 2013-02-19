<div id="product" class="block">
  <ul class="block-header">
    <li><h1 class="block-name">Karta produktu</h1>
    <? if ($canManageProducts): ?>
    <li><?= fff('Edytuj produkt', 'page_edit', "catalog/products/edit.php?id={$product->id}") ?>
    <li><?= fff('Usuń produkt', 'page_delete', "catalog/products/delete.php?id={$product->id}") ?>
    <? endif ?>
    <li><?= fff('Pokaż kartę katalogową', 'page_white', "catalog/products/card/?id={$product->id}") ?>
  </ul>
  <div class="block-body">
    <div class="yui-gb">
      <div class="yui-u first">
        <table class="attributes">
          <tr>
            <th>ID:
            <td><?= $product->id ?>
          <tr>
            <th>Nr:
            <td><?= e($product->nr) ?>
          <tr>
            <th>Typ:
            <td><?= dash_if_empty($product->type) ?>
          <tr>
            <th>Rodzaj:
            <td>
              <? if (empty($product->kind)): ?>
              -
              <? else: ?>
              <?= $product->kindNr ?> - <?= e($product->kindName) ?>
              <? endif ?>
          <tr>
            <th>Wykonawca:
            <td>
              <? if (empty($product->manufacturer)): ?>
              -
              <? else: ?>
              <?= $product->manufacturerNr ?> - <?= e($product->manufacturerName) ?>
              <? endif ?>
          <tr>
            <th>Rewizja:
            <td><?= $product->revision ?>
          <tr>
            <th>Data produkcji:
            <td><?= dash_if_empty($product->productionDate) ?>
        </table>
      </div>
      <div class="yui-u">
        <table class="attributes">
          <tr>
            <th>Nazwa:
            <td><?= e($product->name) ?>
          <tr>
            <th>Kategoria:
            <td>
              <? foreach ($categoryPath as $pathCategory): ?>
              &gt; <a href="<?= url_for("catalog/?category={$pathCategory->id}") ?>"><?= e($pathCategory->name) ?></a><br>
              <? endforeach ?>
        </table>
      </div>
      <div class="yui-u">
        <table class="attributes">
          <tr>
            <th>Czas stworzenia:
            <td><?= date('Y-m-d, H:i', $product->createdAt) ?>
          <tr>
            <th>Czas aktualizacji:
            <td><?= date('Y-m-d, H:i', $product->updatedAt) ?>
          <tr>
            <th>Publiczny:
            <td><?= $product->public ? 'Tak' : 'Nie' ?>
          <tr>
            <th>QR Code:
            <td><img src="http://chart.apis.google.com/chart?chs=110x110&cht=qr&choe=UTF-8&chld=L|0&chl=<?= urlencode("http://walkner.pl/p/{$product->nr}") ?>" width="110" height="110" alt="QR Code">
        </table>
      </div>
    </div>
    <div class="product-description">
      <? if (empty($product->description)): ?>
      <em>Brak opisu produktu.</em>
      <? else: ?>
      <?= markdown($product->description) ?>
      <? endif ?>
    </div>
    <div class="product-markings">
      <? foreach ($product->markings as $marking): ?>
      <img src="<?= $marking->src ?>" alt="<?= e($marking->name) ?>" title="<?= e($marking->name) ?>" height="50">
      <? endforeach ?>
    </div>
  </div>
</div>

<div id="productTabs">
  <ul>
    <li><a href="#issues">Powiązane zgłoszenia</a>
    <li><a href="#gallery">Galeria</a>
    <li><a href="#docs">Dokumentacje</a>
    <li><a href="#files">Pliki</a>
  </ul>
  <div id="issues">
    <? if (empty($product->issues)): ?>
    <p>Aktualnie nie ma żadnych zgłoszeń powiązanych z tym produktem.</p>
    <? else: ?>
    <table>
      <thead>
      <tr>
        <th>Temat</th>
        <th>Status</th>
        <th>Nr zamówienia</th>
        <th>Nr faktury</th>
      </tr>
      </thead>
      <tbody>
        <? foreach ($product->issues as $issue): ?>
      <tr>
        <td class="clickable"><a href="<?= url_for("service/view.php?id={$issue->id}") ?>"><?= e($issue->subject) ?></a></td>
        <td><?= $statuses[$issue->status] ?></td>
        <td><?= dash_if_empty($issue->orderNumber) ?></td>
        <td><?= dash_if_empty($issue->orderInvoice) ?></td>
      </tr>
        <? endforeach ?>
      </tbody>
    </table>
    <? endif ?>
  </div>
  <div id="gallery">
    <ul id=productImages>
      <? foreach ($product->images as $image): ?>
      <li>
        <a class="thumb <?= $image->id === $product->image ? 'default' : '' ?>" href="<?= url_for("/_files_/products/{$image->file}") ?>" rel="lightbox[<?= $product->id ?>]" title="<?= e($image->description) ?>" data-id="<?= $image->id ?>">
          <img src="<?= url_for("/catalog/products/images/thumb.php?file={$image->file}") ?>" alt="">
        </a>
        <? if ($canManageProducts): ?>
        <div class="actions">
          <!-- <?= fff('Edytuj opis obrazu', 'pencil', "catalog/products/images/edit.php?product={$product->id}&id={$image->id}") ?> //-->
          <?= fff('Ustaw jako domyślne', 'bullet_tick', "catalog/products/images/default.php?product={$product->id}&id={$image->id}", null, 'default') ?>
          <?= fff('Usuń obraz', 'bullet_cross', "catalog/products/images/delete.php?product={$product->id}&id={$image->id}", null, 'delete') ?>
          <?= fff('Obróć o 90°', 'arrow_rotate_clockwise', "catalog/products/images/rotate.php?product={$product->id}&id={$image->id}", null, 'rotate') ?>
        </div>
        <? endif ?>
      <? endforeach ?>
    </ul>
    <? if ($canManageProducts): ?>
    <input id="productImageFile" name=file type=file>
    <? endif ?>
  </div>
  <div id="docs">
    <? foreach ($product->docs as $doc): ?>
    <div class="documentation">
      <h1>
        <a href="<?= url_for("documentation/view.php?id={$doc->id}") ?>"><?= e($doc->title) ?></a>
        <? if ($canEditDocumentation): ?><?= fff('Edytuj dokumentację', 'pencil', "documentation/edit.php?product={$product->id}&id={$doc->id}") ?><? endif ?>
        <? if ($canDeleteDocumentation): ?><?= fff('Usuń dokumentację', 'cross', "documentation/delete.php?product={$product->id}&id={$doc->id}") ?><? endif ?>
      </h1>
      <?= markdown($doc->description) ?>
      <? if (!empty($doc->files)): ?>
      <dl>
      <dt>Dostępne pliki:
      <? foreach ($doc->files as $file): ?>
      <dd><a href="<?= url_for("documentation/download.php?id={$file->id}") ?>"><?= e($file->name) ?></a>
      <? endforeach ?>
      </dl>
      <? endif ?>
    </div>
    <? endforeach ?>
    <div id="documentationsOptions">
      <? if (empty($product->docs)): ?>
      <p>Brak dokumentacji.</p>
      <? endif ?>
      <ul class="actions">
        <? if ($canAddDocumentation): ?><li><?= fff_link('Dodaj nową dokumentację', 'add',  "documentation/add.php?product={$product->id}") ?><? endif ?>
        <? if ($canManageProducts): ?><li><?= fff_link('Zarządzaj istniejącą dokumentacją', 'link',  "documentation/?product={$product->id}") ?><? endif ?>
      </ul>
    </div>
  </div>
  <div id="files">
    <table>
      <thead>
        <tr>
          <th>Nazwa
          <th>Typ
          <th>Czas wysłania
          <th>Akcje
      <tbody id="productFiles">
        <? if (empty($product->files)): ?>
        <tr class="nofiles">
          <td colspan=5>Brak plików.
        <? endif ?>
        <? foreach ($product->files as $file): ?>
        <tr>
          <td class="name clickable"><a href="<?= url_for("catalog/products/files/download.php?product={$product->id}&id={$file->id}") ?>"><?= e($file->name) ?></a>
          <td><?= $file->type ?>
          <td><?= date('Y-m-d, H:i', $file->uploadedAt) ?>
          <td class="actions">
            <? if ($canManageProducts): ?>
            <ul>
              <li><?= fff('Edytuj nazwę', 'bullet_edit', "catalog/products/files/edit.php?product={$product->id}&id={$file->id}", null, 'edit') ?>
              <li><?= fff('Usuń plik', 'bullet_cross', "catalog/products/files/delete.php?product={$product->id}&id={$file->id}", null, 'delete') ?>
            </ul>
            <? endif ?>
          <? endforeach ?>
    </table>
    <? if ($canManageProducts): ?>
    <input id="productFileUrl" name=fileUrl type=button value="Dodaj zewnętrzne pliki">
    <input id="productFile" name=file type=file>
    <form id="productFileUrlForm" action="<?= url_for("/catalog/products/files/upload.php?product={$product->id}") ?>" method="post">
      <ol class="form-fields">
        <li class="horizontal">
          <ol>
            <li>
              <label for="productFileUrlName">Nazwa:</label>
              <input id="productFileUrlName" name="name" type="text">
            </li>
            <li>
              <label for="productFileUrlFile">Adres URL:</label>
              <input id="productFileUrlFile" name="file" type="text">
            </li>
          </ol>
        </li>
        <li>
          <input type="submit" value="Dodaj zewnętrzny plik">
        </li>
      </ol>
    </form>
    <? endif ?>
  </div>
</div>

<script id="productImageTpl" type="template">
<li>
  <a class="thumb" href="<?= url_for('/_files_/products/${file}') ?>" rel="lightbox[<?= $product->id ?>]" title="${description}" data-id="${id}">
    <img src="<?= url_for('/catalog/products/images/thumb.php?file=${file}') ?>" alt="">
  </a>
  <div class="actions">
    <?= fff('Ustaw jako domyślne', 'bullet_tick', "catalog/products/images/default.php?product={$product->id}&id=\${id}", null, 'default') ?>
    <?= fff('Usuń obraz', 'bullet_cross', "catalog/products/images/delete.php?product={$product->id}&id=\${id}", null, 'delete') ?>
    <?= fff('Obróć o 90°', 'arrow_rotate_clockwise', "catalog/products/files/rotate.php?product={$product->id}&id=\${id}", null, 'rotate') ?>
  </div>
</script>

<script id="productFileTpl" type="template">
<tr>
  <td class="name clickable"><a href="<?= url_for("catalog/products/files/download.php?id=\${id}") ?>">${name}</a>
  <td>${type}
  <td>${uploadedAt}
  <td><a href="<?= url_for("user/view.php?id=\${uploader}") ?>">${uploaderName}</a>
  <td class="actions">
    <? if ($canManageProducts): ?>
    <ul>
      <li class="edit"><?= fff('Edytuj nazwę', 'bullet_edit', "catalog/products/files/edit.php?product={$product->id}&id=\${id}") ?>
      <li class="delete"><?= fff('Usuń plik', 'bullet_cross', "catalog/products/files/delete.php?product={$product->id}&id=\${id}") ?>
    </ul>
    <? endif ?>
</script>
