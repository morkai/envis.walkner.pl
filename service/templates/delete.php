<?php

include '../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('service/templates*');

$template = fetch_one('SELECT id, name FROM issue_templates WHERE id=:id', array(':id' => $_GET['id']));

if (empty($template)) not_found();

$referer = get_referer('service/templates/view.php?id=' . $template->id);

if (count($_POST))
{
  exec_stmt('DELETE FROM issue_templates WHERE id=:id', array(':id' => $template->id));

  log_info('Usunięto szablon zadań <%s>.', $template->name);

  set_flash(sprintf('Szablon zadań <%s> został usunięty.', $template->name));

  go_to('service/templates/');
}

?>

<? decorate("Usuwanie szablonu zadań") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Usuwanie szablonu zadań</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("service/templates/delete.php?id={$template->id}") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<p>Na pewno chcesz usunąć szablon zadań &lt;<?= e($template->name) ?>&gt;?</p>
				<ol class="form-actions">
					<li><input type="submit" value="Usuń szablon zadań">
					<li><a href="<?= $referer ?>">Anuluj</a>
				</ol>
			</fieldset>
		</form>
	</div>
</div>