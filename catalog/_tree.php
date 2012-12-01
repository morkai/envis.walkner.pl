
<div id="catalog-tree" class="block">
  <div class="block-header">
    <h1 class="block-name">Kategorie produktów</h1>
  </div>
  <div class="block-body">
    <?= catalog_render_categories_tree(empty($category) ? null : $category->id) ?>
  </div>
</div>
