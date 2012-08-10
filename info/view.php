<?php

$bypassAuth = true;

include '../_common.php';

if (empty($_GET['id'])) bad_request();

$news = fetch_one('SELECT n.*, u.name AS creator FROM news n INNER JOIN users u ON u.id=n.createdBy WHERE n.id=:id', array(':id' => $_GET['id']));

$canManage = is_allowed_to('info*');

?>
<? begin_slot('head') ?>
<style>
.news .block-options { color: #FFF; }
</style>
<? append_slot() ?>
<? if ($canManage): ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<li><a href="<?= url_for('info/edit.php?id=' . $news->id) ?>">Edytuj informację</a>
	<li><a href="<?= url_for('info/delete.php?id=' . $news->id) ?>">Usuń informację</a>
</ul>
<? append_slot() ?>
<? endif ?>

<? decorate("Informacja") ?>

<div class="block news">
	<div class="block-header">
		<h1 class="block-name"><?= e($news->title) ?></h1>
		<ul class="block-options">
			<li>dodał <?= e($news->creator) ?> @ <?= $news->createdAt ?>
		</ul>
	</div>
  <div class="block-body">
    <?= markdown($news->introduction) ?>
		<? if ($news->body): ?>
		<?= markdown($news->body) ?>
		<? endif ?>
	</div>
</div>