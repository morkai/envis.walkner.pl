<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('catalog/manage');

$oldManufacturer = fetch_one('SELECT * FROM catalog_manufacturers WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($oldManufacturer));

$referer = get_referer("catalog/manufacturers/");
$errors = array();

if (is('post'))
{
  $manufacturer = $_POST['manufacturer'];

  if (!empty($errors))
    goto VIEW;

  try
  {
    exec_update('catalog_manufacturers', $manufacturer, "id={$oldManufacturer->id}");

    log_info('Zmodyfikowano wykonwawcę produktów <%s>.', $manufacturer['name']);

    set_flash(sprintf('Wykonawca produktów <%s> został zmodyfikowany pomyślnie.', $manufacturer['name']));

    go_to($referer);
  }
  catch (PDOException $x)
  {
    if ($x->getCode() == 23000)
    {
      $errors[] = 'Podany nr jest już wykorzystywany przez innego wykonawcę produktów.';
    }
    else
    {
      throw $x;
    }
  }
}
else
{
	$manufacturer = $oldManufacturer;
}

VIEW:

?>

<? decorate("Edycja wykonawcy produktów - Katalog produktów") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Edycja wykonawcy produktów &lt;<?= $oldManufacturer->id ?>&gt;</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("catalog/manufacturers/edit.php?id={$oldManufacturer->id}") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Edycja wykonawcy produktów</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
          <li>
            <?= label('manufacturer-nr', 'Nr') ?>
            <p><?= $oldManufacturer->nr ?></p>
          <li>
            <?= label('manufacturer-name', 'Nazwa') ?>
            <input id="manufacturer-name" name="manufacturer[name]" type="text" maxlength="100" value="<?= e($manufacturer['name']) ?>">
					<li>
						<?= label('manufacturer-label', 'Etykieta') ?>
						<input id="manufacturer-label" name="manufacturer[label]" type="text" maxlength="100" value="<?= e($manufacturer['label']) ?>">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Edytuj wykonawcę produktów">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>
