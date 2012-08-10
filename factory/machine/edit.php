<?php

include '../../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not(is_allowed_to('machine/edit'), has_access_to_machine($_GET['id']));

$machine = fetch_one('SELECT * FROM machines WHERE id=?', array(1 => $_GET['id']));

if (empty($machine)) not_found();

$referer = get_referer('factory/machine/edit.php?id=' . $_GET['id']);
$errors  = array();

if (isset($_POST['machine']))
{
	if (empty($_POST['machine']['factory']))
	{
		$errors[] = 'Fabryka jest wymagana.';
	}

	if (!between(1, $_POST['machine']['name'], 128))
	{
		$errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
	}

	if (empty($errors))
	{
		$bindings = array(1 => $_POST['machine']['name'], $_POST['machine']['factory'], $_GET['id']);

		try
		{
			exec_stmt('UPDATE machines SET name=?, factory=? WHERE id=?', $bindings);

			log_info('Zmodyfikowano maszynę <%s>.', $machine->name);

			set_flash(sprintf('Maszyna <%s> została zmodyfikowana pomyślnie.', $machine->name));

			header('Location: ' . $referer);
			exit;
		}
		catch (PDOException $x)
		{
			if ($x->getCode() == 26000)
			{
				$errors[] = 'Wybrano złą fabrykę.';
			}
		}
	}

	$name    = escape($_POST['machine']['name']);
	$factory = (int)$_POST['machine']['factory'];
}
else
{
	$name    = escape($machine->name);
	$factory = (int)$machine->factory;
}

$factories = array();

$where = '';

if (!$_SESSION['user']->isSuper())
{
	$where = 'WHERE id IN(' . implode(',', $_SESSION['user']->getAllowedFactoryIds()) . ')';
}

$factories = fetch_array('SELECT id AS `key`, name AS `value` FROM factories ' . $where . ' ORDER BY name ASC');


?>

<? decorate("Edycja maszyny") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Edycja maszyny</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('factory/machine/edit.php?id=' . $machine->id) ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Edycja maszyny</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
						<label for="machine-factory">Fabryka<span class="form-field-required" title="Wymagane">*</span></label>
						<select id="machine-factory" name="machine[factory]">
							<option value="0"></option>
						<?= render_options($factories, $factory) ?>
						</select>
					<li>
						<label>ID</label>
						<?= escape($_GET['id']) ?>
					<li>
						<label for="machine-name">Nazwa<span class="form-field-required" title="Wymagane">*</span></label>
						<input id="machine-name" name="machine[name]" type="text" maxlength="128" value="<?= $name ?>">
						<p class="form-field-help">Od 1 do 128 znaków.</p>
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Edytuj maszynę">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>