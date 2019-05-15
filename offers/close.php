<?php

include_once './_common.php';

no_access_if_not_allowed('offers/close');

bad_request_if(empty($_GET['id']));

$offer = fetch_one('SELECT id, number, title, clientContact, closedAt FROM offers WHERE id=? LIMIT 1', array(1 => $_GET['id']));

not_found_if(empty($offer));

preg_match('/([a-zA-Z0-9-_.]+@[a-zA-Z0-9-_.]+\.[a-zA-Z]{2,10})/s', $offer->clientContact, $matches);

$referer = get_referer("offers/view.phpg?id={$offer->id}");
$errors = array();
$mail = array_merge(array(
  'subject' => strpos(strtolower($offer->title), 'oferta') === false ? ('Oferta Walkner: ' . $offer->title) : $offer->title,
  'to' => empty($matches[1]) ? '' : $matches[1],
  'text' => ''
), empty($_POST['mail']) ? array() : $_POST['mail']);
$lang = !empty($_POST['lang']) && $_POST['lang'] === 'en' ? 'en' : 'pl';

if (is('post'))
{
  $mail['to'] = array_filter(preg_split('/[ ,;]/', $mail['to']));

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    $bindings = array(
      'updatedAt' => time()
    );

    if (empty($offer->closedAt))
    {
      $bindings['closedAt'] = date('Y-m-d');
    }

    if (!empty($mail['to']))
    {
      $bindings['sentTo'] = implode(', ', $mail['to']);
    }

    exec_update('offers', $bindings, "id={$offer->id}");

    $_GET['format'] = 'html';
    $_GET['force'] = '1';

    ob_start();
    include_once __DIR__ . '/export.php';
    $contents = ob_get_clean();

    $offerHtmlFile = make_offer_file($offer->id, 'html');
    $offerPdfFile = make_offer_file($offer->id, 'pdf');

    if (file_put_contents($offerHtmlFile, $contents) === false)
    {
      throw new Exception('Nie udało się wyeksportować oferty do formatu HTML.');
    }

    $output = array();
    $result = -1;

    if ($_SERVER['HTTP_HOST'] === 'localhost')
    {
      $result = 0;
      $offerPdfFile = make_offer_file(0, 'pdf');
    }
    else
    {
      $scheme = $_SERVER['REQUEST_SCHEME'];

      $_SERVER['REQUEST_SCHEME'] = 'http';

      $cmd = sprintf('wkhtmltopdf -B 25mm -R 10mm -L 10mm -T 32mm --header-spacing 7 --header-html "%s" --footer-html "%s" "%s" "%s"',
               url_for("/offers/print/header.php?id={$offer->id}&lang={$lang}", true),
               url_for("/offers/print/footer.php?id={$offer->id}&lang={$lang}", true),
               url_for("/offers/print/body.php?id={$offer->id}&lang={$lang}", true),
               $offerPdfFile);

      $_SERVER['REQUEST_SCHEME'] = $scheme;

      $errors[] = $cmd;

      exec($cmd, $output, $result);
    }

    if ($result != 0)
    {
      throw new Exception('Nie udało się wyeksportować oferty do formatu PDF.');
    }

    if (!empty($mail['to']))
    {
      $attachment = create_email_attachment($offerPdfFile, str_replace(array('/', '\\'), '-', $offer->number) . '.pdf');
      $message = create_email($mail['to'], $mail['subject'], $mail['text'])->attach($attachment);

      send_email_message($message);
    }

    log_info(sprintf('Wysłano ofertę <%s>.', $offer->title));

    $conn->commit();

    set_flash('Oferta została wysłana pomyślnie.');
    go_to($referer);
  }
  catch (Exception $x)
  {
    $conn->rollBack();

    @unlink($offerHtmlFile);
    @unlink($offerPdfFile);

    $errors[] = $x->getMessage();
  }
}

if (is_array($mail['to']))
{
  $mail['to'] = implode(', ', $mail['to']);
}

?>

<? decorate('Wysyłanie oferty') ?>

<? begin_slot('head') ?>
<style>
li.form-choice ol.form-fields {
  margin-top: 5px;
}
</style>
<? append_slot() ?>

<?= render_message('Przed wysłaniem oferty sprawdź czy jej wygląd jest odpowiedni. [Eksportuj do HTML](/offers/export.php?id=' . $offer->id . '&format=html).', 'warning') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Wysyłanie oferty &lt;<?= e($offer->number) ?>&gt;</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("offers/close.php?id={$offer->id}") ?>">
      <input name="referer" type="hidden" value="<?= $referer ?>">
      <? display_errors($errors) ?>
      <? if (!$offer->closedAt): ?>
      <p>Jeżeli chcesz wysłać i zamknąć ofertę <strong><?= $offer->number ?></strong> wypełnij poniższe pola.
         Do danego adresata zostanie wysłana wiadomość o podanej treści i kopia oferty w formacie PDF jako załącznik.</p>
      <p>W przypadku, gdy chcesz jedynie zamknąć ofertę na zmiany, pozostaw pole <em>Adresat</em> puste.</p>
      <? endif ?>
      <fieldset>
        <ol class="form-fields">
          <li>
            <?= label('mailTo', 'Adresat') ?>
            <input id="mailTo" name="mail[to]" type="text" value="<?= e($mail['to']) ?>" <?= $offer->closedAt ? 'required' : '' ?>>
          <li>
            <?= label('mailSubject', 'Temat') ?>
            <input id="mailSubject" name="mail[subject]" type="text" value="<?= e($mail['subject']) ?>">
          <li class="form-choice">
            <?= render_choice('Język szablonu', 'lang', 'lang', array('pl' => 'Polski', 'en' => 'Angielski'), $lang) ?>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Wyślij ofertę">
              <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
