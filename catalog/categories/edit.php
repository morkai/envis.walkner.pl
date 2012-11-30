<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('catalog/manage');

$query = <<<SQL
SELECT c.id, c.parent, c.name, p.name AS parentName
FROM catalog_categories c
LEFT JOIN catalog_categories p ON p.id = c.parent
WHERE c.id=?
LIMIT 1
SQL;
$oldCategory = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($oldCategory));

$referer = get_referer('catalog/');
$errors = array();

if (is('put'))
{
  $category = $_POST['category'];

  if (is_empty($category['name']))
    $errors[] = 'Nazwa kategorii jest wymagana.';

  if (!empty($errors))
    goto VIEW;

  try
  {
    $bindings = array('name' => $category['name']);

    exec_update('catalog_categories', $bindings, 'id=' . $oldCategory->id);

    $bindings['id'] = $oldCategory->id;

    log_info("Zmodyfikowano kategorię katalogu <{$bindings['name']}>.");

    if (is_ajax())
      output_json(array('success' => true, 'data' => $bindings));

    set_flash('Kategoria została zmodyfikowana.');

    catalog_set_categories_cache();

    go_to($referer);
  }
  catch (PDOException $x)
  {
    $errors[] = $x->getCode() ? 'Nazwa kategorii musi być unikalna.' : $x->getMessage();
  }
}
else
{
  $category = array(
    'name' => $oldCategory->name,
  );
}

VIEW:

if (!empty($errors) && is_ajax())
  output_json(array('success' => false, 'errors' => $errors));

?>

<? decorate('Modyfikowanie kategorii produktów') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Modyfikowanie kategorii produktów</h1>
  </div>
  <div class="block-body">
    <form id="editCategoryForm" method="post" action="<?= url_for("catalog/categories/edit.php?id={$oldCategory->id}") ?>">
      <input name="_method" type=hidden value="PUT">
      <input name="referer" type=hidden value="<?= $referer ?>">
      <?= display_errors($errors) ?>
      <ol class="form-fields">
        <li>
          <?= label('categoryParent', 'Kategoria nadrzędna') ?>
          <p id="categoryParentPath"><?= e($oldCategory->parentName) ?></p>
        <li>
          <?= label('categoryName', 'Nazwa*') ?>
          <input id=categoryName name="category[name]" type="text" value="<?= e($category['name']) ?>" maxlenth="100">
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Modyfikuj kategorię">
            <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
          </ol>
      </ol>
    </form>
  </div>
</div>
