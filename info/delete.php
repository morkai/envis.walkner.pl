<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('info*');

$news = fetch_one('SELECT title FROM news WHERE id=:id', array(':id' => $_GET['id']));

not_found_if(empty($news));

if (count($_POST))
{
  exec_stmt('DELETE FROM news WHERE id=:id', array(':id' => $_GET['id']));

  log_info('Usunięto informację <%s>.', $news->title);

  set_flash(sprintf('Informacja <%s> została usunięta pomyślnie.', $news->title));

  go_to('info/');
}

$referer = get_referer('info/view.php?id=' . $_GET['id']);
$errors = array();

$id = escape($_GET['id']);
$title = escape($news->title);

?>

<? decorate("Usuwanie informacji") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie informacji</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('info/delete.php?id=' . $id) ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Usuwanie informacji</legend>
        <p>Na pewno chcesz usunąć informację &lt;<?= $title ?>&gt;?</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń informację">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
