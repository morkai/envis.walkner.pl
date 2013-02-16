<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('user/edit/roles');

$rola = fetch_one('SELECT * FROM roles WHERE id=:id', array(':id' => $_GET['id']));

not_found_if(empty($rola));

$referer = get_referer('user/roles/view.php?id=' . $rola->id);
$errors = array();

if (isset($_POST['role']))
{
  $role = $_POST['role'];

  if (empty($role['name']))
  {
    $errors[] = 'Nazwa jest wymagana.';
  }

  if (empty($errors))
  {
    $bindings = array(
      ':id' => $rola->id,
      ':name' => $role['name']
    );

    exec_stmt('UPDATE roles SET name=:name WHERE id=:id', $bindings);

    log_info('Zmodyfikowano rolę <%s>.', $rola->name);

    set_flash(sprintf('Rola <%s> została zmodyfikowana pomyślnie', $rola->name));

    go_to('user/role/view.php?id=' . $rola->id);
  }

  escape_array($role);
}
else
{
  $role = array('name' => $rola->name);
}


?>

<? decorate("Edycja roli użytkownika") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja roli użytkownika</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('user/role/edit.php?id=' . $rola->id) ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Edycja roli</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label for="role-id">ID</label>
            <p><?= $rola->id ?></p>
          <li>
            <label for="role-name">Nazwa<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="role-name" name="role[name]" type="text" maxlength="128" value="<?= $role['name'] ?>">
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Edytuj rolę użytkownika">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
