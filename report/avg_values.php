<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('report/avg_values');

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

  if (!preg_match($dateRegExp, $_POST['avgValues']['period'], $periodMatch))
  {
    $errors[] = 'Niepoprawny format zakresu czasu.';
  }

  if (empty($errors))
  {
    $from = new DateTime(reconstruct_date($periodMatch));

    if (isset($periodMatch['minute']))
    {
      $periodMatch['second'] = '59';

      $period = '%Y-%m-%d %H:%i:%s';
      $format = 's';
      $titleX = 'sekunda';
    }
    elseif (isset($periodMatch['hour']))
    {
      $periodMatch['second'] = '59';
      $periodMatch['minute'] = '59';

      $period = '%Y-%m-%d %H:%i';
      $format = 'i';
      $titleX = 'minuta';
    }
    elseif (isset($periodMatch['day']))
    {
      $periodMatch['second'] = '59';
      $periodMatch['minute'] = '59';
      $periodMatch['hour'] = '23';

      $period = '%Y-%m-%d %H';
      $format = 'H';
      $titleX = 'godzina';
    }
    elseif (isset($periodMatch['month']))
    {
      $periodMatch['second'] = '59';
      $periodMatch['minute'] = '59';
      $periodMatch['hour'] = '23';
      $periodMatch['day'] = $from->format('t');

      $period = '%Y-%m-%d';
      $format = 'd';
      $titleX = 'dzień';
    }
    else
    {
      $periodMatch['second'] = '59';
      $periodMatch['minute'] = '59';
      $periodMatch['hour'] = '23';
      $periodMatch['day'] = $from->format('t');
      $periodMatch['month'] = '12';

      $period = '%Y-%m';
      $format = 'm';
      $titleX = 'miesiąc';
    }

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
SELECT machine, engine, AVG(`value`) AS avgValue, DATE_FORMAT(createdAt, '%s') AS period
FROM  `values`
WHERE  variable=:variable
  AND machine=:machine
  AND engine IN (%s)
  AND createdAt
    BETWEEN :from
    AND :to
GROUP BY machine, engine, period
ORDER BY period
SQL;

    $bindings = array(
      ':variable' => $_POST['avgValues']['variable'],
      ':from' => $from->format('Y-m-d H:i:s'),
      ':to' => reconstruct_date($periodMatch),
    );

    $result = array();

    foreach ($machines as $machine => $devices)
    {
      $bindings[':machine'] = $machine;

      $devices = "'" . implode("', '", $devices) . "'";

      $result = array_merge($result, fetch_all(sprintf($query, $period, $devices), $bindings));
    }

    $deviceColumns = array_flip($_POST['avgValues']['device']);
    $data = array();

    foreach ($result as $value)
    {
      if ($format === 'H')
      {
        $value->period .= ':00';
      }

      $value->period = new DateTime($value->period);
      $value->period = (int)$value->period->format($format);

      if (!isset($data[$value->period]))
      {
        $data[$value->period] = array();
      }

      if (!isset($data[$value->period][$deviceColumns[$value->machine . '|' . $value->engine]]))
      {
        $data[$value->period][$deviceColumns[$value->machine . '|' . $value->engine]] = array();
      }

      $data[$value->period][$deviceColumns[$value->machine . '|' . $value->engine]] = round($value->avgValue, 4);
    }

    $rowCount = count($data);
    $devices = $_POST['avgValues']['device'];
?>
<? begin_slot('head') ?>
<style>
.print #chartForm { display: none; }
</style>
<? append_slot() ?>
<?php

  }

  $selectedDevices = isset($_POST['avgValues']['device']) ? escape($_POST['avgValues']['device']) : null;
  $selectedVariable = escape($_POST['avgValues']['variable']);
  $period = escape($_POST['avgValues']['period']);
  $showGraph = empty($errors);
}
else
{
  $selectedDevices = null;
  $selectedVariable = null;
  $period = date('Y-m-d');
  $showGraph = false;
}

?>
<? if ($showGraph): ?>
<? begin_slot('js') ?>
<script src="https://www.google.com/jsapi?key=<?= ENVIS_GOOGLE_KEY ?>"></script>
<script>
  google.load('visualization', '1', {packages: ['linechart']});

  $(document).ready(function()
  {
    var data = new google.visualization.DataTable();

    data.addColumn('string', 'Czas');
    <? foreach ($devices as $device): ?>
    data.addColumn('number', '<?= $device ?>');
    <? endforeach ?>

    data.addRows(<?= $rowCount ?>);

    var row = -1;
    <? foreach ($data as $category => $values): ?>
    data.setValue(++row, 0, '<?= $category ?>');
    <? foreach ($values as $column => $value): ?>
    data.setValue(row, <?= $column + 1 ?>, <?= $value ?>);
    <? endforeach ?>
    <? endforeach ?>

    var el = document.getElementById('avgValuesChart');
    el.innerHTML = '';

    new google.visualization.LineChart(el).draw(data, {
      width: '100%',
      height: 600,
      legend: 'bottom',
      pointSize: 5,
      titleY: 'Średnia wartość',
      titleX: '<?= $titleX ?>'
    });

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
$referer = get_referer('report/avg_values.php');

?>

<? begin_slot('head') ?>
<style>
#avgValuesChart  { overflow: auto; }
</style>
<? append_slot() ?>

<? decorate("Raport średnich wartości zmiennych") ?>

<? if ($showGraph): ?>
<div class="block" id="avgValuesChartBlock">
  <div class="block-header">
    <h1 class="block-name">Średnie wartości zmiennej &lt;<?= $selectedVariable ?>&gt;</h1>
    <ul class="block-options">
      <li><img id="printChart" src="<?= url_for_media('fff/printer_start.png') ?>" alt="Drukuj" title="Drukuj">
    </ul>
  </div>
  <div class="block-body" id="avgValuesChart"></div>
</div>
<? endif ?>
<div class="block" id="chartForm">
  <div class="block-header">
    <h1 class="block-name">Średnie wartości zmiennych</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('report/avg_values.php') ?>">
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
          <li>
            <label for="avgValuesFormPeriod">Zakres czasu<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="avgValuesFormPeriod" name="avgValues[period]" type="text" value="<?= $period ?>">
            <p class="form-field-help">Format YYYY-MM-DD HH:MM.</p>
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
