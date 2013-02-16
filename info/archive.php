<?php

$bypassAuth = true;

include_once __DIR__ . '/../_common.php';

$years = array();

foreach (fetch_all('SELECT YEAR(createdAt) AS year FROM news GROUP BY year ORDER BY createdAt DESC') as $row)
{
  $years[] = (int)$row->year;
}

not_found_if(empty($years));

$selectedYear = empty($_GET['year']) || !in_array($_GET['year'], $years) ? $years[0] : (int)$_GET['year'];

$rows = fetch_all('SELECT id, title, createdAt FROM news WHERE YEAR(createdAt) = :year ORDER BY createdAt ASC', array(':year' => $selectedYear));
$list = array();

foreach ($rows as $row)
{
  $time = strtotime($row->createdAt);

  $row->month = gmdate('F', $time);
  $row->createdAt = gmdate('d, H:i', $time);

  if (!isset($list[$row->month]))
  {
    $list[$row->month] = array();
  }

  $list[$row->month][] = $row;
}

?>

<? begin_slot('head') ?>
<style>
.months { margin-left: 0; }
.months li { list-style: none!important; }
</style>
<? append_slot() ?>

<? if (count($years) > 1): ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <? foreach ($years as $year): ?>
  <li><a href="<?= url_for("info/archive.php?year={$year}") ?>"><?= $year ?></a>
  <? endforeach ?>
</ul>
<? append_slot() ?>
<? endif ?>

<? decorate("Archiwum informacji z roku <{$selectedYear}>") ?>

<div class="block news">
  <div class="block-header">
    <h1 class="block-name">Archiwum informacji z roku &lt;<?= $selectedYear ?>&gt;</h1>
  </div>
  <div class="block-body">
    <? if (empty($list)): ?>
    <p>Nie ma żadnych informacji z roku <?= $selectedYear ?>.</p>
    <? else: ?>
    <ul class="months">
    <? foreach ($list as $month => $newsList): ?>
      <li>
        <?= $month ?>
        <ul>
          <? foreach ($newsList as $news): ?>
          <li><?= $news->createdAt ?> &bull; <a href="<?= url_for("info/view.php?id={$news->id}") ?>"><?= e($news->title) ?></a>
          <? endforeach ?>
        </ul>
    <? endforeach ?>
    </ul>
    <? endif ?>
  </div>
</div>
