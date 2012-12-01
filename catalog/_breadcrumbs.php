<div id="catalog-breadcrumbs">
  <p>
    <a href="<?= url_for("catalog/") ?>">Katalog produktów</a>
    <? if (!empty($categoryPath)): ?>
    <? foreach ($categoryPath as $pathCategory): ?>
    &nbsp;&gt; <a href="<?= url_for("catalog/?category={$pathCategory->id}") ?>"><?= e($pathCategory->name) ?></a>
    <? endforeach ?>
    <? endif ?>
    <? if (!empty($showProduct) && $showProduct): ?>
    &nbsp;&gt; <a href="<?= url_for("catalog/?product={$product->id}") ?>"><?= e($product->name) ?></a>
    <? endif ?>
  </p>
  <form id="catalog-search" action="<?= url_for("catalog/search.php") ?>">
    <input id="catalog-search-query" type="text" name="q" value="<?= empty($query) ? '' : e($query) ?>" autofocus>
    <input type="image" src="<?= url_for_media('fff/zoom.png') ?>">
  </form>
</div>
