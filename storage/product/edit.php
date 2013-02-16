<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('storage*');

$product_ = fetch_one('SELECT s.name AS storageName, s.owner, p.* FROM storage_products p INNER JOIN storages s ON s.id=p.storage WHERE p.id=:id', array(':id' => $_GET['id']));

if (empty($product_)) not_found();

no_access_if_not($_SESSION['user']->isSuper() || ($product_->owner == $_SESSION['user']->getId()));

$errors = array();
$referer = get_referer('storage/product/view.php?id=' . $product_->id);

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
      ':id' => $product_->id,
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

      exec_stmt('UPDATE storage_products SET name=:name, `index`=:index, quantity=:quantity, price=:price, supplier=:supplier, contact=:contact WHERE id=:id', $bindings);

      $id = get_conn()->lastInsertId();

      $conn->commit();

      log_info('Zmodyfikowano produkt <%s> z magazynu <%s>.', $product['name'], $product_->storageName);

      set_flash(sprintf('Produkt <%s> z magazynu <%s> został zmodyfikowany pomyślnie.', $product['name'], $product_->storageName));

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
        set_flash('Produkt nie został zmodyfikowany. ' . $x, 'error');

        go_to($referer);
      }
    }
  }
}
else
{
  $product = array(
    'name' => $product_->name,
    'index' => $product_->index,
    'quantity' => $product_->quantity,
    'price' => $product_->price,
    'supplier' => $product_->supplier,
    'contact' => $product_->contact,
  );
}

$product['storage'] = $product_->storageName;

escape_array($product);


?>

<? decorate("Edycja produktu") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja produktu</h1>
  </div>
  <div class="block-body">
    <form name="editProduct" method="post" action="<?= url_for("storage/product/edit.php?id={$product_->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Edycja produktu</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label>Magazyn</label>
            <p><?= $product['storage'] ?></p>
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
              <li><input type="submit" value="Edytuj produkt">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
