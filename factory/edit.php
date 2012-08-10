<?php

include '../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not(is_allowed_to('factory/edit'), has_access_to_factory($_GET['id']));

$factory = fetch_one('SELECT * FROM factories WHERE id=?', array(1 => $_GET['id']));

if (empty($factory)) not_found();

$referer = get_referer('factory/view.php?id=' . $_GET['id']);
$errors  = array();

if (isset($_POST['factory']))
{
	settype($_POST['factory']['latitude'], 'float');
	settype($_POST['factory']['longitude'], 'float');

	if (!between(1, $_POST['factory']['name'], 128))
	{
		$errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
	}

	if (empty($errors))
	{
		$bindings = array(1 => $_POST['factory']['name'], $_POST['factory']['latitude'], $_POST['factory']['longitude'], $_GET['id']);

		exec_stmt('UPDATE factories SET name=?, latitude=?, longitude=? WHERE id=?', $bindings);

		log_info('Zmodyfikowano fabrykę <%s>.', $factory->name);

		set_flash(sprintf('Fabryka <%s> została zmodyfikowana pomyślnie.', $factory->name));

		go_to($referer);
	}

	$name      = escape($_POST['factory']['name']);
	$latitude  = escape($_POST['factory']['latitude']);
	$longitude = escape($_POST['factory']['longitude']);
}
else
{
	$name      = escape($factory->name);
	$latitude  = $factory->latitude;
	$longitude = $factory->longitude;
}

$id = escape($_GET['id']);

?>

<? decorate("Edycja fabryki") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Edycja fabryki</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('factory/edit.php?id=' . $id) ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Edycja fabryki</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
						<label for="factory-name">Nazwa<span class="form-field-required" title="Wymagane">*</span></label>
						<input id="factory-name" name="factory[name]" type="text" maxlength="128" value="<?= $name ?>">
					<li>
						<label for="factory-latitude">Szerokość geograficzna<span class="form-field-required" title="Wymagane">*</span></label>
						<input id="factory-latitude" name="factory[latitude]" type="text" maxlength="32" value="<?= $latitude ?>">
					<li>
						<label for="factory-longitude">Długość geograficzna<span class="form-field-required" title="Wymagane">*</span></label>
						<input id="factory-longitude" name="factory[longitude]" type="text" maxlength=32" value="<?= $longitude ?>">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Edytuj fabrykę">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>