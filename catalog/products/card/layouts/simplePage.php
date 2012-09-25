<div class="page <?= $canManageProducts ? 'editable' : '' ?>" data-page="<?= $page->id ?>" data-layout="simplePage">
  <? include __DIR__ . '/_hd.php' ?>
  <div class="contentsContainer">
    <div class="contents" <? if ($canManageProducts): ?>contenteditable="true"<? endif ?>>
      <?= $page->contents ?>
    </div>
  </div>
  <? include __DIR__ . '/_ft.php' ?>
</div>
