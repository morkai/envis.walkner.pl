<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('catalog/manage');

$kind = fetch_one('SELECT id, name FROM catalog_product_kinds WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($kind));

if (is('post'))
{
  exec_stmt('DELETE FROM catalog_product_kinds WHERE id=?', array(1 => $kind->id));

  log_info('Usunięto rodzaj produktów <%s>.', $kind->name);

  set_flash(sprintf('Rodzaj produktów <%s> został usunięty pomyślnie.', $kind->name));

  go_to('catalog/kinds/');
}

$referer = get_referer("catalog/kinds/");
$errors = array();

?>

<? decorate("Usuwanie rodzaju produktów - Katalog produktów") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie rodzaju produktów</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("catalog/kinds/delete.php?id={$kind->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Usuwanie rodzaju produktów</legend>
        <p>Na pewno chcesz usunąć rodzaj produktów &lt;<?= e($kind->name) ?>&gt;?</p>
        <p>Produkty o danym rodzaju <strong>nie</strong> zostaną usunięte.</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń rodzaj produktów">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
