<?php

include '../_common.php';

no_access_if_not_allowed('storage*');

$errors  = array();
$referer = get_referer('storage/');

if (isset($_POST['storage']))
{
	$storage = $_POST['storage'];

	if (!between(1, $storage['name'], 128))
	{
		$errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
	}

	if (empty($errors))
	{
		$bindings = array(
			':owner' => $_SESSION['user']->getId(),
			':name'  => $storage['name'],
		);

		$conn = get_conn();
		
		try
		{
			$conn->beginTransaction();

			exec_stmt('INSERT INTO storages SET name=:name, owner=:owner', $bindings);

			$id = get_conn()->lastInsertId();

			$conn->commit();

			log_info('Dodano magazyn <%s>.', $storage['name']);

			set_flash(sprintf('Magazyn <%s> został dodany pomyślnie.', $storage['name']));

			go_to('storage/view.php?id=' . $id);
		}
		catch (PDOException $x)
		{
			$conn->rollBack();

			set_flash('Magazyn nie został dodany. ' . $x, 'error');

			go_to($referer);
		}
	}
}
else
{
	$storage = array(
		'name' => '',
	);
}

escape_array($storage);


?>

<? decorate("Dodawanie nowego magazynu") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Nowy magazyn</h1>
	</div>
	<div class="block-body">
		<form name="newStorage" method="post" action="<?= url_for('storage/add.php') ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Nowy magazyn</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
						<label for="newStorage-name">Nazwa<span class="form-field-required" name="Wymagane">*</span></label>
						<input id="newStorage-name" name="storage[name]" type="text" maxlength="128" value="<?= $storage['name'] ?>">
						<p class="form-field-help">Od 1 do 128 znaków.</p>
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Dodaj magazyn">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>