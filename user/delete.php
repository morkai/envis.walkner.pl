<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(!isset($_GET['id']) || ($_GET['id'] == $_SESSION['user']->getId()));

no_access_if_not_allowed('user/delete');

$user = fetch_one('SELECT name FROM users WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($user));

if (count($_POST))
{
  exec_stmt('DELETE FROM users WHERE id=?', array(1 => $_GET['id']));

  log_info('Usunięto użytkownika <%s>.', $user->name);

  set_flash(sprintf('Użytkownik <%s> został usunięty pomyślnie.', $user->name));

  go_to('user/');
}

$referer = get_referer('user/view.php?id=' . $_GET['id']);
$errors = array();

$id = escape($_GET['id']);
$name = escape($user->name);

?>

<? decorate("Usuwanie użytkownika") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie użytkownika</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('user/delete.php?id=' . $id) ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Usuwanie użytkownika</legend>
        <p>Na pewno chcesz usunąć użytkownika &lt;<?= $name ?>&gt;?</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń użytkownika">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
