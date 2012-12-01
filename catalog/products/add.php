<?php

include __DIR__ . '/../_common.php';

no_access_if_not_allowed('catalog/manage');

bad_request_if(empty($_GET['category']));

$category = fetch_one('SELECT id, name FROM catalog_categories WHERE id=?', array(1 => $_GET['category']));

bad_request_if(empty($category));

$errors = array();
$referer = get_referer('catalog/'); 

if (is('post'))
{
  $product = $_POST['product'] + array('public' => 0);

  if (is_empty($product['name']))
    $errors[] = 'Nazwa produktu jest wymagana.';

  if (!empty($errors))
    goto VIEW;

  if (!empty($product['markings']) && is_array($product['markings']))
  {
    $product['markings'] = implode(',', $product['markings']);
  }

  try
  {
    $bindings = $product + array('category' => $category->id, 'createdAt' => time());

    exec_insert('catalog_products', $bindings);

    $bindings['id'] = get_conn()->lastInsertId();

    if (empty($product['nr']))
    {
      exec_update(
        'catalog_products',
        array('nr' => catalog_generate_product_nr($bindings)),
        "id={$bindings['id']}"
      );
    }

    log_info("Dodano produkt <{$product['name']}> do katalogu.");

    set_flash("Nowy produkt został dodany pomyślnie.");

    go_to($referer);
  }
  catch (PDOException $x)
  {
    $errors[] = $x->getMessage();
  }
}
else
{
  $product = array(
    'nr' => '',
    'name' => '',
    'type' => '',
    'description' => '',
    'public' => 0,
    'revision' => 0,
    'manufacturer' => null,
    'kind' => null,
    'markings' => '',
    'productionDate' => date('Y-m')
  );
}

VIEW:

$categoryPath = catalog_get_category_path($category->id);
$markings = catalog_get_product_markings();
$kinds = catalog_get_product_kinds();
$manufacturers = catalog_get_manufacturers();

if (empty($product['manufacturer']))
{
  foreach ($manufacturers as $k => $v)
  {
    if (strpos(strtolower($v), 'walkner') !== false)
    {
      $product['manufacturer'] = $k;

      break;
    }
  }
}

if (empty($product['kind']))
{
  foreach ($kinds as $k => $v)
  {
    $product['kind'] = $k;

    break;
  }
}

if (!is_array($product['markings']))
{
  $product['markings'] = explode(',', (string)$product['markings']);
}

?>

<? begin_slot('head') ?>
<link rel="stylesheet" href="<?= url_for("/catalog/products/_static_/form.css") ?>">
<? append_slot() ?>

<? begin_slot('js') ?>
<script src="<?= url_for("/catalog/products/_static_/form.js") ?>"></script>
<? append_slot() ?>

<? decorate('Dodawanie produktu - Katalog produktów') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowy produkt</h1>
  </div>
  <div class="block-body">
    <form id="productForm" method="post" action="<?= url_for("catalog/products/add.php?category={$category->id}") ?>">
      <input name="referer" type="hidden" value="<?= $referer ?>">
      <? display_errors($errors) ?>
      <? include_once __DIR__ . '/_form.php' ?>
    </form>
  </div>
</div>
