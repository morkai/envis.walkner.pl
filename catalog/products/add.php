<?php

include '../../_common.php';

no_access_if_not_allowed('catalog/manage');

if (empty($_GET['category'])) bad_request();

$category = fetch_one('SELECT id, name FROM catalog_categories WHERE id=?', array(1 => $_GET['category']));

if (empty($category)) bad_request();

$errors  = array();
$referer = get_referer('catalog/'); 

if (is('post'))
{
  $product = empty($_POST['product']) ? array() : $_POST['product'];

  if (is_empty($product['name']))
    $errors[] = 'Nazwa produktu jest wymagana';

  if (!empty($errors))
    goto VIEW;

  try
  {
    $bindings = $product + array('category' => $category->id);

    exec_insert('catalog_products', $bindings);

    $bindings['id'] = get_conn()->lastInsertId();

    log_info("Dodano produkt <{$product['name']}> do katalogu.");

    if (is_ajax())
      output_json(array('success' => true, 'data' => $bindings));

    set_flash("Nowy produkt został dodany pomyślnie.");

    go_to($referer);
  }
  catch (PDOException $x)
  {
    if ($x->getCode() == 23000)
      $errors[] = 'Nazwa produktu musi być unikalna.';
    else
      $errors[] = $x->getMessage();
  }
}
else
{
  $product = array(
    'name'         => '',
    'type'         => '',
    'description'  => '',
    'public'       => 0
  );
}

VIEW:

if (!empty($errors))
  output_json(array('status' => false, 'errors' => $errors));

?>

<? decorate('Dodawanie produktu do katalogu') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Dodawanie produktu</h1>
  </div>
  <div class="block-body">
    <form id="addProductForm" method="post" action="<?= url_for("catalog/products/add.php?category={$category->id}") ?>">
      <input name="referer" type="hidden" value="<?= $referer ?>">
      <? display_errors($errors) ?>
      <ol class="form-fields">
        <li>
          <?= label('addProductCategory', 'Kategoria') ?>
          <p id="addProductCategory"><?= e($category->name) ?></p>
        <li>
          <?= label('addProductName', 'Nazwa*') ?>
          <input id="addProductName" name="product[name]" type="text" value="<?= e($product['name']) ?>" maxlength="100">
        <li>
          <?= label('addProductDescription', 'Opis') ?>
          <textarea id="addProductDescription" name="product[description]" class="markdown"><?= e($product['description']) ?></textarea>
        <li>
          <?= label('addProductType', 'Typ') ?>
          <input id="addProductType" name="product[type]" type="text" value="<?= e($product['type']) ?>" maxlength="100">
        <li>
          <input id="addProductPublic" name="product[public]" type="checkbox" value="1" <?= checked_if($product['public']) ?>>
          <?= label('addProductPublic', 'Publiczny') ?>
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Dodaj produkt">
            <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
          </ol>
      </ol>
    </form>
  </div>
</div>
