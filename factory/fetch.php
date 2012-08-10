<?php

include '../_common.php';

if (empty($_GET['id'])) bad_request();

if (!is_allowed_to('machine*') || !has_access_to_factory($_GET['id'])) exit;

$where = '';

if (!$_SESSION['user']->isSuper())
{
	$where = 'AND id IN(' . list_quoted($_SESSION['user']->getAllowedMachineIds()) . ')';
}

$machines = fetch_all('SELECT id, name, factory FROM machines WHERE factory=? ' . $where . ' ORDER BY name ASC', array(1 => $_GET['id']));

$canEdit   = is_allowed_to('machine/edit');
$canDelete = is_allowed_to('machine/delete');

?>
<? foreach ($machines as $machine): ?>
<tr class="factory-<?= $machine->factory ?> machine">
	<td><a class="machine" href="<?= url_for('factory/machine/fetch.php?id=' . $machine->id) ?>" data-id="<?= $machine->id ?>"><?= escape($machine->name) ?></a></td>
	<td class="actions">
		<ul>
			<li><?= fff('Pokaż', 'computer', 'factory/machine/?id=' . $machine->id) ?>
			<? if ($canEdit): ?><li><?= fff('Edytuj', 'computer_edit', 'factory/machine/edit.php?id=' . $machine->id) ?><? endif ?>
			<? if ($canDelete): ?><li><?= fff('Usuń', 'computer_delete', 'factory/machine/delete.php?id=' . $machine->id) ?><? endif ?>
		</ul>
	</td>
</tr>
<? endforeach ?>