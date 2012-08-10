<?php

include '../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('variable/delete');

if ($_GET['id'] === ENVIS_COUNTER_VARIABLE)
{
	set_flash(sprintf('Zmienna <%s> jest wykorzystywana w module liczników i nie może zostań usunięta.', ENVIS_COUNTER_VARIABLE), 'error');

	go_to('variable/index.php');
}

$variable = fetch_one('SELECT `name` FROM `variables` WHERE `id`=?', array(1 => $_GET['id']));

if (empty($variable)) not_found();

if (count($_POST))
{
	exec_stmt('DELETE FROM `variables` WHERE `id`=?', array(1 => $_GET['id']));

	log_info('Usunięto zmienną <%s>.', $variable->name);

	set_flash(sprintf('Zmienna <%s> została usunięta pomyślnie.', $variable->name));

	go_to('variable/index.php');
}

$referer = get_referer('variable/view.php?id=' . $_GET['id']);
$errors  = array();

$id   = escape($_GET['id']);
$name = escape($variable->name);

?>

<? decorate("Usuwanie zmiennej") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Usuwanie zmiennej</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('variable/delete.php?id=' . $id) ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Usuwanie zmiennej</legend>
				<p>Na pewno chcesz usunąć zmienną &lt;<?= $name ?>&gt;?</p>
				<p>Wraz ze zmienną usunięte zostaną wszystkie zgromadzone dla niej dane.</p>
				<ol class="form-actions">
					<li><input type="submit" value="Usuń zmienną">
					<li><a href="<?= $referer ?>">Anuluj</a>
				</ol>
			</fieldset>
		</form>
	</div>
</div>