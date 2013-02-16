<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('documentation*');

$query = <<<SQL
SELECT doc.id, doc.title, doc.description, dev.name AS device, m.name AS machine, f.name AS factory, doc.machine AS machineId
FROM documentations doc
LEFT JOIN engines dev
  ON dev.id=doc.device
LEFT JOIN machines m
  ON m.id=doc.machine
LEFT JOIN factories f
  ON f.id=m.factory
WHERE doc.id=:id
ORDER BY doc.title ASC
SQL;

$doc = fetch_one($query, array(':id' => $_GET['id']));

not_found_if(empty($doc));

no_access_if_not(has_access_to_machine($doc->machineId));

$files = fetch_all('SELECT id, name FROM documentation_files WHERE documentation=:id ORDER BY name ASC', array(':id' => $doc->id));

escape_vars($doc->title, $doc->description);

$canEdit = is_allowed_to('documentation/edit');
$canDelete = is_allowed_to('documentation/delete');

?>
<? begin_slot('head') ?>
<style>
  .files dd { display: list-item; list-style-type: square; margin-left: 2em; margin-top: 0.25em; margin-bottom: 0; }
</style>
<? append_slot() ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($canEdit): ?><li><a href="<?= url_for('documentation/edit.php?id=' . $doc->id) ?>">Edytuj dokumentację</a><? endif ?>
  <? if ($canDelete): ?><li><a href="<?= url_for('documentation/delete.php?id=' . $doc->id) ?>">Usuń dokumentację</a><? endif ?>
</ul>
<? append_slot() ?>

<? decorate("Dokumentacja") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name"><?= $doc->title ?></h1>
  </div>
  <div class="block-body">
    <dl>
      <dt>Dotyczy
      <dd><?= doc_features($doc) ?>
    </dl>
    <?= markdown($doc->description) ?>
    <? if (!empty($files)): ?>
    <dl class="files">
      <dt>Dostępne pliki
      <? foreach ($files as $file): ?>
      <dd><a href="<?= url_for('documentation/download.php?id=' . $file->id) ?>"><?= $file->name ?></a>
      <? endforeach ?>
    </dl>
    <? endif ?>
  </div>
</div>
