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
          <img src="<?= url_for("/_files_/products/{$image->file}") ?>" alt="">
        </a>
        <? if ($canManageProducts): ?>
        <div class="actions">
          <!-- <?= fff('Edytuj opis obrazu', 'pencil', "catalog/products/images/edit.php?product={$product->id}&id={$image->id}") ?> //-->
          <?= fff('Ustaw jako domyślne', 'bullet_tick', "catalog/products/images/default.php?product={$product->id}&id={$image->id}", null, 'default') ?>
          <?= fff('Usuń obraz', 'bullet_cross', "catalog/products/images/delete.php?product={$product->id}&id={$image->id}", null, 'delete') ?>
        </div>
        <? endif ?>
      <? endforeach ?>
    </ul>
    <? if ($canManageProducts): ?>
    <input id="productImageFile" name=file type=file>
    <? endif ?>
  </div>
  <div id="docs">
    <p>TODO</p>
  </div>
  <div id="files">
    <p>TODO</p>
  </div>
</div>

<script id="productImageTpl" type="template">
<li>
  <a class="thumb" href="<?= url_for('/_files_/products/${file}') ?>" rel="lightbox[<?= $product->id ?>]" title="\${description}" data-id="\${id}">
    <img src="<?= url_for('/_files_/products/${file}') ?>" alt="">
  </a>
  <div class="actions">
    <?= fff('Ustaw jako domyślne', 'bullet_tick', "catalog/products/images/default.php?product={$product->id}&id=\${id}", null, 'default') ?>
    <?= fff('Usuń obraz', 'bullet_cross', "catalog/products/images/delete.php?product={$product->id}&id=\${id}", null, 'delete') ?>
  </div>
</script>
