<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('catalog/manage');

$referer = get_referer('catalog/kinds/');
$errors = array();

if (is('post'))
{
  $kind = $_POST['kind'];

  if (!is_numeric($kind['nr']))
		$errors[] = 'Nr musi być liczbą.';

  if (!empty($errors))
    goto VIEW;

  try
  {
    exec_insert('catalog_product_kinds', $kind);

    log_info('Dodano rodzaj produktów <%s>.', $kind['name']);

    set_flash(sprintf('Rodzaj produktów <%s> został dodany pomyślnie.', $kind['name']));

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
	$kind = array(
    'nr' => '',
    'name' => ''
  );
}

VIEW:

?>

<? decorate("Nowy rodzaj produktów - Katalog produktów") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Nowy rodzaj produktów</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("catalog/kinds/add.php") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Nowy rodzaj produktów</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
            <?= label('kind-nr', 'Nr*') ?>
						<input id="kind-nr" name="kind[nr]" type="number" min="0" max="65535" value="<?= $kind['nr'] ?>">
						<p class="form-field-help">Musi być unikalny.</p>
					<li>
            <?= label('kind-name', 'Nazwa') ?>
						<input id="kind-name" name="kind[name]" type="text" maxlength="200" value="<?= e($kind['name']) ?>">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Dodaj rodzaj produktów">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>
