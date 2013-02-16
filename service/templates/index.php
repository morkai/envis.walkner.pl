<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('service/templates*');

$query = <<<SQL
SELECT t.id, t.name, (SELECT COUNT(*) FROM issue_template_tasks WHERE template=t.id) AS tasks FROM issue_templates t ORDER BY name
SQL;

$templates = fetch_all($query);

$hasAnyTemplates = !empty($templates);

?>

<? begin_slot('submenu') ?>
<ul id="submenu">
  <li><a href="<?= url_for('service/templates/add.php') ?>">Dodaj nowy szablon</a>
</ul>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#issueTemplateList').makeClickable();
});
</script>
<? append_slot() ?>

<? decorate("Szablony zadań do zgłoszeń") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Szablony zadań</h1>
  </div>
  <div class="block-body">
    <? if ($hasAnyTemplates): ?>
    <table>
      <thead>
        <tr>
          <th>Nazwa
          <th>Ilość zadań
          <th>Akcje
      <tbody id="issueTemplateList">
        <? foreach ($templates as $template): ?>
        <tr>
          <td class="clickable"><a href="<?= url_for("service/templates/view.php?id={$template->id}") ?>"><?= e($template->name) ?></a>
          <td><?= $template->tasks ?>
          <td class="actions">
            <ul>
              <li><?= fff('Pokaż', 'bullet_magnify', 'service/templates/view.php?id=' . $template->id) ?>
              <li><?= fff('Edytuj', 'bullet_edit', 'service/templates/edit.php?id=' . $template->id) ?>
              <li><?= fff('Usuń', 'bullet_cross', 'service/templates/delete.php?id=' . $template->id) ?>
            </ul>
        <? endforeach ?>
    </table>
    <? else: ?>
    <p>Aktualnie nie ma żadnych szablonów zadań.</p>
    <p><a href="<?= url_for('service/templates/add.php') ?>">Dodaj nowy szablon</a>.</p>
    <? endif ?>
  </div>
</div>
