<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

$tpl = fetch_one('SELECT t.*, u.name AS creator FROM issue_templates t INNER JOIN users u ON u.id=t.createdBy WHERE t.id=?', array(1 => $_GET['id']));

not_found_if(empty($tpl));

$tpl->tasks = fetch_all('SELECT * FROM issue_template_tasks WHERE template=? ORDER BY summary', array(1 => $tpl->id));

?>

<? begin_slot('head') ?>
<style>
  .task
  {
    margin-top: 1em;
  }
  .summary
  {
    margin-bottom: 0.25em;
  }
  .block-options li
  {
    margin-left: 0.5em;
  }
  .task .block-header
  {
    padding-right: 0.5em;
  }
</style>
<? append_slot() ?>

<? begin_slot('submenu') ?>
<ul id="submenu">
  <li><a href="<?= url_for("service/templates/tasks/add.php?template={$tpl->id}") ?>">Dodaj nowe zadanie</a>
  <li><a href="<?= url_for("service/templates/edit.php?id={$tpl->id}") ?>">Edytuj szablon zadań</a>
  <li><a href="<?= url_for("service/templates/delete.php?id={$tpl->id}") ?>">Usuń szablon zadań</a>
</ul>
<? replace_slot() ?>

<? decorate("Szablon zadań") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Szablon zadań &lt;<?= $tpl->id ?>></h1>
  </div>
  <div class="block-body">
    <dl>
      <dt>Nazwa
      <dd><?= e($tpl->name) ?>
      <dt>Stworzył
      <dd><?= e($tpl->creator) ?> @ <?= date('Y-m-d, H:i', $tpl->createdAt) ?>
    </dl>
  </div>
</div>

<div id="tasks">
  <? foreach ($tpl->tasks as $task): ?>
  <div class="task block">
    <div class="block-header aside">
      <h1 class="block-name"><?= e($task->summary) ?></h1>
      <ul class="block-options">
        <li><?= fff('Usuń', 'bullet_cross', "service/templates/tasks/delete.php?id={$task->id}&template={$tpl->id}") ?>
        <li><?= fff('Edytuj', 'bullet_edit', "service/templates/tasks/edit.php?id={$task->id}&template={$tpl->id}") ?>
      </ul>
    </div>
    <div class="block-body">
      <?= markdown($task->description) ?>
    </div>
  </div>
  <? endforeach ?>
</div>
