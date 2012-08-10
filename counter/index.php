<?php

include './_common.php';

no_access_if_not_allowed('counter');

$query = <<<SQL
SELECT
	val.machine,
	val.`engine` AS device,
	m.name AS machineName,
	d.name AS name,
	f.name AS factoryName
FROM `values` val
INNER JOIN machines m ON m.id=val.machine
INNER JOIN engines d ON d.id=val.`engine`
INNER JOIN factories f ON f.id=m.factory
WHERE val.variable=:variable
GROUP BY val.machine, val.`engine`
SQL;

$var = get_counter_var();

$counters = fetch_all($query, array(':variable' => $var));

$factories = array();

foreach ($counters as $counter)
{
	if (!has_access_to_machine($counter->machine))
	{
		continue;
	}
	
	if (!isset($factories[$counter->factoryName]))
	{
		$factories[$counter->factoryName] = array();
	}

	if (!isset($factories[$counter->factoryName][$counter->machineName]))
	{
		$factories[$counter->factoryName][$counter->machineName] = array();
	}

	$factories[$counter->factoryName][$counter->machineName][] = $counter;
}

$hasAnyCounters = !empty($counters);

?>

<? begin_slot('head') ?>
<style>
.factory th { padding-top: 1em; }
.factory:first-child th { padding-top: 0; }
.machine td { font-weight: bold; color: #000; }
.machine:hover td { border-color: #CCC; color: inherit; }
.counter td { padding-left: 2em; }
form { margin-top: 1em; }
</style>
<? append_slot() ?>

<? decorate("Lista liczników") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Liczniki</h1>
	</div>
	<div class="block-body">
		<? if ($hasAnyCounters): ?>
		<table>
			<tbody>
			<? foreach ($factories as $factory => $machines): ?>
				<tr class="factory">
					<th><?= e($factory) ?>
				</tr>
				<? foreach ($machines as $machine => $counters): ?>
					<tr class="machine">
						<td><?= e($machine) ?>
					</tr>
					<? foreach ($counters as $counter): ?>
						<tr class="counter">
							<td>
								<a href="<?= url_for("counter/view.php?machine={$counter->machine}&device={$counter->device}&var={$var}") ?>"><?= e($counter->name) ?></a>
						</tr>
					<? endforeach ?>
				<? endforeach ?>
			<? endforeach ?>
			</tbody>
		</table>
		<? else: ?>
		<p>Aktualnie nie ma żadnych liczników.</p>
		<? endif ?>
		<form action="<?= url_for('counter/') ?>" method="get">
			<fieldset>
				<ol class="form-fields horizontal">
					<li>
						<label for="var">Zmienna:</label>
						<input id="var" name="var" type="text" value="<?= $var ?>">
					</li>
					<li>
						<input type="submit" value="Zmień">
					</li>
				</ol>
			</fieldset>
		</form>
	</div>
</div>