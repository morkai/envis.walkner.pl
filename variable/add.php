<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('variable/add');

$referer = get_referer('variable');
$errors = array();

if (isset($_POST['id']) && isset($_POST['name']))
{
  if (!preg_match('/^[A-Za-z0-9-_]{1,64}$/', $_POST['id']))
  {
    $errors[] = 'ID musi się składać z od 1 do 64 znaków alfanumerycznych oraz -, _.';
  }

  if (!between(1, $_POST['name'], 128))
  {
    $errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
  }

  if (empty($errors))
  {
    $bindings = array(1 => $_POST['id'], $_POST['name']);

    try
    {
      exec_stmt('INSERT INTO `variables` SET `id`=?, `name`=?', $bindings);

      log_info('Dodano zmienną <%s>.', $_POST['name']);

      set_flash(sprintf('Zmienna <%s> została dodana pomyślnie.', $_POST['name']));

      header('Location: ' . $referer);
      exit;
    }
    catch (PDOException $x)
    {
      if ($x->getCode() == 23000)
      {
        $errors[] = 'Podane ID jest już wykorzystywane przez inny silnik.';
      }
      else
      {
        throw $x;
      }
    }
  }

  $id = escape($_POST['id']);
  $name = escape($_POST['name']);
}
else
{
  $id = '';
  $name = '';
}


?>

<? decorate("Nowa zmienna") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowa zmienna</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('variable/add.php') ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Nowy zmienna</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label for="variable-id">ID<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="variable-id" name="id" type="text" maxlength="64" value="<?= $id ?>">
            <p class="form-field-help">Od 1 do 64 znaków alfanumerycznych oraz -, _.</p>
            <p class="form-field-help">Musi być unikalne.</p>
          <li>
            <label for="variable-name">Nazwa<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="variable-name" name="name" type="text" maxlength="128" value="<?= $name ?>">
            <p class="form-field-help">Od 1 do 128 znaków.</p>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj zmienną">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
