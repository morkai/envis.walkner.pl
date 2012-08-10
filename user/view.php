<?php

include '../_common.php';

if (!isset($_GET['id']))
{
	$_GET['id'] = $_SESSION['user']->getId();
}

no_access_if(($_GET['id'] !== $_SESSION['user']->getId()) && !is_allowed_to('user*'));

$user = fetch_one('SELECT id, name, email, createdAt FROM users WHERE id=? LIMIT 1', array(1 => $_GET['id']));

if (!$user) not_found();

escape_vars($user->name, $user->email);

$canEdit   = is_allowed_to('user/edit');
$canDelete = is_allowed_to('user/delete');

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<? if ($canEdit): ?><li><a href="<?= url_for("user/edit.php?id={$user->id}") ?>">Edytuj użytkownika</a><? endif ?>
	<? if ($canDelete): ?><li><a href="<?= url_for("user/delete.php?id={$user->id}") ?>">Usuń użytkownika</a><? endif ?>
	<li><a href="<?= url_for("user/logs.php?user={$user->name}") ?>">Pokaż logi użytkownika</a>
</ul>
<? append_slot() ?>

<? decorate("Użytkownik") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Użytkownik &lt;<?= $user->id ?>&gt;</h1>
	</div>
	<div class="block-body">
		<dl>
			<dt>Imię i nazwisko
			<dd><?= $user->name ?>
			<dt>Adres e-mail
			<dd><?= $user->email ?>
			<dt>Stworzony
			<dd><?= $user->createdAt ?>
		</dl>
	</div>
</div>