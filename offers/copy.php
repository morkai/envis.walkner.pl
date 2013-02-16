<?php

include_once './_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('offers/add');

$offer = fetch_one('SELECT * FROM offers WHERE id=?', array(1 => $_GET['id']));

not_found_if(empty($offer));

$referer = get_referer('offers/view.php?id=' . $offer->id);
$errors = array();
$newOffer = array_merge(array(
  'title' => $offer->title,
), empty($_POST['newOffer']) ? array() : $_POST['newOffer']);

if (is('post'))
{
  if (is_empty($newOffer['title']))
  {
    $errors[] = 'Tytuł nowej oferty jest wymagany.';
  }

  if (!empty($errors)) goto VIEW;

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    $ignoreFields = array('id', 'issue', 'closedAt', 'title', 'sentTo');

    foreach ($offer as $field => $value)
    {
      if (!in_array($field, $ignoreFields))
      {
        $newOffer[$field] = $value;
      }
    }

    $newOffer['number'] = fetch_next_offer_number();
    $newOffer['createdAt'] = date('Y-m-d');

    exec_insert('offers', $newOffer);

    $newOffer['id'] = $conn->lastInsertId();

    $items = fetch_all('SELECT * FROM offer_items WHERE offer=? ORDER BY position', array(1 => $offer->id));

    foreach ($items as $item)
    {
      unset($item->id, $item->issue);

      $item->offer = $newOffer['id'];

      exec_insert('offer_items', $item);
    }

    log_info('Skopiowano ofertę <%s> do <%s>.', $offer->title, $newOffer['title']);

    $conn->commit();

    set_flash(sprintf('Oferta <%s> została skopiowana pomyślnie.', $offer->title));
    go_to("offers/view.php?id={$newOffer['id']}");
  }
  catch (PDOException $x)
  {
    $conn->rollBack();

    $errors[] = $x->getMessage();
  }
}

VIEW:

escape_var($referer);

?>

<? decorate("Kopiowanie oferty") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Kopiowanie oferty</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("offers/copy.php?id={$offer->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Kopiowanie oferty</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label('baseOfferTitle', 'Oferta bazowa') ?>
            <p><?= e($offer->title) ?> (<?= e($offer->number) ?>)</p>
          <li>
            <?= label('offerTitle', 'Tytuł nowej oferty*') ?>
            <input id="offerTitle" name="newOffer[title]" type="text" value="<?= e($newOffer['title']) ?>">
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Kopiuj ofertę">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
