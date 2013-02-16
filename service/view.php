<?php

include __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

$query = <<<SQL
SELECT
  i.*,
  o.name AS ownerName,
  c.name AS creatorName,
  f.name AS factoryName,
  m.name AS machineName,
  d.name AS deviceName,
  p.nr AS relatedProductNr,
  p.name AS relatedProductName,
  p.type AS relatedProductType
FROM issues i
LEFT JOIN users o ON o.id=i.owner
INNER JOIN users c ON c.id=i.creator
LEFT JOIN factories f ON f.id=i.relatedFactory
LEFT JOIN machines m ON m.id=i.relatedMachine
LEFT JOIN engines d ON d.id=i.relatedDevice
LEFT JOIN catalog_products p ON p.id=i.relatedProduct
WHERE i.id=?
LIMIT 1
SQL;

$issue = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($issue));

$issue->statusText = $statuses[$issue->status];
$issue->priorityText = $priorities[$issue->priority];
$issue->kindText = $kinds[$issue->kind];
$issue->typeText = $types[$issue->type];
$issue->order = $issue->type == ISSUE_TYPE_ORDER;
$issue->description = trim($issue->description);

$issue->assignees = fetch_all('SELECT u.id, u.name FROM issue_assignees ia INNER JOIN users u ON u.id=ia.assignee WHERE ia.issue=?', array(1 => $issue->id));

$issue->subscribers = get_issue_subscribers($issue->id, false);

$currentUser = $_SESSION['user'];
$inform = false;
$participant = true;
$assignee = false;
$owner = false;
$creator = false;

$docsViewer = is_issue_docs_viewer($currentUser, $issue);
$docsViewerSuffix = $docsViewer ? '&docs=1' : '';

foreach ($issue->subscribers as $subscriber)
{
  if ($subscriber->id == $currentUser->getId())
  {
    $inform = true;

    break;
  }
}

switch ($currentUser->getId())
{
  case $issue->owner:
    $owner = true;
    break;

  case $issue->creator:
    $creator = true;
    break;

  default:
    foreach ($issue->assignees as $issueAssignee)
    {
      if ($issueAssignee->id == $currentUser->getId())
      {
        $assignee = true;

        break 2;
      }
    }

    $participant = false;

    break;
}

$canChangeProperties = $currentUser->isSuper() || $owner || (!$issue->owner && is_allowed_to('service/edit'));
$canManageAssignees = $canChangeProperties || ($issue->owner == null && is_allowed_to('service/assigning'));
$canViewTasks = $currentUser->isSuper() || $participant;
$canAddNewTasks = $currentUser->isSuper() || $owner;
$canCompleteTasks = $canChangeProperties || $assignee;
$canComment = $canChangeProperties || $participant || $docsViewer;
$canUpdateTime = $currentUser->isSuper() || $owner || $assignee;
$canViewPrices = $issue->order && ($currentUser->isSuper() || $owner || $creator);

no_access_if_not($canComment);

escape_vars($issue->subject);

?>

<? begin_slot('submenu') ?>
<ul id="submenu">
  <li><a id=goToUpdateIssueForm href="#updateIssueForm">Aktualizuj zgłoszenie</a>
  <? if ($canChangeProperties): ?>
  <li><a href="<?= url_for("service/edit.php?id={$issue->id}") ?>">Edytuj zgłoszenie</a>
  <? endif ?>
  <? if ($canUpdateTime): ?>
  <li><a href="<?= url_for("service/time.php?id={$issue->id}") ?>">Aktualizuj czas pracy</a>
  <? endif ?>
  <? if ($participant): ?>
  <li>
    <a href="<?= url_for("service/update.php?issue={$issue->id}&what=subscription{$docsViewerSuffix}") ?>">
      <? if ($inform): ?>
      Nie obserwuj
      <? else: ?>
      Obserwuj
      <? endif ?>
    </a>
  <? endif ?>
  <? if (is_allowed_to('service/delete/all') || ($owner && is_allowed_to('service/delete'))): ?>
  <li><a href="<?= url_for("service/delete.php?id={$issue->id}") ?>">Usuń zgłoszenie</a>
  <? endif ?>
  <? if (is_allowed_to('service/templates*')): ?>
  <li><a href="<?= url_for("service/templates/copy.php?issue={$issue->id}") ?>">Kopiuj zadania</a>
  <? endif ?>
  <? if (is_allowed_to('service/declare')): ?>
  <li><a href="<?= url_for("service/declarations/declare.php?issue={$issue->id}") ?>">Deklaruj zgodność</a>
  <? endif ?>
