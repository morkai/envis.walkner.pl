<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('catalog/manage');

$referer = get_referer('catalog/manufacturers/');
$errors = array();

if (is('post'))
{
  $manufacturer = $_POST['manufacturer'];

  if (!is_numeric($manufacturer['nr']))
		$errors[] = 'Nr musi być liczbą.';

  if (!empty($errors))
    goto VIEW;

  try
  {
    exec_insert('catalog_manufacturers', $manufacturer);

    log_info('Dodano wykonawcę produktów <%s>.', $manufacturer['name']);

    set_flash(sprintf('Wykonawca produktów <%s> został dodany pomyślnie.', $manufacturer['name']));

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
	$manufacturer = array(
    'nr' => '',
    'name' => '',
    'label' => ''
  );
}

VIEW:

?>

<? decorate("Nowy wykonawca produktów - Katalog produktów") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Nowy wykonawca produktów</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("catalog/manufacturers/add.php") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Nowy wykonawca produktów</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
            <?= label('manufacturer-nr', 'Nr*') ?>
						<input id="manufacturer-nr" name="manufacturer[nr]" type="number" min="0" max="65535" value="<?= $manufacturer['nr'] ?>">
						<p class="form-field-help">Musi być unikalny.</p>
					<li>
            <?= label('manufacturer-name', 'Nazwa') ?>
						<input id="manufacturer-name" name="manufacturer[name]" type="text" maxlength="200" value="<?= e($manufacturer['name']) ?>">
 					<li>
            <?= label('manufacturer-label', 'Etykieta') ?>
						<input id="manufacturer-label" name="manufacturer[label]" type="text" maxlength="100" value="<?= e($manufacturer['label']) ?>">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Dodaj wykonawcę produktów">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>
