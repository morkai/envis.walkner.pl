<?php

include '../_common.php';

no_access_if_not_allowed('variable*');

$variables = fetch_all('SELECT `id`, `name` FROM `variables` ORDER BY `name` ASC');

$hasAnyVariables = !empty($variables);

$canAdd    = is_allowed_to('variable/add');
$canEdit   = is_allowed_to('variable/edit');
$canDelete = is_allowed_to('variable/delete');
$canView   = is_allowed_to('variable/value');

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<? if ($canAdd): ?><li><a href="<?= url_for("variable/add.php") ?>">Zdefiniuj nową zmienną</a><? endif ?>
</ul>
<? append_slot() ?>

<? decorate("Lista zmiennych") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Zmienne</h1>
	</div>
	<div class="block-body">
		<? if ($hasAnyVariables): ?>
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Nazwa</th>
					<? if ($canEdit || $canDelete): ?>
					<th>Akcje</th>
					<? endif ?>
				</tr>
			</thead>
			<tbody>
			<? foreach ($variables as $variable): ?>
				<tr>
					<td><?= $variable->id ?></td>
					<td><?= escape($variable->name) ?></td>
					<td class="actions">
						<ul>
							<? if ($canView): ?>
							<li><?= fff('Wartości', 'cog', 'variable/value/?variable=' . $variable->id) ?>
							<? endif ?>
							<? if ($canEdit): ?>
							<li><?= fff('Edytuj', 'cog_edit', 'variable/edit.php?id=' . $variable->id) ?>
							<? endif ?>
							<? if ($canDelete): ?>
							<li><?= fff('Usuń', 'cog_delete', 'variable/delete.php?id=' . $variable->id) ?>
							<? endif ?>
						</ul>
					</td>
				</tr>
			<? endforeach ?>
			</tbody>
		</table>
		<? else: ?>
		<p>Aktualnie nie ma żadnych zmiennych.</p>
		<? endif ?>
	</div>
</div>