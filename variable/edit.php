<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('variable/edit');

$variable = fetch_one('SELECT `name` FROM `variables` WHERE `id`=?', array(1 => $_GET['id']));

not_found_if(empty($variable));

$referer = get_referer('variable/view.php?id=' . $_GET['id']);
$errors = array();

if (isset($_POST['name']))
{
  if (!between(1, $_POST['name'], 128))
  {
    $errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
  }

  if (empty($errors))
  {
    $bindings = array(1 => $_POST['name'], $_GET['id']);

    exec_stmt('UPDATE `variables` SET `name`=? WHERE `id`=?', $bindings);

    log_info('Zmodyfikowano zmienną <%s>.', $variable->name);

    set_flash(sprintf('Zmienna <%s> została zmodyfikowana pomyślnie.', $variable->name));

    go_to($referer);
  }

  $name = escape($_POST['name']);
}
else
{
  $name = escape($variable->name);
}

$id = escape($_GET['id']);

?>

<? decorate("Edycja zmiennej") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja zmiennej &lt;<?= $id ?>&gt;</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('variable/edit.php?id=' . $id) ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Edycja zmiennej</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label for="variable-name">Nazwa:</label>
            <input id="variable-name" name="name" type="text" maxlength="128" value="<?= $name ?>">
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Edytuj zmienną">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
