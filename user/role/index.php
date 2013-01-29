<?php

include '../../_common.php';

no_access_if_not_allowed('user/edit/roles');

include '../../_lib_/PagedData.php';

$page    = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$perPage = 15;

$roles = new PagedData($page, $perPage);

$totalItems = fetch_one('SELECT COUNT(*) AS `count` FROM roles')->count;

$query  = 'SELECT id, name FROM roles ORDER BY name';

$items = fetch_all(sprintf("%s LIMIT %s,%s", $query, $roles->getOffset(), $roles->getPerPage()));

$roles->fill($totalItems, $items);

$hasAnyRoles = $totalItems > 0;

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<li><a href="<?= url_for("user/role/add.php") ?>">Dodaj nową rolę</a>
</ul>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#roleList').makeClickable();
});
</script>
<? append_slot() ?>

<? decorate("Role użytkowników") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Role</h1>
	</div>
	<div class="block-body">
	<? if ($hasAnyRoles): ?>
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Nazwa</th>
					<th>Akcje</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="5" class="table-options">
						<?= $roles->render(url_for('user/role/?')) ?>
					</td>
				</tr>
			</tfoot>
			<tbody id="roleList">
			<? foreach ($roles as $role): ?>
				<tr>
					<td><?= $role->id ?></td>
					<td class="clickable"><a href="<?= url_for("user/role/view.php?id={$role->id}") ?>"><?= escape($role->name) ?></a></td>
					<td class="actions">
						<ul>
							<li><?= fff('Pokaż', 'group', 'user/role/view.php?id=' . $role->id) ?>
							<li><?= fff('Edytuj', 'group_edit', 'user/role/edit.php?id=' . $role->id) ?>
							<? if ($role->id !== 'user'): ?>
							<li><?= fff('Usuń', 'group_delete', 'user/role/delete.php?id=' . $role->id) ?>
							<? endif ?>
						</ul>
					</td>
				</tr>
			<? endforeach ?>
			</tbody>
		</table>
	<? else: ?>
		<p>Aktualnie nie ma żadnych ról.</p>
	<? endif ?>
	</div>
</div>
