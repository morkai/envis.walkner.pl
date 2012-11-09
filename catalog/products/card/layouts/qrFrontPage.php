<div class="page <?= $canManageProducts ? 'editable' : '' ?>" data-page="<?= $page->id ?>" data-layout="qrFrontPage">
  <? include __DIR__ . '/_hd.php' ?>
  <div class="productId">
    <h2 class="productName"><?= e($product->name) ?></h2>
    <img class="productImage" src="<?= $product->imageFile ?>" style="max-width: 290px; max-height: 163px">
    <h3 class="productType"><?= e($product->type) ?></h3>
    <img class="productQr" src="http://api.qrserver.com/v1/create-qr-code/?data=http%3A%2F%2Fwalkner.pl%2Fp%2F<?= $product->nr ?>&size=100x100&margin=0" width="100" height="100">
  </div>
  <div class="contentsContainer">
    <div class="contents" <? if ($canManageProducts): ?>contenteditable="true"<? endif ?>>
      <?= $page->contents ?>
    </div>
  </div>
  <? include __DIR__ . '/_ft.php' ?>
</div>
