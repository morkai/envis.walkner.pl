<?php

include '../_common.php';

no_access_if_not_allowed('storage*');

$storages = fetch_all('SELECT * FROM storages WHERE owner=:owner', array(':owner' => $_SESSION['user']->getId()));

$hasAnyStorages = !empty($storages);

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<li><a href="<?= url_for('storage/add.php') ?>">Dodaj nowy magazyn</a>
</ul>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#storageList').makeClickable();
});
</script>
<? append_slot() ?>

<? decorate("Lista magazynów") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Magazyny</h1>
	</div>
	<div class="block-body">
		<? if ($hasAnyStorages): ?>
		<table>
			<thead>
				<tr>
					<th>Nazwa
					<th>Akcje
			<tbody id="storageList">
				<? foreach ($storages as $storage): ?>
				<tr>
					<td class="clickable"><a href="<?= url_for("storage/view.php?id={$storage->id}") ?>"><?= $storage->name ?></a>
					<td class="actions">
						<ul>
							<li><?= fff('Pokaż', 'cart', 'storage/view.php?id=' . $storage->id) ?>
							<li><?= fff('Edytuj', 'cart_edit', 'storage/edit.php?id=' . $storage->id) ?>
							<li><?= fff('Usuń', 'cart_delete', 'storage/delete.php?id=' . $storage->id) ?>
						</ul>
				<? endforeach ?>
		</table>
		<? else: ?>
		<p>Aktualnie nie ma żadnych magazynów.</p>
		<p><a href="<?= url_for('storage/add.php') ?>">Dodaj nowy magazyn</a>.</p>
		<? endif ?>
	</div>
</div>