<div class="page <?= $canManageProducts ? 'editable' : '' ?>" data-page="<?= $page->id ?>" data-layout="frontPage">
  <? include __DIR__ . '/_hd.php' ?>
  <div class="productId">
    <img class="productImage" src="<?= $product->imageFile ?>" width="350" height="197">
    <h2 class="productName"><?= e($product->name) ?></h2>
    <h3 class="productType"><?= e($product->type) ?></h3>
  </div>
  <div class="contentsContainer">
    <div class="contents" <? if ($canManageProducts): ?>contenteditable="true"<? endif ?>>
      <?= $page->contents ?>
    </div>
  </div>
  <? include __DIR__ . '/_ft.php' ?>
</div>
