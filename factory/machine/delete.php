<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not(is_allowed_to('machine/delete'), has_access_to_machine($_GET['id']));

$machine = fetch_one('SELECT `name`, factory FROM `machines` WHERE `id`=?', array(1 => $_GET['id']));

not_found_if(empty($machine));

if (count($_POST))
{
  exec_stmt('DELETE FROM `machines` WHERE `id`=?', array(1 => $_GET['id']));

  log_info('Usunięto maszynę <%s>.', $machine->name);

  set_flash(sprintf('Maszyna <%s> została usunięta pomyślnie.', $machine->name));

  go_to('factory/view.php?id=' . $machine->factory);
}

$referer = get_referer('factory/machine/?id=' . $_GET['id']);
$errors = array();

$id = escape($_GET['id']);
$name = escape($machine->name);

?>

<? decorate("Usuwanie maszyny") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie maszyny</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('factory/machine/delete.php?id=' . $id) ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Usuwanie maszyny</legend>
        <p>Na pewno chcesz usunąć maszynę &lt;<?= $name ?>&gt;?</p>
        <p>Wraz z maszyną usunięte zostaną wszystkie zdefiniowane dla niej urządzenia.</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń maszynę">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
