<?php

include_once __DIR__ . '/../../../_common.php';

bad_request_if(empty($_GET['machine']) || empty($_GET['id']));

no_access_if_not_allowed('machine/device/delete');

$device = fetch_one(
  'SELECT e.`name`, e.machine, m.name AS machineName, f.name AS factoryName FROM `engines` e INNER JOIN machines m ON m.id=e.machine INNER JOIN factories f ON f.id=m.factory WHERE e.`id`=? AND e.machine=?',
  array(1 => $_GET['id'], $_GET['machine'])
);

not_found_if(empty($device));

no_access_if_not(has_access_to_machine($device->machine));

if (count($_POST))
{
  exec_stmt('DELETE FROM `engines` WHERE `id`=? AND machine=?', array(1 => $_GET['id'], $_GET['machine']));

  log_info('Usunięto urządzenie <%s>.', $device->name);

  set_flash(sprintf('Urządzenie <%s> zostało usunięte pomyślnie.', $device->name));

  go_to('factory/machine/?id=' . $device->machine);
}

$referer = get_referer('factory/machine/device/?machine=' . $_GET['machine'] . '&id=' . $_GET['id']);
$errors = array();

$id = escape($_GET['id']);

escape_var($device->name);
escape_var($device->machineName);
escape_var($device->factoryName);

?>

<? decorate("Usuwanie urządzenia") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie urządzenia</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("factory/machine/engine/delete.php?machine={$device->machine}&amp;id={$id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Usuwanie urządzenia</legend>
        <p>Na pewno chcesz usunąć urządzenie &lt;<?= $device->name ?>&gt; od maszyny &lt;<?= $device->machineName ?>&gt; z fabryki &lt;<?= $device->factoryName ?>&gt;?</p>
        <p>Wraz z urządzeniem usunięte zostaną wszystkie zgromadzone dla niego wartości.</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń urządzenie">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
