<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT
  i.id,
  i.owner,
  i.creator,
  i.relatedFactory,
  i.relatedMachine,
  i.relatedDevice
FROM issues i
WHERE i.id=?
LIMIT 1
SQL;

$issue = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($issue));

$issue->documentations = array();

if (!empty($issue->relatedMachine))
{
  if (empty($issue->relatedDevice))
  {
    $documentations = fetch_all(
      'SELECT id, title, description FROM documentations WHERE machine=? AND device IS NULL',
      array(1 => $issue->relatedMachine)
    );
  }
  else
  {
    $documentations = fetch_all(
      'SELECT id, title, description FROM documentations WHERE machine=? AND device=?',
      array(1 => $issue->relatedMachine, $issue->relatedDevice)
    );
  }

  $documentationIds = array_map(function($documentation) { return $documentation->id; }, $documentations);

  $files = empty($documentationIds)
    ? array()
    : fetch_all('SELECT id, documentation, name FROM documentation_files WHERE documentation IN(' . implode(',', $documentationIds) . ') ORDER BY name ASC');

  $issue->documentations = array();

  foreach ($documentations as $documentation)
  {
    $documentation->files = array();

    $issue->documentations[$documentation->id] = $documentation;
  }

  foreach ($files as $file)
  {
    $issue->documentations[$file->documentation]->files[] = $file;
  }
}

$canAddDocumentation    = is_allowed_to('documentation/add');
$canEditDocumentation   = is_allowed_to('documentation/edit');
$canDeleteDocumentation = is_allowed_to('documentation/delete');

$addDocumentationUrl = url_for("documentation/add.php?factory={$issue->relatedFactory}&amp;machine={$issue->relatedMachine}&amp;device={$issue->relatedDevice}");

?>

<? foreach ($issue->documentations as $documentation): ?>
<div class="documentation">
  <h1>
    <a href="<?= url_for("documentation/view.php?id={$documentation->id}") ?>"><?= e($documentation->title) ?></a>
    <? if ($canEditDocumentation): ?><?= fff('Edytuj', 'pencil', 'documentation/edit.php?id=' . $documentation->id) ?><? endif ?>
    <? if ($canDeleteDocumentation): ?><?= fff('Usuń', 'cross', 'documentation/delete.php?id=' . $documentation->id) ?><? endif ?>
  </h1>
  <?= markdown($documentation->description) ?>
  <? if (!empty($documentation->files)): ?>
  <dl>
    <dt>Dostępne pliki:
    <? foreach ($documentation->files as $file): ?>
    <dd><a href="<?= url_for('documentation/download.php?id=' . $file->id) ?>"><?= $file->name ?></a>
    <? endforeach ?>
  </dl>
  <? endif ?>
</div>
<? endforeach ?>

<div id="documentationsOptions">
  <? if (empty($issue->documentations)): ?>
  <p>Brak dokumentacji.</p>
  <? endif ?>
  <? if ($canAddDocumentation): ?>
  <ul class="actions">
    <li><?= fff_link('Dodaj nową dokumentację', 'add',  $addDocumentationUrl) ?>
  </ul>
  <? endif ?>
</div>
