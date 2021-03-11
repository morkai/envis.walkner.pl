<div id="catalog" class="block <?= $showProduct ? 'collapsed' : '' ?>">
  <ul class="block-header">
    <li>
      <h1 class="block-name">Produkty</h1>
    <? if ($canManageProducts): ?>
    <? if (empty($category)): ?>
    <li><?=  fff('Dodaj główną kategorię', 'folder_add', 'catalog/categories/add.php') ?>
    <? else: ?>
    <li><?= fff('Dodaj produkt', 'folder_page', "catalog/products/add.php?category={$category->id}") ?>
    <li><?= fff('Dodaj podkategorię', 'folder_add', "catalog/categories/add.php?parent={$category->id}") ?>
    <li><?= fff('Edytuj kategorię', 'folder_edit', "catalog/categories/edit.php?id={$category->id}") ?>
    <li><?= fff('Usuń kategorię', 'folder_delete', "catalog/categories/delete.php?id={$category->id}") ?>
    <? endif ?>
    <? endif ?>
  </ul>
  <div class="block-body">
    <? if (empty($products)): ?>
    <? if ($isRoot): ?>
    <p>Wybierz kategorię.</p>
    <? else: ?>
    <p>Brak produktów w wybranej kategorii.</p>
    <? if ($canManageProducts): ?>
    <ul class="actions">
      <li><?= fff_link('Dodaj podkategorię', 'folder_add', "catalog/categories/add.php?parent={$category->id}") ?></li>
      <li><?= fff_link('Dodaj produkt', 'folder_page', "catalog/products/add.php?category={$category->id}") ?></li>
    </ul>
    <? endif ?>
    <? endif ?>
    <? else: ?>
    <div id="productGallery">
      <? foreach ($pagedProducts as $categoryProduct): ?>
      <div class="productGallery-item">
        <div class="productGallery-thumb">
          <? if ($categoryProduct->thumb): ?>
          <a href="<?= url_for("/_files_/products/{$categoryProduct->thumb->file}") ?>" rel="lightbox[<?= $categoryId ?>]" title="<?= e($categoryProduct->name) ?>" data-id="<?= e($categoryProduct->thumb->id) ?>">
            <img src="<?= url_for("/catalog/products/images/thumb.php?file={$categoryProduct->thumb->file}") ?>" alt="" height="100">
          </a>
          <? else: ?>
          <a href="javascript:void(0)">
            <img src="<?= url_for('/_static_/img/no-image.png') ?>" alt="" width="100" height="100">
          </a>
          <? endif ?>
        </div>
        <div class="productGallery-details">
          <a class="productGallery-details-name" href="<?= url_for("catalog/?category={$categoryProduct->category}&product={$categoryProduct->id}&page={$pagedProducts->getPage()}") ?>"><?= e($categoryProduct->name) ?></a>
          <? if (!empty($categoryProduct->type)): ?>
            <span class="productGallery-details-type"><?= $categoryProduct->type ?></span>
          <? endif ?>
          <? if (!empty($categoryProduct->nr)): ?>
            <span class="productGallery-details-nr"><?= $categoryProduct->nr ?></span>
          <? endif ?>
        </div>
      </div>
      <? endforeach ?>
    </div>

    <? if (!empty($pagedProducts)): ?>
    <div id="productGallery-paging">
      <?= $pagedProducts->render(url_for("catalog/?category={$categoryId}&product={$productId}")) ?>
    </div>
    <? endif ?>

    <? endif ?>
  </div>
</div>
