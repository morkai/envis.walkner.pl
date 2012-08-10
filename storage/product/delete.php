<?php

include '../../_common.php';

if (empty($_GET['id'])) bad_request();

no_access_if_not_allowed('storage*');

$product = fetch_one('SELECT s.owner, s.name AS storageName, p.name, p.id, p.storage FROM storage_products p INNER JOIN storages s ON s.id=p.storage WHERE p.id=:id', array(':id' => $_GET['id']));

if (empty($product)) not_found();

no_access_if_not($_SESSION['user']->isSuper() || ($product->owner == $_SESSION['user']->getId()));

$referer = get_referer('storage/product/view.php?id=' . $product->id);

if (count($_POST))
{
	$conn = get_conn();

	try
	{
		$conn->beginTransaction();

		exec_stmt('DELETE FROM storage_products WHERE id=:id', array(':id' => $product->id));

		$conn->commit();

		log_info('Usunięto produkt <%s> z magazynu <%s>.', $product->name, $product->storageName);

		set_flash(sprintf('Produkt <%s> został pomyślnie usunięty z magazynu <%s>.', $product->name, $product->storageName));

		go_to('storage/view.php?id=' . $product->storage);
	}
	catch (PDOException $x)
	{
		set_flash(sprintf('Produkt <%s> nie został usunięty z magazynu <%s>.', $product->name, $product->storageName), 'error');

		go_to($referer);
	}
}

escape_var($product->name);

?>

<? decorate("Usuwanie produktu z magazynu") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Usuwanie produktu</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for("storage/product/delete.php?id={$product->id}") ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<fieldset>
				<legend>Usuwanie magazynu</legend>
				<p>Na pewno chcesz usunąć produkt &lt;<?= $product->name ?>&gt; z magazynu &lt;<?= $product->storageName ?>&gt;?</p>
				<ol class="form-actions">
					<li><input type="submit" value="Usuń produkt">
					<li><a href="<?= $referer ?>">Anuluj</a>
				</ol>
			</fieldset>
		</form>
	</div>
</div>