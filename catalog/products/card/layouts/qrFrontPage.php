<div class="page <?= $canManageProducts ? 'editable' : '' ?>" data-page="<?= $page->id ?>" data-layout="qrFrontPage">
  <? include __DIR__ . '/_hd.php' ?>
  <div class="productId">
    <h2 class="productName"><?= e($product->name) ?></h2>
    <img class="productImage" src="<?= $product->imageFile ?>" style="max-width: 290px; max-height: 163px">
    <h3 class="productType"><?= e($product->type) ?></h3>
    <img class="productQr" src="https://chart.googleapis.com/chart?chs=100x100&cht=qr&choe=UTF-8&chld=L|0&chl=<?= urlencode("http://walkner.pl/p/$product->nr") ?>" width="100" height="100">
  </div>
  <div class="contentsContainer">
    <div class="contents" <? if ($canManageProducts): ?>contenteditable="true"<? endif ?>>
      <?= $page->contents ?>
    </div>
  </div>
  <? include __DIR__ . '/_ft.php' ?>
</div>
