<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_REQUEST['id']));

no_access_if_not_allowed('catalog/manage');

$product = fetch_one('SELECT id, name FROM catalog_products WHERE id=?', array(1 => $_REQUEST['id']));

not_found_if(empty($product));

$referer = get_referer('catalog/');

if (is('delete'))
{
  $files = fetch_all('SELECT file FROM catalog_product_files WHERE product=?', array(1 => $product->id));

  foreach ($files as $file)
  {
    $path = ENVIS_UPLOADS_PATH . '/products/' . $file->file;

    if (file_exists($path))
    {
      unlink($path);
    }
  }

  $images = fetch_all('SELECT file FROM catalog_product_images WHERE product=?', array(1 => $product->id));

  foreach ($images as $image)
  {
    $path = ENVIS_UPLOADS_PATH . '/products/' . $image->file;
    $thumbPath = preg_replace('/([a-z0-9]{32})\.([a-zA-Z]+)$/', '$1.thumb.$2', $path);

    if (file_exists($path))
    {
      unlink($path);
    }

    if (file_exists($thumbPath))
    {
      unlink($thumbPath);
    }
  }

  exec_stmt('DELETE FROM catalog_products WHERE id=?', array(1 => $_REQUEST['id']));

  log_info("Usunięto produkt <{$product->name}> z katalogu.");

  set_flash("Produkt <{$product->name}> został usunięty.");

  catalog_set_categories_cache();

  $refererQuery = parse_url(htmlspecialchars_decode($referer, ENT_COMPAT), PHP_URL_QUERY);
  parse_str((string)$refererQuery, $refererQuery);

  if (!empty($refererQuery['product']) && $refererQuery['product'] == $product->id)
  {
    unset($refererQuery['product']);
  }

  go_to("catalog/?" . http_build_query($refererQuery));
}

?>

<? decorate('Usuwanie produktu - Katalog produktów') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie produktu</h1>
  </div>
  <div class="block-body">
    <form id=deleteProductForm method=post action="<?= url_for("catalog/products/delete.php?id={$product->id}") ?>">
      <input type="hidden" name="_method" value="DELETE">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <p>Jesteś pewien że chcesz usunąć produkt &lt;<?= e($product->name) ?>>?</p>
      <ol class="form-actions">
        <li><input type=submit value="Usuń produkt">
        <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
      </ol>
    </form>
  </div>
</div>
