<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not(is_allowed_to('factory*'), has_access_to_factory($_GET['id']));

$factory = fetch_one('SELECT * FROM factories WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($factory));

$machines = fetch_all('SELECT id, name FROM machines WHERE factory=? ORDER BY name ASC', array(1 => $factory->id));

$hasAnyMachines = !empty($machines);

$canEdit = is_allowed_to('factory/edit');
$canDelete = is_allowed_to('factory/delete');
$canAddMachine = is_allowed_to('machine/add');
$canViewVis = is_allowed_to('vis/factory');

$canManageMachines = is_allowed_to('machine*');
$canEditMachines = is_allowed_to('machine/edit');
$canDeleteMachines = is_allowed_to('machine/delete');

escape_var($factory->name);

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($canEdit): ?><li><a href="<?= url_for("factory/edit.php?id={$factory->id}") ?>">Edytuj fabrykę</a><? endif ?>
  <? if ($canDelete): ?><li><a href="<?= url_for("factory/delete.php?id={$factory->id}") ?>">Usuń fabrykę</a><? endif ?>
  <? if ($canAddMachine): ?><li><a href="<?= url_for("factory/machine/add.php?factory={$factory->id}") ?>">Dodaj maszynę</a><? endif ?>
  <? if ($canViewVis): ?><li><a href="<?= url_for("factory.php?id={$factory->id}") ?>">Pokaż wizualizację</a><? endif ?>
  <li>&nbsp;
</ul>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#machineList').makeClickable();
});
</script>
<? append_slot() ?>

<? decorate("Fabryka") ?>

<div class="yui-gd">
  <div class="yui-u first">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Fabryka &lt;<?= $factory->id ?>&gt;</h1>
      </div>
      <div class="block-body">
        <dl>
          <dt>Nazwa</dt>
          <dd><?= $factory->name ?></dd>
          <dt>Szerokość geograficzna</dt>
          <dd><?= (float)$factory->latitude ?></dd>
          <dt>Długość geograficzna</dt>
          <dd><?= (float)$factory->longitude ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="yui-u">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Maszyny</h1>
      </div>
      <div class="block-body">
      <? if ($hasAnyMachines): ?>
        <table>
          <thead>
            <tr>
              <th>Nazwa</th>
              <? if ($canManageMachines): ?><th>Akcje</th><? endif ?>
            </tr>
          </thead>
          <tbody id="machineList">
          <? foreach ($machines as $machine): ?>
            <? $hasAccessToMachine = has_access_to_machine($machine->id) ?>
            <tr>
              <? if ($hasAccessToMachine): ?>
              <td class="clickable"><a href="<?= url_for("factory/machine/?id={$machine->id}") ?>"><?= e($machine->name) ?></a>
              <? else: ?>
              <td><?= e($machine->name) ?>
              <? endif ?>
              <? if ($canManageMachines): ?>
              <td class="actions">
                <ul>
                  <? if ($hasAccessToMachine): ?>
                  <li><?= fff('Pokaż', 'computer', 'factory/machine/?id=' . $machine->id) ?>
                  <? if ($canEditMachines): ?><li><?= fff('Edytuj', 'computer_edit', 'factory/machine/edit.php?id=' . $machine->id) ?><? endif ?>
                  <? if ($canDeleteMachines): ?><li><?= fff('Usuń', 'computer_delete', '/factory/machine/delete.php?id=' . $machine->id) ?><? endif ?>
                  <? endif ?>
                </ul>
              <? endif ?>
          <? endforeach ?>
          </tbody>
        </table>
      <? else: ?>
        <p>Aktualnie nie ma zdefiniowanych maszyn dla tej fabryki.</p>
      <? endif ?>
      </div>
    </div>
  </div>
</div>
