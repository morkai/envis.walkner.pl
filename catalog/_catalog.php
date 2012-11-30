<div id="catalog" class="block <?= $showProduct ? 'collapsed' : '' ?>">
  <ul class="block-header">
    <li>
      <h1 class="block-name">
        <? if ($isRoot): ?>
        Katalog produktów
        <? else: ?>
        Kategorie i produkty
        <? endif ?>
      </h1>
    <? if (empty($category)): ?>
    <li><?=  fff('Dodaj główną kategorię', 'folder_add', 'catalog/categories/add.php') ?>
    <? else: ?>
    <li><?= fff('Dodaj produkt', 'folder_page', "catalog/products/add.php?category={$category->id}") ?>
    <li><?= fff('Dodaj podkategorię', 'folder_add', "catalog/categories/add.php?parent={$category->id}") ?>
    <li><?= fff('Edytuj kategorię', 'folder_edit', "catalog/categories/edit.php?id={$category->id}") ?>
    <li><?= fff('Usuń kategorię', 'folder_delete', "catalog/categories/delete.php?id={$category->id}") ?>
    <? endif ?>
  </ul>
  <div class="block-body">
    <? if (empty($subcategories) && empty($products)): ?>
    <? if ($isRoot): ?>
    <p>Brak kategorii.</p>
    <? else: ?>
    <p>Brak podkategorii i produktów.</p>
    <ul class="actions">
      <li><?= fff_link('Dodaj podkategorię', 'folder_add', "catalog/categories/add.php?parent={$category->id}") ?></li>
      <li><?= fff_link('Dodaj produkt', 'folder_page', "catalog/products/add.php?category={$category->id}") ?></li>
    </ul>
    <? endif ?>
    <? else: ?>
    <table>
      <thead>
        <tr>
          <th>Nazwa</th>
          <th>Typ</th>
          <th>Nr</th>
          <th class="actions">Akcje</th>
        </tr>
      </thead>
      <? if (!empty($pagedProducts)): ?>
      <tfoot>
        <tr>
          <td colspan="99" class="table-options">
            <?= $pagedProducts->render(url_for("catalog/?category={$categoryId}&product={$productId}")) ?>
          </td>
        </tr>
      </tfoot>
      <? endif ?>
      <tbody id="categories">
        <? foreach ($subcategories as $subcategory): ?>
        <tr>
          <td class="name clickable"><a href="<?= url_for("catalog/?category={$subcategory->id}") ?>"><?= e($subcategory->name) ?></a></td>
          <td>Kategoria</td>
          <td>-</td>
          <td class="actions">
            <ul>
              <li><?= fff('Dodaj produkt', 'folder_page', "catalog/products/add.php?category={$subcategory->id}") ?></li>
              <li><?= fff('Edytuj kategorię', 'folder_edit', "catalog/categories/edit.php?id={$subcategory->id}") ?></li>
              <li><?= fff('Usuń kategorię', 'folder_delete', "catalog/categories/delete.php?id={$subcategory->id}") ?></li>
            </ul>
          </td>
        </tr>
        <? endforeach ?>
      </tbody>
      <? if (!empty($pagedProducts)): ?>
      <tbody id="products">
        <? foreach ($pagedProducts as $categoryProduct): ?>
        <tr>
          <td class="clickable"><a href="<?= url_for("catalog/?category={$categoryProduct->category}&product={$categoryProduct->id}&page={$pagedProducts->getPage()}") ?>"><?= e($categoryProduct->name) ?></a></td>
          <td><?= dash_if_empty($categoryProduct->type) ?></td>
          <td><?= dash_if_empty($categoryProduct->nr) ?></td>
          <td class="actions">
            <ul>
              <li><?= fff('Pokaż produkt', 'page', "catalog/?product={$categoryProduct->id}") ?></li>
              <li><?= fff('Edytuj produkt', 'page_edit', "catalog/products/edit.php?id={$categoryProduct->id}") ?></li>
              <li><?= fff('Usuń produkt', 'page_delete', "catalog/products/delete.php?id={$categoryProduct->id}") ?></li>
              <li><?= fff('Pokaż kartę katalogową', 'page_white', "catalog/products/card/?id={$categoryProduct->id}") ?></li>
            </ul>
          </td>
        </tr>
        <? endforeach ?>
      </tbody>
      <? endif ?>
    </table>
    <? endif ?>
  </div>
</div>
