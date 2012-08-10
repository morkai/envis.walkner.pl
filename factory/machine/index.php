<?php

include '../../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not(is_allowed_to('machine*'), has_access_to_machine($_GET['id']));

$machine = fetch_one(
	'SELECT m.*, f.name AS factoryName FROM machines m INNER JOIN factories f ON f.id=m.factory WHERE m.id=?',
	array(1 => $_GET['id'])
);

if (empty($machine)) not_found();

$engines       = fetch_all('SELECT id, name FROM engines WHERE machine=? ORDER BY name ASC', array(1 => $machine->id));
$hasAnyEngines = !empty($engines);

$canEdit         = is_allowed_to('machine/edit');
$canDelete       = is_allowed_to('machine/delete');
$canAddDevice    = is_allowed_to('machine/device/add');
$canEditDevice   = is_allowed_to('machine/device/edit');
$canDeleteDevice = is_allowed_to('machine/device/delete');
$canViewDocs     = is_allowed_to('documentation*');
$canAddDocs      = is_allowed_to('documentation/edit');
$canViewVis      = is_allowed_to('vis/machine');

?>

<? begin_slot('submenu') ?>
<ul id="submenu">
	<? if ($canEdit): ?><li><a href="<?= url_for("factory/machine/edit.php?id={$machine->id}") ?>">Edytuj maszynę</a><? endif ?>
	<? if ($canDelete): ?><li><a href="<?= url_for("factory/machine/delete.php?id={$machine->id}") ?>">Usuń maszynę</a><? endif ?>
	<? if ($canAddDevice): ?><li><a href="<?= url_for("factory/machine/engine/add.php?machine={$machine->id}") ?>">Dodaj urządzenie</a><? endif ?>
	<? if ($canViewDocs): ?><li><a href="<?= url_for("documentation/view_machine.php?id={$machine->id}") ?>">Pokaż dokumentacje</a><? endif ?>
	<? if ($canAddDocs): ?><li><a href="<?= url_for("documentation/add.php?factory={$machine->factory}&amp;machine={$machine->id}") ?>">Dodaj dokumentację</a><? endif ?>
	<? if ($canViewVis): ?><li><a href="<?= url_for("machine.php?id={$machine->id}") ?>">Pokaż wizualizację</a><? endif ?>
</ul>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#engineList').makeClickable();
});
</script>
<? append_slot() ?>

<? decorate("Maszyna <{$machine->name}>") ?>

<div class="yui-gd">
	<div class="yui-u first">
		<div class="block">
			<div class="block-header">
				<h1 class="block-name">Maszyna &lt;<?= $machine->id ?>&gt;</h1>
			</div>
			<div class="block-body">
				<dl>
					<dt>Nazwa</dt>
					<dd><?= e($machine->name) ?></dd>
					<dt>Fabryka</dt>
					<dd><a href="<?= url_for('factory/view.php?id=' . $machine->factory) ?>"><?= $machine->factoryName ?></a></dd>
				</dl>
			</div>
		</div>
	</div>
	<div class="yui-u">
		<div class="block">
			<div class="block-header">
				<h1 class="block-name">Urządzenia</h1>
			</div>
			<div class="block-body">
			<? if ($hasAnyEngines): ?>
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Nazwa</th>
							<th>Akcje</th>
						</tr>
					</thead>
					<tbody id="engineList">
					<? foreach ($engines as $engine): ?>
						<tr>
							<td><?= $engine->id ?></td>
							<td class="clickable"><a href="<?= url_for("factory/machine/engine/?machine={$machine->id}&id={$engine->id}") ?>"><?= $engine->name ?></a></td>
							<td class="actions">
								<ul>
									<li><?= fff('Pokaż', 'brick', 'factory/machine/engine/?machine=' . $machine->id . '&amp;id=' . $engine->id) ?>
									<? if ($canEditDevice): ?><li><?= fff('Edytuj', 'brick_edit', 'factory/machine/engine/edit.php?machine=' . $machine->id . '&amp;id=' . $engine->id) ?><? endif ?>
									<? if ($canDeleteDevice): ?><li><?= fff('Usuń', 'brick_delete', 'factory/machine/engine/delete.php?machine=' . $machine->id . '&amp;id=' . $engine->id) ?><? endif ?>
								</ul>
							</td>
						</tr>
					<? endforeach ?>
					</tbody>
				</table>
			<? else: ?>
				<p>Aktualnie nie ma zdefiniowanych urządzeń dla tej maszyny.</p>
			<? endif ?>
			</div>
		</div>
	</div>
</div>