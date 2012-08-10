<?php

include '../_common.php';

no_access_if_not_allowed('report/values');

$errors = array();

if (isset($_POST['avgValues']))
{
	$dateRegExp = '/^(?P<year>[0-9]{4})(?:\-(?P<month>[0-9]{1,2})(?:\-(?P<day>[0-9]{1,2})(?: (?P<hour>[0-9]{1,2})(?:\:(?P<minute>[0-9]{1,2}))?)?)?)?$/';

	if (empty($_POST['avgValues']['device']))
	{
		$errors[] = 'Urządzenie jest wymagane.';
	}
	
	if (empty($_POST['avgValues']['variable']))
	{
		$errors[] = 'Zmienna jest wymagana.';
	}

	if (!preg_match($dateRegExp, $_POST['avgValues']['from'], $fromMatch))
	{
		$errors[] = 'Niepoprawny format zakresu od.';
	}

	if (!preg_match($dateRegExp, $_POST['avgValues']['to'], $toMatch))
	{
		$errors[] = 'Niepoprawny format zakresu do.';
	}

	if (empty($errors))
	{
		$from = new DateTime(reconstruct_date($fromMatch));
		$to   = new DateTime(reconstruct_date($toMatch));

		$machines = array();

		foreach ($_POST['avgValues']['device'] as $device)
		{
			list ($machine, $device) = explode('|', $device);

			if (!isset($machines[$machine]))
			{
				$machines[$machine] = array();
			}

			$machines[$machine][] = $device;
		}

		$query = <<<SQL
SELECT CONCAT(machine, '|', engine) AS device, ROUND(AVG(`value`), 4) AS avgValue, DATE_FORMAT(createdAt, '%%M %%d, %%Y %%H:%%i:%%s') AS period
FROM  `values`
WHERE	variable=:variable
  AND machine=:machine
	AND engine IN (%s)
	AND createdAt
		BETWEEN :from
		AND :to
GROUP BY device, period
ORDER BY createdAt
SQL;

		$bindings = array(
			':variable' => $_POST['avgValues']['variable'],
			':from'     => $from->format('Y-m-d H:i:s'),
			':to'       => $to->format('Y-m-d H:i:s'),
		);
		
		$result = array();

		foreach ($machines as $machine => $devices)
		{
			$bindings[':machine'] = $machine;
			
			$devices = "'" . implode("', '", $devices) . "'";

			$result = array_merge($result, fetch_all(sprintf($query, $devices), $bindings));
		}
		
		$deviceColumns = array_flip($_POST['avgValues']['device']);
		$data          = array();
		$rowCount = 0;

		foreach ($result as $value)
		{
			++$rowCount;

			if (!isset($data[$value->period]))
			{
				$data[$value->period] = array();
			}

			$data[$value->period][$value->device] = (float)$value->avgValue;
		}
		
		$devices  = $_POST['avgValues']['device'];
?>
<? begin_slot('head') ?>
<style>
.print #chartForm { display: none; }
</style>
<? append_slot() ?>
<?php
		
	}
	
	$selectedDevices  = isset($_POST['avgValues']['device']) ? escape($_POST['avgValues']['device']) : null;
	$selectedVariable = escape($_POST['avgValues']['variable']);
	$showGraph        = empty($errors);
}
else
{
	$selectedDevices  = null;
	$selectedVariable = null;
	$showGraph        = false;
}

?>
<? if ($showGraph): ?>
<? begin_slot('js') ?>
<!--[if IE]>
<script src="http://danvk.org/dygraphs/tests/excanvas.js"></script>
<![endif]-->
<script src="<?= url_for_media('dygraph-combined.js', true) ?>"></script>

