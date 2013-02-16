<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['storage']));

no_access_if_not_allowed('storage*');

$storage = fetch_one('SELECT * FROM storages WHERE id=:id', array(':id' => $_GET['storage']));

not_found_if(empty($storage));

no_access_if_not($_SESSION['user']->isSuper() || ($storage->owner == $_SESSION['user']->getId()));

$errors = array();
$referer = get_referer('storage/view.php?id=' . $storage->id);

if (isset($_POST['product']))
{
  $product = $_POST['product'];

  if (!between(1, $product['index'], 64))
  {
    $errors[] = 'Indeks musi się składać z od 1 do 64 znaków.';
  }

  if (!between(1, $product['name'], 128))
  {
    $errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
  }

  if (empty($errors))
  {
    $bindings = array(
      ':storage' => $storage->id,
      ':name' => $product['name'],
      ':index' => $product['index'],
      ':quantity' => $product['quantity'] < 0 ? 0 : (int)$product['quantity'],
      ':price' => storage_format_price($product['price']),
      ':supplier' => $product['supplier'],
      ':contact' => $product['contact'],
    );

    $conn = get_conn();

    try
    {
      $conn->beginTransaction();

      exec_stmt('INSERT INTO storage_products SET storage=:storage, name=:name, `index`=:index, quantity=:quantity, price=:price, supplier=:supplier, contact=:contact', $bindings);

      $id = get_conn()->lastInsertId();

      $conn->commit();

      log_info('Dodano produkt <%s> do magazynu <%s>.', $product['name'], $storage->name);

      set_flash(sprintf('Produkt <%s> został pomyślnie dodany do magazynu <%s>.', $product['name'], $storage->name));

      go_to($referer);
    }
    catch (PDOException $x)
    {
      $conn->rollBack();

      if ($x->getCode() == 23000)
      {
        $errors[] = 'Podany indeks jest już wykorzystany przy innym produkcie.';
      }
      else
      {
        set_flash('Produkt nie został dodany. ' . $x, 'error');

        go_to($referer);
      }
    }
  }
}
else
{
  $product = array(
    'name' => '',
    'index' => '',
    'quantity' => '0',
    'price' => '0.00',
    'supplier' => '',
    'contact' => '',
  );
}

$product['storage'] = $storage->name;

escape_array($product);


?>
<? begin_slot('head') ?>
<style>
</style>
<? append_slot() ?>

<? decorate("Dodawanie produktu do magazynu <{$storage->name}>") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Dodawanie produktu</h1>
  </div>
  <div class="block-body">
    <form name="editProduct" method="post" action="<?= url_for("storage/product/add.php?storage={$storage->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Dodawanie produktu</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label>Magazyn</label>
            <p><?= e($product['storage']) ?></p>
          <li>
            <label for="editProduct-index">Indeks<span class="form-field-required" name="Wymagane">*</span></label>
            <input id="editProduct-index" name="product[index]" type="text" maxlength="128" value="<?= $product['index'] ?>">
            <p class="form-field-help">Od 1 do 64 znaków.</p>
            <p class="form-field-help">Unikalny dla magazynu.</p>
          <li>
            <label for="editProduct-name">Nazwa<span class="form-field-required" name="Wymagane">*</span></label>
            <input id="editProduct-name" name="product[name]" type="text" maxlength="128" value="<?= $product['name'] ?>">
            <p class="form-field-help">Od 1 do 128 znaków.</p>
          <li>
            <label for="editProduct-price">Cena za sztukę</label>
            <input id="editProduct-price" name="product[price]" type="text" maxlength="32" value="<?= $product['price'] ?>">
          <li>
            <label for="editProduct-quantity">Ilość</label>
            <input id="editProduct-quantity" name="product[quantity]" type="text" maxlength="16" value="<?= $product['quantity'] ?>">
          <li>
            <label for="editProduct-supplier">Dostawca</label>
            <textarea id="editProduct-supplier" name="product[supplier]"><?= $product['supplier'] ?></textarea>
          <li>
            <label for="editProduct-contact">Kontakt</label>
            <textarea id="editProduct-contact" name="product[contact]"><?= $product['contact'] ?></textarea>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj produkt">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