</ul>
<? append_slot() ?>

<? begin_slot('head') ?>
<link rel=stylesheet href="<?= url_for_media("uploadify/2.1.4/uploadify.css", true) ?>">
<link rel=stylesheet href="<?= url_for("service/view.css") ?>">
<? append_slot() ?>

<? begin_slot('js') ?>
<script src="<?= url_for("service/view.js") ?>"></script>
<script src="<?= url_for_media("jquery-plugins/inview/1.0.0/jquery.inview.js") ?>"></script>
<script src="<?= url_for_media('jquery-plugins/simplemodal/1.3/jquery.simplemodal.min.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/tmpl/1.0.0beta1/jquery.tmpl.min.js') ?>"></script>
<? append_slot() ?>

<? decorate("Zgłoszenie <{$issue->subject}>") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name"><?= $issue->subject ?></h1>
  </div>
  <div class="block-body">
    <div class="yui-gb">
      <div class="yui-u first">
        <table class="attributes">
          <tr>
            <th>ID:
            <td><?= $issue->id ?>
          <tr>
            <th>Status:
            <td><?= $issue->statusText ?>
          <tr>
            <th>Priorytet:
            <td><?= $issue->priorityText ?>
          <tr>
            <th>Rodzaj:
            <td><?= $issue->kindText ?>
          <tr>
            <th>Typ:
            <td><?= $issue->typeText ?>
          <? if ($issue->order): ?>
          <tr>
            <th>Zamówienie:
            <td>
              <? if (!empty($issue->orderNumber)): ?>
              numer <?= e($issue->orderNumber) ?>
              <? elseif (empty($issue->orderDate)): ?>
              -
              <? endif ?>
              <? if (!empty($issue->orderDate)): ?>
              z dnia <?= $issue->orderDate ?>
              <? endif ?>
          <tr>
            <th>Faktura:
            <td>
              <? if (!empty($issue->orderInvoice)): ?>
              numer <?= e($issue->orderInvoice) ?>
              <? elseif (empty($issue->orderInvoiceDate)): ?>
              -
              <? endif ?>
              <? if (!empty($issue->orderInvoiceDate)): ?>
              z dnia <?= $issue->orderInvoiceDate ?>
              <? endif ?>
              <tr>
          <? endif ?>
          <? if (!empty($issue->relatedProduct)): ?>
          <tr>
            <th>Powiązany produkt:
            <td><a href="<?= url_for("/catalog/?product={$issue->relatedProduct}") ?>"><?= e($issue->relatedProductName) ?></a>
          <? endif ?>
        </table>
      </div>
      <div class="yui-u">
        <table class="attributes">
          <tr>
            <th>Czas stworzenia:
            <td><?= date('Y-m-d, H:i', $issue->createdAt) ?>
          <tr>
            <th>Czas aktualizacji:
            <td><?= date('Y-m-d, H:i', $issue->updatedAt) ?>
          <tr>
            <th>Data ukończenia:
            <td><?= dash_if_empty($issue->expectedFinishAt) ?>
          <tr>
            <th>% wykonania:
            <td><?= $issue->percent === null ? '-' : (round($issue->percent) . '%') ?>
          <tr>
            <th>Powiązany obiekt:
            <td>
              <? if ($issue->relatedFactory): ?>
              &gt; <a href="<?= url_for("factory/view.php?id={$issue->relatedFactory}") ?>"><?= e($issue->factoryName) ?></a>
              <? else: ?>
              -
              <? endif ?>
              <? if ($issue->relatedMachine): ?>
              <br>&gt; <a href="<?= url_for("factory/machine/?id={$issue->relatedMachine}") ?>"><?= e($issue->machineName) ?></a>
              <? endif ?>
              <? if ($issue->relatedDevice): ?>
              <br>&gt; <a href="<?= url_for("factory/machine/engine/?machine={$issue->relatedMachine}&amp;id={$issue->relatedDevice}") ?>"><?= e($issue->deviceName) ?></a>
              <? endif ?>
          <? if ($canViewPrices): ?>
          <tr>
            <th>Ilość
            <td><?= round($issue->quantity) ?> <?= $issue->unit ?>
          <tr>
            <th>Cena
            <td><?= $issue->price ?> <?= $issue->currency ?> za <?= $issue->per ?> (<?= $issue->vat ?>% VAT)
          <? endif ?>
          <? if (!empty($issue->relatedProduct)): ?>
          <tr>
            <th>Nr powiązanego produktu:
            <td><?= dash_if_empty($issue->relatedProductNr) ?>
          <tr>
            <th>Typ powiązanego produktu:
            <td><?= dash_if_empty($issue->relatedProductType) ?>
          <? endif ?>
        </table>
      </div>
      <div class="yui-u">
        <table class="attributes">
          <tr>
            <th>Zgłaszający:
            <td><a href="<?= url_for("user/view.php?id={$issue->creator}") ?>"><?= $issue->creatorName ?></a>
          <tr>
            <th>Właściciel:
            <td>
              <? if ($issue->owner): ?>
              <a id="owner" href="<?= url_for("user/view.php?id={$issue->owner}") ?>" data-id="<?= $issue->owner ?>"><?= $issue->ownerName ?></a>
              <? else: ?>
              -
              <? endif ?>
          <tr>
            <th>Przypisane osoby:
            <td>
              <? if (empty($issue->assignees)): ?>
              -
              <? else: ?>
                <? foreach ($issue->assignees as $assignee): ?>
                  <? if ($canManageAssignees): ?>
                  <a class="removeAssignee" href="<?= url_for("service/update.php?issue={$issue->id}&amp;who={$assignee->id}&amp;what=assignee") ?>">
                    <img src="<?= url_for_media('fff/bullet_cross.png') ?>" alt="Usuń przypisanie" title="Usuń przypisanie">
                  </a>
                  <? endif ?>
                  <a class="assignee" href="<?= url_for("user/view.php?id={$assignee->id}") ?>" data-id="<?= $assignee->id ?>"><?= e($assignee->name) ?></a><br>
                <? endforeach ?>
              <? endif ?>
          <? if ($canChangeProperties): ?>
          <tr>
            <th>
            <td>
              <form id=newAssigneeForm method="post" action="<?= url_for("service/update.php?issue={$issue->id}&what=assignees") ?>">
                <input id=newAssignee name="who" type=text maxlength=50>
                <input type=submit value="+">
                <label>
                  <input name="inform" type=checkbox value="1" checked> Poinformuj o przypisaniu
                </label>
              </form>
           <? endif ?>
        </table>
      </div>
    </div>
    <? if (!empty($issue->description)): ?>
    <div id="description">
      <?= markdown($issue->description) ?>
    </div>
    <? endif ?>
  </div>
</div>

<div id="issueTabs">
  <ul>
    <li><a href="#activity">Aktywność</a>
    <? if ($canViewTasks): ?>
    <li><a href="<?= url_for("service/view/tasks.php?id={$issue->id}{$docsViewerSuffix}") ?>">Zadania</a>
    <? endif ?>
    <li><a href="<?= url_for("service/view/relations.php?id={$issue->id}{$docsViewerSuffix}") ?>">Powiązania</a>
    <? if (!empty($issue->relatedMachine)): ?>
    <li><a href="<?= url_for("service/view/docs.php?id={$issue->id}{$docsViewerSuffix}") ?>">Dokumentacje</a>
    <? endif ?>
    <li><a href="<?= url_for("service/view/files.php?id={$issue->id}{$docsViewerSuffix}") ?>">Pliki</a>
  </ul>
  <div id="activity" data-href="<?= url_for("service/view/activity.php?id={$issue->id}{$docsViewerSuffix}") ?>">
    <p>Ładowanie...</p>
  </div>
</div>
