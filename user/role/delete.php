<?php

include '../../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not(is_allowed_to('user/edit/roles'), strtolower($_GET['id']) !== 'user');

$role = fetch_one('SELECT name FROM roles WHERE id=?', array(1 => $_GET['id']));

if (empty($role)) not_found();

if (count($_POST))
{
	exec_stmt('DELETE FROM roles WHERE id=?', array(1 => $_GET['id']));

	log_info('Usunięto rolę <%s>.', $role->name);
	
	set_flash(sprintf('Rola <%s> została usunięta pomyślnie.', $role->name));

	go_to('user/role/');
}

$referer = get_referer('user/role/view.php?id=' . $_GET['id']);
$errors  = array();

$id   = escape($_GET['id']);
$name = escape($role->name);

?>

<? decorate("Usuwanie roli użytkownika") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Usuwanie roli użytkownika</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('user/role/delete.php?id=' . $id) ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Usuwanie roli</legend>
				<p>Na pewno chcesz usunąć rolę &lt;<?= $name ?>&gt;?</p>
				<ol class="form-actions">
					<li><input type="submit" value="Usuń rolę użytkownika">
					<li><a href="<?= $referer ?>">Anuluj</a>
				</ol>
			</fieldset>
		</form>
	</div>
</div>