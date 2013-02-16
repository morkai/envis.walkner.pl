<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('catalog/manage');

$manufacturer = fetch_one('SELECT id, name FROM catalog_manufacturers WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($manufacturer));

if (is('post'))
{
  exec_stmt('DELETE FROM catalog_manufacturers WHERE id=?', array(1 => $manufacturer->id));

  log_info('Usunięto wykonawcę produktów <%s>.', $manufacturer->name);

  set_flash(sprintf('Wykonawca produktów <%s> został usunięty pomyślnie.', $manufacturer->name));

  go_to('catalog/manufacturers/');
}

$referer = get_referer("catalog/manufacturers/");
$errors = array();

?>

<? decorate("Usuwanie wykonawcy produktów - Katalog produktów") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie wykonawcy produktów</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("catalog/manufacturers/delete.php?id={$manufacturer->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Usuwanie wykonawcy produktów</legend>
        <p>Na pewno chcesz usunąć wykonawcę produktów &lt;<?= e($manufacturer->name) ?>&gt;?</p>
        <p>Produkty danego wykonawcy <strong>nie</strong> zostaną usunięte.</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń wykonawcę produktów">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