<script>
	$(function()
	{
		var rowCount = <?= $rowCount ?>;

		if (rowCount > 0)
		{
			var labels = ['Czas'<? foreach ($devices as $device): ?>,'<?= $device ?>'<? endforeach ?>];

			var data = [<? foreach ($data as $time => $values): ?>[new Date('<?= $time ?>') <? foreach ($devices as $device): ?>, <?= isset($values[$device]) ? $values[$device] : 'null' ?><? endforeach ?>],
			<? endforeach ?>[]];
			data.pop();
			
			var el = document.getElementById('chart');
			el.innerHTML = '';

			$(el).width($(el).innerWidth());

			new Dygraph(el, data, {
				labels: labels,
				labelsDiv: document.getElementById('chart-label')
			});
		}

		$('#printChart').css('cursor', 'pointer').click(function()
		{
			var bd = $('html');

			if (bd.hasClass('print'))
			{
				bd.removeClass('print');

				this.src = this.src.replace(/stop/, 'start');
				this.alt = this.title = 'Wersja do druku';
			}
			else
			{
				bd.addClass('print');

				this.src = this.src.replace(/start/, 'stop');
				this.alt = this.title = 'Oryginalna wersja';
			}
		});
	});
</script>
<? append_slot() ?>
<? endif ?>
<?php

$query = <<<SQL
SELECT
	e.id, e.name, e.machine, m.name AS machineName, m.factory, f.name AS factoryName
FROM engines e
INNER JOIN machines m ON m.id=e.machine
INNER JOIN factories f ON f.id=m.factory
ORDER BY factoryName, machineName, e.name
SQL;

$devices = array();

foreach (fetch_all($query) as $row)
{
	if (!has_access_to_machine($row->machine))
	{
		continue;
	}
	
	if (!isset($devices[$row->factoryName]))
	{
		$devices[$row->factoryName] = array();
	}

	if (!isset($devices[$row->factoryName][$row->machineName]))
	{
		$devices[$row->factoryName][$row->machineName] = array();
	}

	$devices[$row->factoryName][$row->machineName][$row->machine . '|' . $row->id] = $row->name;
}

$variables = fetch_all('SELECT id AS value, name AS label FROM variables ORDER BY name');
$referer   = get_referer('report/values.php');

$from = isset($_POST['avgValues']['from']) ? escape($_POST['avgValues']['from']) : date('Y-m-d');
$to = isset($_POST['avgValues']['to']) ? escape($_POST['avgValues']['to']) : date('Y-m-d', strtotime('+1 day'));

?>
<? begin_slot('head') ?>
<style>
#avgValuesChart	{ overflow: auto; }
#chart-label
{
	text-align: center;
	margin: 0.5em auto;
	padding: 0.5em;
	border: 0.1em solid #246;
	max-width: 300px;
}
</style>
<? append_slot() ?>

<? decorate("Raport średnich wartości zmiennej") ?>

<? if ($showGraph): ?>
<div class="block" id="avgValuesChartBlock">
	<div class="block-header">
		<h1 class="block-name">Średnie wartości zmiennej &lt;<?= $selectedVariable ?>&gt;</h1>
		<ul class="block-options">
			<li><img id="printChart" src="<?= url_for_media('fff/printer_start.png') ?>" alt="Drukuj" title="Drukuj">
		</ul>
	</div>
	<div class="block-body" id="avgValuesChart">
		<div id="chart"></div>
		<div id="chart-label"></div>
	</div>
</div>
<? endif ?>
<div class="block" id="chartForm">
	<div class="block-header">
		<h1 class="block-name">Średnie wartości zmiennych</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('report/values.php') ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Średnie wartości zmiennych</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
						<label for="avgValuesFormVariable">Zmienna<span class="form-field-required" title="Wymagane">*</span></label>
						<select id="avgValuesFormVariable" name="avgValues[variable]">
							<option value="0"></option>
							<?= render_options($variables, $selectedVariable) ?>
						</select>
					<li>
						<label for="avgValuesFormDevice">Urządzenia<span class="form-field-required" title="Wymagane">*</span></label>
						<select id="avgValuesFormDevice" name="avgValues[device][]" multiple="multiple" class="group">
							<option value="0"></option>
							<?= render_grouped_options($devices, $selectedDevices) ?>
						</select>
				<li class="horizontal">
					<ol>
						<li>
							<label for="avgValuesForm-from">Od</label>
							<input id="avgValuesForm-from" name="avgValues[from]" type="text" maxlength="19" value="<?= $from ?>">
						<li>
							<label for="avgValuesForm-to">Do</label>
							<input id="avgValuesForm-to" name="avgValues[to]" type="text" maxlength="19" value="<?= $to ?>">
					</ol>
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Generuj raport">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>
