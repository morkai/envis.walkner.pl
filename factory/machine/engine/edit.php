<?php

include '../../../_common.php';

if (empty($_GET['machine']) || empty($_GET['id'])) bad_request();

no_access_if_not_allowed('machine/device/edit');

$device = fetch_one(
	'SELECT e.id, e.`name`, e.machine, m.factory, f.name AS factoryName FROM `engines` e INNER JOIN machines m ON m.id=e.machine INNER JOIN factories f ON f.id=m.factory WHERE e.`id`=? AND e.machine=?',
	array(1 => $_GET['id'], $_GET['machine'])
);

if (empty($device)) not_found();

no_access_if_not(has_access_to_machine($device->machine));

$referer = get_referer('factory/machine/device/?machine=' . $_GET['machine'] . '&id=' . $_GET['id']);
$errors  = array();

if (isset($_POST['engine']))
{
	if (empty($_POST['engine']['machine']))
	{
		$errors[] = 'Maszyna jest wymagana.';
	}
	
	if (!between(1, $_POST['engine']['name'], 128))
	{
		$errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
	}

	if (empty($errors))
	{
		$bindings = array(1 => $_POST['engine']['name'], $_POST['engine']['machine'], $_GET['id'], $_GET['machine']);

		exec_stmt('UPDATE `engines` SET `name`=?, machine=? WHERE `id`=? AND machine=?', $bindings);

		log_info('Zmodyfikowano urządzenie <%s>.', $device->name);

		set_flash(sprintf('Urządzenie <%s> zostało zmodyfikowane pomyślnie.', $device->name));

		go_to($referer);
	}

	$name    = escape($_POST['engine']['name']);
	$machine = $_POST['engine']['machine'];
}
else
{
	$name    = escape($device->name);
	$machine = $device->machine;
}

$machines = array();

$stmt = exec_stmt('SELECT id, name FROM machines WHERE factory=? ORDER BY name ASC', array(1 => $device->factory));

foreach ($stmt as $row)
{
	$machines[$row['id']] = $row['name'];
}

$id          = escape($_GET['id']);

?>

<? decorate("Edycja urządzenia") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Edycja urządzenia</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("factory/machine/engine/edit.php?machine={$device->machine}&amp;id={$device->id}") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Edycja urządzenia</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
						<label>ID</label>
						<p><?= $device->id ?></p>
					<li>
						<label for="engine-machine">Maszyna<span class="form-field-required" title="Wymagane">*</span></label>
						<select id="engine-machine" name="engine[machine]">
							<option value="0"></option>
						<?= render_options($machines, $machine) ?>
						</select>
					<li>
						<label for="engine-name">Nazwa:</label>
						<input id="engine-name" name="engine[name]" type="text" maxlength="128" value="<?= $name ?>">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Edytuj urządzenie">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>