<?php

include_once './_common.php';

no_access_if_not_allowed('service/edit');

include_once __DIR__ . '/../_lib_/PagedData.php';

$tasks = new PagedData(1, 1000000);

$query = <<<SQL
SELECT
  t.id,
  t.issue,
  t.assignedTo,
  t.summary,
  t.description,
  t.createdAt,
  i.subject AS issueSubject,
  o.id AS offer,
  o.number AS offerNo,
  o.title AS offerTitle
FROM issue_tasks t
INNER JOIN issues i
  ON i.id=t.issue
INNER JOIN offers o
  ON o.issue=i.id 
WHERE t.completed=0
  AND i.type=4
  AND i.status IN(0,1,2)
ORDER BY t.createdAt DESC
SQL;

$items = fetch_all($query);

$tasks->fill(count($items), $items);

?>

<? begin_slot('head') ?>
<style>

</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#tasksList').makeClickable();
});
</script>
<? append_slot() ?>

<? decorate("Zadania do zamówień") ?>

<div class="block">
  <ul class="block-header">
    <li>
      <h1 class="block-name">Zadania do zamówień</h1>
    </li>
  </ul>
  <div class="block-body">
    <? if (!$tasks->isEmpty()): ?>
    <table>
      <thead>
        <tr>
          <th>Zadanie
          <th class="min">Data
          <th class="min">Oferta
          <th>Zamówienie
      </thead>
      <tbody id="tasksList">
        <? foreach ($tasks as $task): ?>
        <tr>
          <td><?= e($task->summary) ?>
          <td class="min"><?= date('Y-m-d', $task->createdAt) ?>
          <td class="min clickable"><a href="<?= url_for("offers/view.php?id={$task->offer}") ?>" title="<?= e($task->offerTitle) ?>"><?= $task->offerNo ?></a>
          <td class="clickable"><a href="<?= url_for("service/view.php?id={$task->issue}") ?>"><?= $task->issue ?>: <?= e($task->issueSubject) ?></a>
        <? endforeach ?>
      </tbody>
    </table>
    <? else: ?>
    <p>Nie znaleziono żadnych zadań.</p>
    <? endif ?>
  </div>
</div>
