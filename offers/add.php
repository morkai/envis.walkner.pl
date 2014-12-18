<?php

include_once './_common.php';

no_access_if_not_allowed('offers/add');

$errors = array();
$referer = get_referer('offers/');
$offer = array_merge(
  array(
    'number' => '',
    'title' => '',
    'client' => '',
    'clientContact' => '',
    'supplier' => $defaultOfferSupplier,
    'supplierContact' => $defaultOfferSupplierContact,
    'intro' => '',
    'outro' => '',
    'createdAt' => date('Y-m-d')
  ),
  empty($_POST['offer']) ? array() : $_POST['offer']
);

if (is('post'))
{
  if (is_empty($offer['title']))
  {
    $errors[] = 'Tytuł oferty jest wymagany.';
  }

  if (!empty($errors))
    goto VIEW;

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    if (is_empty($offer['number']))
    {
      $offer['number'] = fetch_next_offer_number();
    }

    $offer['updatedAt'] = time();

    exec_insert('offers', $offer);

    $offer['id'] = $conn->lastInsertId();

    $user = $_SESSION['user'];

    log_info("Dodano ofertę <{$offer['title']}>.");

    $conn->commit();

    set_flash("Nowa oferta została dodana pomyślnie.");

    go_to("offers/view.php?id=${offer['id']}");
  }
  catch (PDOException $x)
  {
    $conn->rollBack();

    if ($x->getCode() == 23000)
    {
      $errors[] = 'Numer dokumentu musi być unikalny.';
      $errors[] = $x->getMessage();
    }
    else
      $errors[] = $x->getMessage();
  }
}

VIEW:

escape_array($offer);

?>

<? begin_slot('head') ?>
<style>
  #addOfferNumber
  {
    width: 10em;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>

</script>
<? append_slot() ?>

<? decorate("Dodawanie nowej oferty") ?>

<div class="block">
  <ul class="block-header">
    <h1 class="block-name">Nowa oferta</h1>
  </ul>
  <div class="block-body">
    <form method="post" action="<?= url_for('offers/add.php') ?>">
      <input name="referer" type="hidden" value="<?= $referer ?>">
      <? display_errors($errors) ?>
      <ol class="form-fields">
        <li>
          <?= label('addOfferTitle', 'Tytuł*') ?>
          <input id="addOfferTitle" name="offer[title]" type="text" value="<?= $offer['title'] ?>">
        <li>
          <?= label('addOfferNumber', 'Numer dokumentu') ?>
          <input id="addOfferNumber" name="offer[number]" type="text" value="<?= $offer['number'] ?>">
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Dodaj ofertę">
            <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
          </ol>
      </ol>
    </form>
  </div>
</div>
