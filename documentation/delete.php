<?php

include './_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('documentation/delete');

$doc = fetch_one('SELECT title, machine FROM documentations WHERE id=?', array(1 => $_GET['id']));

if (empty($doc)) not_found();

no_access_if_not(has_access_to_machine($doc->machine));

$referer = get_referer('documentation/view.php?id=' . $_GET['id']);

if (count($_POST))
{
	$conn = get_conn();

	try
	{
		$conn->beginTransaction();

		$files = fetch_all('SELECT file FROM documentation_files WHERE documentation=?', array(1 => $_GET['id']));

		exec_stmt('DELETE FROM documentations WHERE id=?', array(1 => $_GET['id']));
		
		foreach ($files as $file)
		{
			$file = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/documentation/' . $file->file;

			if (file_exists($file))
			{
				unlink($file);
			}
		}

		$conn->commit();

		log_info('Usunięto dokumentację <%s>.', $doc->title);

		set_flash(sprintf('Dokumentacja <%s> została usunięta pomyślnie.', $doc->title));

		go_to('documentation/');
	}
	catch (PDOException $x)
	{
		set_flash(sprintf('Dokumentacja <%s> nie została usunięta.', $doc->title), 'error');

		go_to($referer);
	}
}

$id    = escape($_GET['id']);
$title = escape($doc->title);

?>

<? decorate("Usuwanie dokumentacji") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Usuwanie dokumentacji</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('documentation/delete.php?id=' . $id) ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Usuwanie dokumentacji</legend>
				<p>Na pewno chcesz usunąć dokumentację &lt;<?= $title ?>&gt;?</p>
				<ol class="form-actions">
					<li><input type="submit" value="Usuń dokumentację">
					<li><a href="<?= $referer ?>">Anuluj</a>
				</ol>
			</fieldset>
		</form>
	</div>
</div>