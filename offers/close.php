<?php

include_once './_common.php';

no_access_if_not_allowed('offers/close');

if (empty($_GET['id'])) bad_request();

$offer = fetch_one('SELECT id, number, title, clientContact, closedAt FROM offers WHERE id=? LIMIT 1', array(1 => $_GET['id']));

if (empty($offer)) not_found();

if (!empty($offer->closedAt)) bad_request();

preg_match('/([a-zA-Z0-9-_.]+@[a-zA-Z0-9-_.]+\.[a-zA-Z]{2,10})/s', $offer->clientContact, $matches);

$referer = get_referer("offers/view.phpg?id={$offer->id}");
$errors  = array();
$mail    = array_merge(array(
  'subject' => strpos(strtolower($offer->title), 'oferta') === false ? ('Oferta: ' . $offer->title) : $offer->title,
  'to'      => empty($matches[1]) ? '' : $matches[1],
  'text'    => ''
), empty($_POST['mail']) ? array() : $_POST['mail']);

if (is('post'))
{
  $mail['to'] = array_filter(preg_split('/[ ,;]/', $mail['to']));

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    $bindings = array('closedAt' => date('Y-m-d'));

    if (!empty($mail['to']))
    {
      $bindings['sentTo'] = implode(', ', $mail['to']);
    }

    exec_update('offers', $bindings, "id={$offer->id}");

    $_GET['format'] = 'html';

    ob_start();
    include './export.php';
    $contents = ob_get_clean();

    $offerHtmlFile = make_offer_file($offer->id, 'html');
    $offerPdfFile  = make_offer_file($offer->id, 'pdf');

    if (file_put_contents($offerHtmlFile, $contents) === false)
    {
      throw new Exception('Nie udało się wyeksportować oferty do formatu HTML.');
    }

    $output = array();
    $result = -1;

    if ($_SERVER['HTTP_HOST'] === 'localhost')
    {
      $result       = 0;
      $offerPdfFile = make_offer_file(0, 'pdf');
    }
    else
    {
      $cmd = sprintf('wkhtmltopdf -B 23mm -R 0 -L 0 -T 23mm --header-spacing 7 --header-html %s --footer-html %s %s %s',
               url_for("/offers/print/header.php?id={$offer->id}", true),
               url_for("/offers/print/footer.php?id={$offer->id}", true),
               url_for("/offers/print/body.php?id={$offer->id}", true),
               $offerPdfFile);

      $errors[] = $cmd;

      exec($cmd, $output, $result);
    }

    if ($result != 0)
    {
      throw new Exception('Nie udało się wyeksportować oferty do formatu PDF.');
    }

    if (!empty($mail['to']))
    {
      require_once __DIR__ . '/../_lib_/swiftmailer/swift_required.php';

      $mailer = Swift_Mailer::newInstance(Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'tls')
        ->setUsername(ENVIS_SMTP_USER)
        ->setPassword(ENVIS_SMTP_PASS));
      $mailer->send(Swift_Message::newInstance()
        ->setSubject($mail['subject'])
        ->setFrom(ENVIS_SMTP_FROM_EMAIL, ENVIS_SMTP_FROM_NAME)
        ->setTo($mail['to'])
        ->setBody($mail['text'])
        ->setReplyTo(ENVIS_SMTP_REPLY_EMAIL, ENVIS_SMTP_REPLY_NAME)
        ->attach(Swift_Attachment::fromPath($offerPdfFile)
                                 ->setFilename(str_replace(array('/', '\\'), '-', $offer->number) . '.pdf')));
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

<?= render_message('Przed wysłaniem oferty sprawdź czy jej wygląd jest odpowiedni. [Eksportuj do HTML](/offers/export.php?id=' . $offer->id . '&format=html).', 'warning') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Wysyłanie oferty &lt;<?= e($offer->number) ?>&gt;</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("offers/close.php?id={$offer->id}") ?>">
      <input name="referer" type="hidden" value="<?= $referer ?>">
      <? display_errors($errors) ?>
      <p>Jeżeli chcesz wysłać i zamknąć ofertę <strong><?= $offer->number ?></strong> wypełnij poniższe pola.
         Do danego adresata zostanie wysłana wiadomość o podanej treści i kopia oferty w formacie PDF jako załącznik.</p>
      <p>W przypadku, gdy chcesz jedynie zamknąć ofertę na zmiany, pozostaw pole <em>Adresat</em> puste.</p>
      <ol class="form-fields">
        <li>
          <?= label('mailTo', 'Adresat') ?>
          <input id="mailTo" name="mail[to]" type="text" value="<?= e($mail['to']) ?>">
        <li>
          <?= label('mailSubject', 'Temat') ?>
          <input id="mailSubject" name="mail[subject]" type="text" value="<?= e($mail['subject']) ?>">
        <li>
          <?= label('mailText', 'Treść') ?>
          <textarea id="mailText" name="mail[text]"><?= e($mail['text']) ?></textarea>
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Wyślij ofertę">
            <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
          </ol>
      </ol>
    </form>
  </div>
</div>
