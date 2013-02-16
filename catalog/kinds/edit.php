<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('catalog/manage');

$oldKind = fetch_one('SELECT * FROM catalog_product_kinds WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($oldKind));

$referer = get_referer("catalog/kinds/");
$errors = array();

if (is('post'))
{
  $kind = $_POST['kind'];

  if (!empty($errors))
    goto VIEW;

  try
  {
    exec_update('catalog_product_kinds', $kind, "id={$oldKind->id}");

    log_info('Zmodyfikowano rodzaj produktów <%s>.', $kind['name']);

    set_flash(sprintf('Rodzaj produktów <%s> został zmodyfikowany pomyślnie.', $kind['name']));

    go_to($referer);
  }
  catch (PDOException $x)
  {
    if ($x->getCode() == 23000)
    {
      $errors[] = 'Podany nr jest już wykorzystywany przez inny rodzaj produktów.';
    }
    else
    {
      throw $x;
    }
  }
}
else
{
  $kind = (array)$oldKind;
}

VIEW:

?>

<? decorate("Edycja rodzaju produktów - Katalog produktów") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja rodzaju produktów &lt;<?= $oldKind->id ?>&gt;</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("catalog/kinds/edit.php?id={$oldKind->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Edycja rodzaju produktów</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label('kind-nr', 'Nr') ?>
            <p><?= $oldKind->nr ?></p>
          <li>
            <?= label('kind-name', 'Nazwa') ?>
            <input id="kind-name" name="kind[name]" type="text" maxlength="100" value="<?= e($kind['name']) ?>">
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Edytuj rodzaj produktów">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
