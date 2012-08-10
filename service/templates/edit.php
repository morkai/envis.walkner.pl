<?php

include '../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('service/templates*');

$oldTpl = (array)fetch_one('SELECT id, name FROM issue_templates WHERE id=?', array(1 => $_GET['id']));

if (empty($oldTpl)) not_found();

$referer = get_referer("service/templates/view.php?id={$oldTpl['id']}");
$errors  = array();

if (!empty($_POST['template']))
{
  $template = $_POST['template'] + array('tasks' => array());

	if (!between(1, $template['name'], 255))
	{
		$errors[] = 'Nazwa jest wymagana.';
	}

  if (!empty($errors)) goto VIEW;

  try
  {
    exec_update('issue_templates', array('name' => $template['name']), 'id=' . $oldTpl['id']);

    log_info('Zmodyfikowano szablon zadań <%s>.', $template['name']);

    set_flash(sprintf('Szablon zadań <%s> został zmodyfikowany pomyślnie.', $template['name']));

    go_to($referer);
  }
  catch (PDOException $x)
  {
    $errors[] = $x->getMessage();
  }
}
else
{
	$template = $oldTpl;
}

VIEW:

escape_array($template);

?>

<? decorate("Edycja szablonu zadań") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Edycja szablonu zadań</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("service/templates/edit.php?id={$oldTpl['id']}") ?>" autocomplete="off">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
						<?= label('templateName', 'Nazwa*') ?>
						<input id="templateName" name="template[name]" type="text" maxlength="255" value="<?= $template['name'] ?>">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Edytuj szablon zadań">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>