<?php

include __DIR__ . '/../../../_common.php';

bad_request_if(empty($_GET['machine']) || empty($_GET['id']));

no_access_if_not_allowed('machine*');

$device = fetch_one(
  'SELECT e.id, e.`name`, e.machine, m.name AS machineName, m.factory, f.name AS factoryName FROM `engines` e INNER JOIN machines m ON m.id=e.machine INNER JOIN factories f ON f.id=m.factory WHERE e.`id`=? AND e.machine=?',
  array(1 => $_GET['id'], $_GET['machine'])
);

not_found_if(empty($device));

no_access_if_not(has_access_to_machine($device->machine));

$valuesQuery = <<<SQL
SELECT val.value, val.variable, var.name AS variableName, l.`min`, l.`max`
FROM (SELECT * FROM `values` WHERE machine=:machine AND `engine`=:device ORDER BY createdAt DESC) AS val
INNER JOIN `variables` var ON var.id=val.variable
LEFT JOIN limits l ON l.machine=:machine AND l.device=:device AND l.variable=val.variable
GROUP BY val.variable
ORDER BY variableName
SQL;

try
{
  $values = fetch_all($valuesQuery, array(':device' => $device->id, ':machine' => $device->machine));
}
catch (PDOException $x)
{
  echo '<pre>', $x, '</pre>';
  exit;
}

$hasAnyValues = !empty($values);

escape_vars($device->name, $device->factoryName, $device->machineName);

$canEdit = is_allowed_to('machine/device/edit');
$canDelete = is_allowed_to('machine/device/delete');

$canViewDocs = is_allowed_to('documentation*');
$canAddDocs = is_allowed_to('documentation/edit');

$canViewVal = is_allowed_to('variable/value');
$canViewVis = is_allowed_to('vis/device');

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($canEdit): ?><li><a href="<?= url_for("factory/machine/engine/edit.php?machine={$device->machine}&amp;id={$device->id}") ?>">Edytuj urządzenie</a><? endif ?>
  <? if ($canDelete): ?><li><a href="<?= url_for("factory/machine/engine/delete.php?machine={$device->machine}&amp;id={$device->id}") ?>">Usuń urządzenie</a><? endif ?>
  <? if ($canViewDocs): ?><li><a href="<?= url_for("documentation/view_device.php?machine={$device->machine}&amp;id={$device->id}") ?>">Pokaż dokumentacje</a><? endif ?>
  <? if ($canAddDocs): ?><li><a href="<?= url_for("documentation/add.php?factory={$device->factory}&amp;machine={$device->machine}&amp;device={$device->id}") ?>">Dodaj dokumentację</a><? endif ?>
</ul>
<? append_slot() ?>

<? decorate("Urządzenie") ?>

<div class="yui-gd">
  <div class="yui-u first">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Urządzenie &lt;<?= $device->id ?>&gt;</h1>
      </div>
      <div class="block-body">
        <dl>
          <dt>Nazwa</dt>
          <dd><?= $device->name ?></dd>
          <dt>Fabryka</dt>
          <dd><a href="<?= url_for('factory/view.php?id=' . $device->factory) ?>"><?= $device->factoryName ?></a></dd>
          <dt>Maszyna</dt>
          <dd><a href="<?= url_for('factory/machine/?id=' . $device->machine) ?>"><?= $device->machineName ?></a></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="yui-u">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Wartości</h1>
      </div>
      <div class="block-body">
      <? if ($hasAnyValues): ?>
        <table>
          <thead>
            <tr>
              <th>Zmienna
              <th>Minimum
              <th>Aktualna
              <th>Maksimum
              <? if ($canEdit): ?><th>Akcje<? endif ?>
            </tr>
          <tbody>
          <? foreach ($values as $value): ?>
            <tr>
              <td><? if ($canViewVal): ?><a href="<?= url_for('variable/value/?variable=' . $value->variable) ?>"><? endif ?><?= escape($value->variableName) ?><? if ($canViewVal): ?></a><? endif ?>
              <td><?= (float)$value->min ?>
              <td><? if ($canViewVal): ?><a href="<?= url_for("variable/value/engine.php?machine={$device->machine}&amp;id={$device->id}&amp;variable={$value->variable}") ?>"><? endif ?><?= (float)$value->value ?><? if ($canViewVal): ?></a><? endif ?>
              <td><?= (float)$value->max ?>
              <? if ($canEdit): ?>
              <td class="actions">
                <ul>
                  <li class="setLimitsAction"><?= fff('Ustaw limity', 'cog_error', "factory/machine/engine/limit.php?machine={$device->machine}&amp;device={$device->id}&amp;variable={$value->variable}") ?>
                </ul>
              <? endif ?>
          <? endforeach ?>
        </table>
      <? else: ?>
        <p>Aktualnie nie ma zgromadzonych wartości dla tego urządzenia.</p>
      <? endif ?>
      </div>
    </div>
  </div>
</div>

<? if ($canEdit): ?>
<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-plugins/simplemodal/1.3/jquery.simplemodal.min.js') ?>"></script>
<script>
  $(document).ready(function()
  {
    $('li.setLimitsAction a').click(function(i)
    {
      var a = $(this);

      $.get(a.attr('href'), function(data)
      {
        $(data).modal({
          overlayCss: {
            backgroundColor: '#000',
            cursor: 'wait'
          },
          containerCss: {
            textAlign: 'left'
          }
        });

        center($('#simplemodal-container'), $('#limit-block'));

        $('#limit-errors').hide();

        $('#limit').submit(function()
        {
          var min = $('#limit-min').val();
          var max = $('#limit-max').val();

          $('#limit-submit').attr('disabled', 'disabled');
          $('#limit-cancel').hide();

          $.post(
            a.attr('href'),
            render('limit[min]=${min}&limit[max]=${max}', {min: min, max: max}),
            function(data)
            {
              $('#limit-submit').removeAttr('disabled');
              $('#limit-cancel').show();

              if (data.status)
              {
                var tdActions = a.parent().parent().parent();

                tdActions.prev().text(max).prev().prev().text(min);

                $.modal.close();
              }
              else
              {
                $('#limit-block .block-header').addClass('error');

                var errors = $('#limit-errors');

                errors.empty();

                for (var i in data.errors) errors.append('<li>' + data.errors[i]);

                errors.show();

                center($('#simplemodal-container'), $('#limit-block'));
              }
            },
            'json'
          );

          return false;
        });
        $('#limit-cancel').click(function()
        {
          $.modal.close();

          return false;
        });
      })

      return false;
    });
  });
</script>
<? append_slot() ?>
<? endif ?>
