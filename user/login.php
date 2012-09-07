<?php

$bypassAuth = true;

include '../_common.php';

$referer = empty($_GET['referer']) ? get_referer('') : substr($_GET['referer'], strlen(ENVIS_BASE_URL));
$errors  = array();

if (isset($_POST['login']) && isset($_POST['password']))
{
	if (empty($_POST['login']) || empty($_POST['password']))
	{
		$errors[] = 'Niepoprawny login i/lub hasło.';
	}

	if (empty($errors))
	{
		$bindings = array(1 => $_POST['login'], hash('sha256', $_POST['password']));

		$user = fetch_one('SELECT id, role, name, createdAt, lastVisitAt, super, allowedFactories, allowedMachines FROM users WHERE email=? AND password=?', $bindings);

		if ($user)
		{
			$_SESSION['user'] = new User($user->id, $user->name, $_POST['login'], $user->createdAt, $user->lastVisitAt, $user->super == 1);

			if ($user->super != 1)
			{
				$_SESSION['user']->setAllowedFactories(empty($user->allowedFactories) ? array() : unserialize($user->allowedFactories));
				$_SESSION['user']->setAllowedMachines(empty($user->allowedMachines) ? array() : unserialize($user->allowedMachines));
				$_SESSION['user']->setPrivilages(fetch_array(
					'SELECT `privilage` AS `key`, 1 AS `value` FROM role_privilages WHERE role=:role', array(':role' => $user->role === null ? 'user' : $user->role)
				));
			}

			log_info('Zalogowano.');

			go_to(urldecode($referer));
		}
		else
		{
			$errors[] = 'Niepoprawny login i/lub hasło.';

			log_info('Nieudana próba logowania. Login: %s.', $_POST['login']);
		}
	}

	$login = escape($_POST['login']);
}
else
{
	$login = '';
}


?>
<? begin_slot('head') ?>
<style>
#bd { text-align: center; }
#loginBlock { margin: 0 auto; text-align: left; }
#login, #password { width: 20em; }
</style>
<? append_slot() ?>

<? decorate("Logowanie") ?>

<div class="block" id="loginBlock">
	<div class="block-header">
		<h1 class="block-name">Logowanie do systemu</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('user/login.php') ?>">
			<input type="hidden" name="referer" value="<?= e($referer) ?>">
			<fieldset>
				<legend>Logowanie do systemu</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
						<label for="login">E-mail</label>
						<input id="login" name="login" type="text" value="<?= $login ?>">
					<li>
						<label for="password">Hasło</label>
						<input id="password" name="password" type="password" value="">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Zaloguj">
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>
