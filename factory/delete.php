<?php

include '../_common.php';

if (!isset($_GET['id'])) bad_request();

no_access_if_not(is_allowed_to('factory/delete'), has_access_to_factory($_GET['id']));

$factory = fetch_one('SELECT `name` FROM `factories` WHERE `id`=?', array(1 => $_GET['id']));

if (empty($factory)) not_found();

if (count($_POST))
{
	exec_stmt('DELETE FROM `factories` WHERE `id`=?', array(1 => $_GET['id']));

	log_info('Usunięto fabrykę <%s>.', $factory->name);

	set_flash(sprintf('Fabryka <%s> została usunięta pomyślnie.', $factory->name));

	go_to('factory');
}

$referer = get_referer('factory/view.php?id=' . $_GET['id']);
$errors  = array();

$id   = escape($_GET['id']);
$name = escape($factory->name);

?>
<? decorate("Usuwanie fabryki") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Usuwanie fabryki</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('factory/delete.php?id=' . $id) ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Usuwanie fabryki</legend>
				<p>Na pewno chcesz usunąć fabrykę &lt;<?= $name ?>&gt;?</p>
				<p>Wraz z fabryką usunięte zostaną wszystkie zdefiniowane dla niej maszyny.</p>
				<ol class="form-actions">
					<li><input type="submit" value="Usuń fabrykę">
					<li><a href="<?= $referer ?>">Anuluj</a>
				</ol>
			</fieldset>
		</form>
	</div>
</div>