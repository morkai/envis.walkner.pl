<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not(is_allowed_to('documentation*'), has_access_to_machine($_GET['id']));

$machine = fetch_one('SELECT f.name AS factoryName, m.id, m.name, m.factory FROM machines m INNER JOIN factories f ON f.id=m.factory WHERE m.id=:machine', array(':machine' => $_GET['id']));

not_found_if(empty($machine));

$query = <<<SQL
SELECT id, title
FROM documentations
WHERE machine=:machine
	AND device IS NULL
ORDER BY title
SQL;

$docs = fetch_all($query, array(':machine' => $_GET['id']));

$hasDocs    = !empty($docs);
$canAddDocs = is_allowed_to('documentation/add');

?>

<? begin_slot('submenu') ?>
<ul id="submenu">
	<? if ($canAddDocs): ?><li><a href="<?= url_for("documentation/add.php?factory={$machine->factory}&amp;machine={$machine->id}") ?>">Dodaj dokumentację</a><? endif ?>
</ul>
<? append_slot() ?>

<? decorate("Dokumentacje maszyny") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Dokumentacje maszyny &lt;<?= $machine->name ?>&gt; z fabryki &lt;<?= $machine->factoryName ?>&gt;</h1>
	</div>
	<div class="block-body">
		<? if ($hasDocs): ?>
		<ul>
			<? foreach ($docs as $doc): ?>
			<li><a href="<?= url_for('documentation/view.php?id=' . $doc->id) ?>"><?= escape($doc->title) ?></a>
			<? endforeach ?>
		</ul>
		<? else: ?>
		<p>Aktualnie nie ma dostępnej żadnej dokumentacji dla wybranej maszyny.</p>
		<? endif ?>
	</div>
</div>
