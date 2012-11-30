<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_REQUEST['id']));

no_access_if_not_allowed('catalog/manage');

$product = fetch_one('SELECT id, name FROM catalog_products WHERE id=?', array(1 => $_REQUEST['id']));

not_found_if(empty($product));

if (is('delete'))
{
  exec_stmt('DELETE FROM catalog_products WHERE id=?', array(1 => $_REQUEST['id']));

  log_info("Usunięto produkt <{$product->name}> z katalogu.");

  if (is_ajax())
    output_json(array('success' => true, 'id' => $product->id));

  set_flash("Produkt <{$product->name}> został usunięty.");

  go_to('catalog');
}

$referer = get_referer('catalog/');

?>

<? decorate('Usuwanie produktu z katalogu') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie produktu</h1>
  </div>
  <div class="block-body">
    <form id=deleteProductForm method=post action="<?= url_for("catalog/products/delete.php?id={$product->id}") ?>">
      <input type="hidden" name="_method" value="DELETE">
      <p>Jesteś pewien że chcesz usunąć produkt &lt;<?= e($product->name) ?>>?</p>
      <ol class="form-actions">
        <li><input type=submit value="Usuń produkt">
        <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
      </ol>
    </form>
  </div>
</div>
