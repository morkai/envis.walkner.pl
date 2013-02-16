<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('info*');

$errors = array();
$referer = get_referer('info/');

if (isset($_POST['news']))
{
  $news = $_POST['news'];

  if (!between(1, $news['title'], 128))
  {
    $errors[] = 'Tytuł musi się składać z od 1 do 128 znaków.';
  }

  if (empty($news['introduction']))
  {
    $errors[] = 'Pole wstęp jest wymagane.';
  }

  if (empty($errors))
  {
    $bindings = array(
      ':title' => $news['title'],
      ':introduction' => $news['introduction'],
      ':body' => $news['body'],
      ':createdAt' => gmdate('Y-m-d H:i:s'),
      ':createdBy' => $_SESSION['user']->getId()
    );

    exec_stmt('INSERT INTO news SET title=:title, introduction=:introduction, body=:body, createdAt=:createdAt, createdBy=:createdBy', $bindings);

    $id = get_conn()->lastInsertId();

    log_info('Dodano informację <%s>.', $news['title']);

    set_flash(sprintf('Informacja <%s> została dodana pomyślnie.', $news['title']));

    go_to('info/view.php?id=' . $id);
  }
}
else
{
  $news = array(
    'title' => '',
    'introduction' => '',
    'body' => '',
  );
}

escape_array($news);

?>

<? begin_slot('head') ?>
<style>
#news-body { height: 20em; }
</style>
<? append_slot() ?>

<? decorate("Dodawanie nowej informacji") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowa informacja</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('info/add.php') ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Nowa informacja</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label for="news-title">Tytuł<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="news-title" name="news[title]" type="text" maxlength="128" value="<?= $news['title'] ?>">
            <p class="form-field-help">Od 1 do 128 znaków.</p>
          <li>
            <label for="news-introduction">Wstęp<span class="form-field-required" title="Wymagane">*</span></label>
            <textarea class="markdown" id="news-introduction" name="news[introduction]"><?= $news['introduction'] ?></textarea>
          <li>
            <label for="news-body">Rozwinięcie</label>
            <textarea class="markdown resizable" id="news-body" name="news[body]"><?= $news['body'] ?></textarea>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj informację">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
