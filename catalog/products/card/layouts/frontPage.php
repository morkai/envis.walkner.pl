<div class="page <?= $canManageProducts ? 'editable' : '' ?>" data-page="<?= $page->id ?>" data-layout="frontPage">
  <? include __DIR__ . '/_hd.php' ?>
  <div class="productId">
    <img class="productImage" src="<?= $product->imageFile ?>" style="max-width: 350px; max-height: 197px">
    <h2 class="productName"><?= e($product->name) ?></h2>
    <h3 class="productType">Typ: <?= e($product->type) ?></h3>
  </div>
  <div class="contentsContainer">
    <div class="contents" <? if ($canManageProducts): ?>contenteditable="true"<? endif ?>>
      <?= $page->contents ?>
    </div>
  </div>
  <? include __DIR__ . '/_ft.php' ?>
</div>
