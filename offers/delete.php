<?php

include_once './_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('offers/delete');

$referer = get_referer('offers/view.php?id=' . $_GET['id']);
$offer = fetch_one('SELECT id, title, issue, closedAt FROM offers WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($offer));

if (count($_POST))
{
  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    $htmlFile = make_offer_file($offer->id, 'html');
    $pdfFile = make_offer_file($offer->id, 'pdf');

    if (file_exists($htmlFile)) unlink($htmlFile);
    if (file_exists($pdfFile)) unlink($pdfFile);

    exec_stmt('DELETE FROM offers WHERE id=?', array(1 => $offer->id));
    // @todo close related issue

    log_info('Usunięto ofertę <%s>.', $offer->title);

    $conn->commit();

    set_flash(sprintf('Oferta <%s> została usunięta pomyślnie.', $offer->title));
    go_to('offers/');
  }
  catch (PDOException $x)
  {
    $conn->rollBack();

    set_flash($x->getMessage(), 'error');
    go_to($referer);
  }
}

escape_var($referer);

?>

<? decorate("Usuwanie oferty") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie oferty</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("offers/delete.php?id={$offer->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Usuwanie oferty</legend>
        <p>Na pewno chcesz usunąć ofertę &lt;<?= e($offer->title) ?>&gt;?</p>
        <ol class="form-actions">
          <li><input type="submit" value="Usuń ofertę">
          <li><a href="<?= $referer ?>">Anuluj</a>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
