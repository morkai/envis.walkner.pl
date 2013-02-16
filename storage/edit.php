<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('storage*');

$storage_ = fetch_one('SELECT * FROM storages WHERE id=:id', array(':id' => $_GET['id']));

if (empty($storage_)) not_found();

no_access_if_not($_SESSION['user']->isSuper() || ($storage_->owner == $_SESSION['user']->getId()));

$errors = array();
$referer = get_referer('storage/view.php?id=' . $storage_->id);

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
      ':id' => $storage_->id,
      ':name' => $storage['name'],
    );

    $conn = get_conn();

    try
    {
      $conn->beginTransaction();

      exec_stmt('UPDATE storages SET name=:name WHERE id=:id', $bindings);

      $id = get_conn()->lastInsertId();

      $conn->commit();

      log_info('Zmodyfikowano magazyn <%s>.', $storage['name']);

      set_flash(sprintf('Magazyn <%s> został zmodyfikowany pomyślnie.', $storage['name']));

      go_to($referer);
    }
    catch (PDOException $x)
    {
      $conn->rollBack();

      set_flash('Magazyn nie został zmodyfikowany. ' . $x, 'error');

      go_to($referer);
    }
  }
}
else
{
  $storage = array(
    'name' => $storage_->name,
  );
}

escape_array($storage);


?>

<? decorate("Edycja magazynu") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja magazynu</h1>
  </div>
  <div class="block-body">
    <form name="editStorage" method="post" action="<?= url_for("storage/edit.php?id={$storage_->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Edycja magazynu</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label for="editStorage-name">Nazwa<span class="form-field-required" name="Wymagane">*</span></label>
            <input id="editStorage-name" name="storage[name]" type="text" maxlength="128" value="<?= $storage['name'] ?>">
            <p class="form-field-help">Od 1 do 128 znaków.</p>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Edytuj magazyn">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
