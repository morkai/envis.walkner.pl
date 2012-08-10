<?php

include '../../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('user/edit/roles');

$role = fetch_one('SELECT * FROM roles WHERE id=? LIMIT 1', array(1 => $_GET['id']));

if (empty($role)) not_found();

$allPrivilages  = include '../../_privilages.php';

if (!empty($_POST))
{
	$conn = get_conn();

	try
	{
		$conn->beginTransaction();

		exec_stmt('DELETE FROM role_privilages WHERE role=:role', array(':role' => $role->id));

		$c = count($_POST['privilage']) - 1;

		if ($c >= 0)
		{
			$query = 'INSERT INTO role_privilages (role, privilage) VALUES';

			foreach ($_POST['privilage'] as $k => $privilage)
			{
				if (!isset($allPrivilages[$privilage])) continue;

				$query .= "(:role, '{$privilage}')";
				$query .= $k === $c ? '' : ', ';
			}

			exec_stmt($query, array(':role' => $role->id));
		}

		$conn->commit();

		log_info('Zmieniono uprawnienia roli <%s>.', $role->name);

		set_flash(sprintf('Uprawnienia roli <%s> zostały zmienione pomyślnie', $role->name));

		go_to('user/role/view.php?id=' . $role->id);
	}
	catch (PDOException $x)
	{
		$conn->rollBack();

		set_flash(sprintf('Uprawnienia roli <%s> zostały nie zmienione.', $role->name), 'error');

		go_to('user/role/view.php?id=' . $role->id);
	}
}

escape_var($role->name);

$rolePrivilages = fetch_array('SELECT 1 AS `value`, privilage AS `key` FROM role_privilages WHERE role=:role', array(':role' => $role->id));

$canDelete = $role->id !== 'user';

$i = 0;
$c = 0;
$g = '';

?>
<? begin_slot('head') ?>
<style>
	.level-3 { margin-left: 2em; }
	.level-4 { margin-left: 4em; }
	.group { margin-top: 2em; }
	#privilages .form-fields { float: left; margin-right: 2em; }
</style>
<? append_slot() ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<li><a href="<?= url_for("user/role/edit.php?id={$role->id}") ?>">Edytuj rolę</a>
	<? if ($canDelete): ?><li><a href="<?= url_for("user/role/delete.php?id={$role->id}") ?>">Usuń rolę</a><? endif ?>
</ul>
<? append_slot() ?>

<? decorate("Rola użytkownika") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Rola &lt;<?= $role->id ?>&gt;</h1>
	</div>
	<div class="block-body">
		<dl>
			<dt>Nazwa</dt>
			<dd><?= $role->name ?></dd>
		</dl>
	</div>
</div>
<div class="block">
	<div class="block-header">
		<h1 class="block-name">Uprawnienia</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('user/role/view.php?id=' . $role->id) ?>">
			<input type="hidden" name="flush" value="1">
			<fieldset>
				<legend>Uprawnienia</legend>
				<ol class="form-fields">
					<li class="form-choice" id="privilages">
						<ol class="form-fields">
							<? foreach ($allPrivilages as $privilage => $label): ?>
							<?
								$parts = explode('/', $privilage);
								$class = 'level-' . count($parts);

								if ($parts[0] !== $g && $i > 0)
								{
									$class .= ' group';

									$g = $parts[0];

									if ($c === 10)
									{
										echo '</ol><ol class="form-fields">';
										
										$c = 1;
									}
								}
								elseif ($c < 10)
								{
									++$c;
								}
							?>
							<li class="<?= $class ?>">
								<input name="privilage[]" type="checkbox" value="<?= $privilage ?>" id="privilage-<?= $i ?>" <?= checked_if(isset($rolePrivilages[$privilage])) ?>>
								<label for="privilage-<?= $i++ ?>"><?= escape($label) ?></label>
							<? endforeach ?>
						</ol>
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Zapisz uprawnienia">
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>