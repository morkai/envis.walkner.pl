<?php

include './_common.php';

if (empty($_GET['machine']) || empty($_GET['id'])) bad_request();

no_access_if_not_allowed('documentation*');

$query = <<<SQL
SELECT f.name AS factoryName, m.name AS machineName, d.name, d.machine, m.factory, d.id
FROM engines d
INNER JOIN machines m ON m.id=d.machine
INNER JOIN factories f ON f.id=m.factory
WHERE d.id=:device
  AND d.machine=:machine
LIMIT 1
SQL;

$device = fetch_one($query, array(':device' => $_GET['id'], ':machine' => $_GET['machine']));

if (empty($device)) not_found();

no_access_if_not(has_access_to_machine($device->machine));

$query = <<<SQL
SELECT id, title
FROM documentations
WHERE machine=:machine
	AND device=:device
ORDER BY title
SQL;

$docs = fetch_all($query, array(':machine' => $device->machine, ':device' => $_GET['id']));

$hasDocs    = !empty($docs);
$canAddDocs = is_allowed_to('documentation/add');

?>

<? begin_slot('submenu') ?>
<ul id="submenu">
	<? if ($canAddDocs): ?><li><a href="<?= url_for("documentation/add.php?factory={$device->factory}&amp;machine={$device->machine}&amp;device={$device->id}") ?>">Dodaj dokumentację</a><? endif ?>
</ul>
<? append_slot() ?>

<? decorate("Dokumentacje urządzenia") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Dokumentacje urządzenia &lt;<?= $device->name ?>&gt; z maszyny &lt;<?= $device->machineName ?>&gt; w fabryce &lt;<?= $device->factoryName ?>&gt;</h1>
	</div>
	<div class="block-body">
		<? if ($hasDocs): ?>
		<ul>
			<? foreach ($docs as $doc): ?>
			<li><a href="<?= url_for('documentation/view.php?id=' . $doc->id) ?>"><?= escape($doc->title) ?></a>
			<? endforeach ?>
		</ul>
		<? else: ?>
		<p>Aktualnie nie ma dostępnej żadnej dokumentacji dla wybranego urządzenia.</p>
		<? endif ?>
	</div>
</div>