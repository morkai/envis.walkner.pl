<?php

include_once __DIR__ . '/../../_common.php';

no_access_if_not_allowed('user/edit/roles');

$referer = get_referer('user/roles/');
$errors = array();

if (isset($_POST['role']))
{
  $role = $_POST['role'];

  if (empty($role['id']))
  {
    $errors[] = 'ID jest wymagane.';
  }
  elseif (!preg_match('#^[A-Za-z0-9_]+$#', $role['id']))
  {
    $errors[] = 'ID zawiera niepoprawne znaki.';
  }

  if (empty($role['name']))
  {
    $errors[] = 'Nazwa jest wymagana.';
  }

  if (empty($errors))
  {
    $bindings = array(
      ':id' => $role['id'],
      ':name' => $role['name']
    );

    try
    {
      exec_stmt('INSERT INTO roles SET id=:id, name=:name', $bindings);

      log_info('Dodano rolę <%s>.', $role['name']);

      set_flash(sprintf('Rola <%s> została dodana pomyślnie', $role['name']));

      go_to('user/role/view.php?id=' . $role['id']);
    }
    catch (PDOException $x)
    {
      if ($x->getCode() == 23000)
      {
        $errors[] = 'ID jest już wykorzystywane przez inną rolę.';
      }
      else
      {
        throw $x;
      }
    }
  }

  escape_array($role);
}
else
{
  $role = array('id' => '', 'name' => '');
}


?>

<? decorate("Nowa rola użytkownika") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowa rola</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('user/role/add.php') ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Nowa rola</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label for="role-id">ID<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="role-id" name="role[id]" type="text" maxlength="64" value="<?= $role['id'] ?>">
            <p class="form-field-help">Musi być unikalne.</p>
          <li>
            <label for="role-name">Nazwa<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="role-name" name="role[name]" type="text" maxlength="128" value="<?= $role['name'] ?>">
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj rolę">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
