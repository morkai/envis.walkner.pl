<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('catalog/manage');

$manufacturers = fetch_all('SELECT * FROM catalog_manufacturers ORDER BY nr ASC');

$hasAnyManufacturers = !empty($manufacturers);

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<li><a href="<?= url_for("catalog/manufacturers/add.php") ?>">Dodaj nowego wykonawcę produktów</a>
</ul>
<? append_slot() ?>

<? decorate("Wykonawcy produktów - Katalog produktów") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Wykonawcy produktów</h1>
	</div>
	<div class="block-body">
		<? if ($hasAnyManufacturers): ?>
		<table>
			<thead>
				<tr>
					<th>Nr
          <th>Nazwa
          <th>Etykieta
					<th>Akcje
				</tr>
			</thead>
			<tbody>
			<? foreach ($manufacturers as $manufacturer): ?>
				<tr>
					<td><?= $manufacturer->nr ?>
          <td><?= e($manufacturer->name) ?>
          <td><?= dash_if_empty($manufacturer->label) ?>
					<td class="actions">
						<ul>
              <li><?= fff('Edytuj wykonawcę produktu', 'pencil', "catalog/manufacturers/edit.php?id={$manufacturer->id}") ?>
              <li><?= fff('Usuń wykonawcę produktu', 'cross', "catalog/manufacturers/delete.php?id={$manufacturer->id}") ?>
					  </ul>
					</td>
				</tr>
			<? endforeach ?>
			</tbody>
		</table>
		<? else: ?>
		<p>Aktualnie nie ma żadnych wykonawców produktów.</p>
		<? endif ?>
	</div>
</div>
