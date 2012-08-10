<?php

include '../../_common.php';

no_access_if_not_allowed('catalog/manage');

$referer = get_referer('catalog/');
$errors  = array();

if (is('post'))
{
  $category = $_POST['category'];

  if (is_empty($category['name']))
    $errors[] = 'Nazwa kategorii jest wymagana.';

  if (!empty($errors))
    goto VIEW;

  try
  {
    $bindings = array('parent' => empty($category['parent']) ? null : (int)$category['parent'],
                      'name'   => $category['name']);

    exec_insert('catalog_categories', $bindings);

    $bindings['id'] = get_conn()->lastInsertId();

    log_info("Dodano nową kategorię do katalogu <{$bindings['name']}>.");

    if (is_ajax())
      output_json(array('success' => true, 'data' => $bindings));

    set_flash('Nowa kategoria została dodana.');

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
    'parent' => empty($_REQUEST['parent']) ? 0 : (int)$_REQUEST['parent'],
    'name'   => '',
  );
}

VIEW:

if (!empty($errors) && is_ajax())
  output_json(array('success' => false, 'errors' => $errors));

$parentCategory = '-';

if ($category['parent'])
{
  $parentCategory = fetch_one('SELECT name FROM catalog_categories WHERE id=?', array(1 => $category['parent']));

  if (!empty($parentCategory))
    $parentCategory = $parentCategory->name;
}

?>

<? decorate('Dodawanie kategorii produktów') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Dodawanie kategorii produktów</h1>
  </div>
  <div class="block-body">
    <form id="addCategoryForm" method="post" action="<?= url_for('catalog/categories/add.php') ?>">
      <input name="referer" type=hidden value="<?= $referer ?>">
      <?= display_errors($errors) ?>
      <ol class="form-fields">
        <li>
          <?= label('categoryParent', 'Kategoria nadrzędna') ?>
          <input id="categoryParent" name="category[parent]" type="hidden" value="<?= (int)$category['parent'] ?>">
          <p id="categoryParentPath"><?= e($parentCategory) ?></p>
        <li>
          <?= label('categoryName', 'Nazwa*') ?>
          <input id=categoryName name="category[name]" type="text" value="<?= e($category['name']) ?>" maxlenth="100">
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Dodaj kategorię">
            <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
          </ol>
      </ol>
    </form>
  </div>
</div>