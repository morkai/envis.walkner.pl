<?php

include '../../../_common.php';

if (empty($_GET['machine']) || empty($_GET['device']) || empty($_GET['variable'])) bad_request();

no_access_if_not_allowed('machine/device/edit');

$query = <<<SQL
SELECT
	d.machine,
	d.id AS device,
	d.name AS deviceName,
	v.id AS variable,
	v.name AS variableName,
	l.min,
	l.max
FROM engines d, variables v
LEFT JOIN limits l
	ON l.device=:device
	AND l.variable=:variable
WHERE d.id=:device
	AND d.machine=:machine
	AND v.id=:variable
SQL;

$bindings = array(
	'machine'  => $_GET['machine'],
	'device'   => $_GET['device'],
	'variable' => $_GET['variable']
);

$info = fetch_one($query, $bindings);

if (empty($info)) not_found();

no_access_if_not(has_access_to_machine($info->machine));

if (isset($_POST['limit']))
{
	$errors = array();

	if (!is_numeric($_POST['limit']['min']))
	{
		$errors[] = 'Wartość minimalna musi być liczbą.';
	}

	if (!is_numeric($_POST['limit']['max']))
	{
		$errors[] = 'Wartość maksymalna musi być liczbą.';
	}

	if (!empty($errors))
	{
		output_json(array('status' => false, 'errors' => $errors));
	}

	if ($_POST['limit']['min'] > $_POST['limit']['max'])
	{
		$min = (float)$_POST['limit']['max'];
		$max = (float)$_POST['limit']['min'];
	}
	else
	{
		$min = (float)$_POST['limit']['min'];
		$max = (float)$_POST['limit']['max'];
	}

	$bindings = array(
		'machine'  => $info->machine,
		'device'   => $info->device,
		'variable' => $info->variable,
		'min'      => $min,
		'max'      => $max,
	);

	try
	{
		if (($info->min === null) && ($info->max === null))
		{
			exec_stmt('INSERT INTO limits SET machine=:machine, device=:device, variable=:variable, `min`=:min, `max`=:max', $bindings);

			log_info('Ustawiono limity zmiennej <%s> dla urządzenia <%s>.', $info->variableName, $info->deviceName);
		}
		else
		{
			exec_stmt('UPDATE limits SET `min`=:min, `max`=:max WHERE machine=:machine AND device=:device AND variable=:variable', $bindings);

			log_info('Zmieniono limity zmiennej <%s> dla urządzenia <%s>.', $info->variableName, $info->deviceName);
		}

		output_json(array('status' => true));
	}
	catch (PDOException $x)
	{
		if ($x->getCode() == 26000)
		{
			not_found();
		}
	}
}

?>
<div class="block" id="limit-block">
	<div class="block-header">
		<h1 class="block-name">Ustawianie limitów</h1>
	</div>
	<div class="block-body">
		<form method="post" id="limit" action="<?= url_for('factory/machine/engine/limit.php?device=' . $info->device . '&amp;variable=' . $info->variable) ?>">
			<input id="limit-device" type="hidden" value="<?= $info->device ?>">
			<input id="limit-variable" type="hidden" value="<?= $info->variable ?>">
			<fieldset>
				<legend>Ustawianie limitów</legend>
				<ul class="form-errors" id="limit-errors">
				</ul>
				<ol class="form-fields">
					<li>
						<label>Urządzenie</label>
						<p><?= $info->deviceName ?></p>
					<li>
						<label>Zmienna</label>
						<p><?= $info->variableName ?></p>
					<li>
						<label for="limit-min">Wartość minimalna<span class="form-field-required" title="Wymagane">*</span></label>
						<input id="limit-min" name="limit[min]" type="text" maxlength="10" value="<?= (float)$info->min ?>">
					<li>
						<label for="limit-max">Wartość maksymalna<span class="form-field-required" title="Wymagane">*</span></label>
						<input id="limit-max" name="limit[max]" type="text" maxlength="10" value="<?= (float)$info->max ?>">
					<li>
						<ol class="form-actions">
							<li><input id="limit-submit" type="submit" value="Ustaw limity">
							<li><a id="limit-cancel" href="<?= url_for('factory/machine/engine/?id=' . $info->device) ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>