<?php

include '../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('storage*');

$storage = fetch_one('SELECT * FROM storages WHERE id=:id', array(':id' => $_GET['id']));

if (empty($storage)) not_found();

no_access_if_not($_SESSION['user']->isSuper() || ($storage->owner == $_SESSION['user']->getId()));

$referer = get_referer('storage/view.php?id=' . $storage->id);

if (count($_POST))
{
	$conn = get_conn();

	try
	{
		$conn->beginTransaction();

		exec_stmt('DELETE FROM storages WHERE id=:id', array(':id' => $storage->id));

		$conn->commit();

		log_info('Usunięto magazyn <%s>.', $storage->name);

		set_flash(sprintf('Magazyn <%s> został usunięty pomyślnie.', $storage->name));

		go_to('storage/');
	}
	catch (PDOException $x)
	{
		set_flash(sprintf('Magazyn <%s> nie został usunięty.', $storage->name), 'error');

		go_to($referer);
	}
}

escape_var($storage->name);

?>

<? decorate("Usuwanie magazynu") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Usuwanie magazynu</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("storage/delete.php?id={$storage->id}") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Usuwanie magazynu</legend>
				<p>Na pewno chcesz usunąć magazyn &lt;<?= $storage->name ?>&gt;?</p>
				<ol class="form-actions">
					<li><input type="submit" value="Usuń magazyn">
					<li><a href="<?= $referer ?>">Anuluj</a>
				</ol>
			</fieldset>
		</form>
	</div>
</div>