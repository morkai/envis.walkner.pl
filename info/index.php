<?php

$bypassAuth = true;

include_once __DIR__ . '/../_common.php';

$limit = 5;

$newsCount = (int)fetch_one('SELECT COUNT(*) AS count FROM news')->count;

if ($newsCount)
{
  $query = <<<SQL
SELECT n.id, n.createdAt, n.title, n.introduction, u.name AS createdBy, LENGTH(n.body) AS hasBody
FROM news n
INNER JOIN users u ON u.id=n.createdBy
ORDER BY n.createdAt DESC
LIMIT $limit
SQL;

  $newsList = fetch_all($query);
}
else
{
  $newsList = array();
}

$canManage = is_allowed_to('info*');

?>
<? begin_slot('head') ?>
<style>
.news .block-options { color: #FFF; white-space: nowrap }
<? if ($canManage): ?>
.news .block-options li:last-child { margin-right: 1em; }
<? endif ?>
.news .block-header a { text-decoration: none; }
.news .block-body a { background: #06C; color: #FFF; text-decoration: none; padding: 0 0.25em; }
.news .block-body a:hover { background: #F60; }
</style>
<? append_slot() ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($canManage): ?>
  <li><a href="<?= url_for('info/add.php') ?>">Dodaj nową informację</a>
  <? endif ?>
  <? if ($newsCount > $limit): ?>
  <li><a href="<?= url_for('info/archive.php') ?>">Archiwum</a>
  <? endif ?>
</ul>
<? append_slot() ?>

<? decorate("Informacje") ?>

<? if (!$newsCount): ?>
<div class="block">
  <div class="block-header">
    <h1 class="block-name">Informacje</h1>
  </div>
  <div class="block-body">
    <p>Aktualnie nie ma żadnych informacji.</p>
  </div>
</div>
<? else: ?>
<? foreach ($newsList as $news): ?>
<div class="block news">
  <div class="block-header">
    <h1 class="block-name"><a href="<?= url_for('info/view.php?id=' . $news->id) ?>"><?= e($news->title) ?></a></h1>
    <ul class="block-options">
      <li>dodał <?= e($news->createdBy) ?> @ <?= $news->createdAt ?>
    </ul>
  </div>
  <div class="block-body">
    <?= markdown($news->introduction) ?>
    <? if ($news->hasBody): ?>
    <a href="<?= url_for('info/view.php?id=' . $news->id) ?>">Czytaj dalej...</a>
    <? endif ?>
  </div>
</div>
<? endforeach ?>
<? endif ?>
