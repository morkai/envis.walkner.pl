<?php

$DEFAULT_RELATED_FACTORY = 2;

include_once './_common.php';

function nl2brmd($string)
{
  return str_replace(array("\r", "\n"), array('', "  \n"), $string);
}

no_access_if_not_allowed('offers*');
bad_request_if(empty($_GET['offer']));

$query = <<<SQL
SELECT o.*
FROM offers o
WHERE o.id=?
LIMIT 1
SQL;

$offer = fetch_one($query, array(1 => $_GET['offer']));

not_found_if(empty($offer));
bad_request_if(!empty($offer->issue));

no_access_if_not(is_allowed_to('offers/close') && !empty($offer->closedAt));

$offer->items = fetch_all('SELECT * FROM offer_items WHERE offer=? ORDER BY position ASC', array(1 => $offer->id));

$referer = get_referer("offers/view.php?id={$offer->id}");
$createMain = true;

if (!empty($_POST['order']))
{
  $idToItemMap = array();

  foreach ($offer->items as $item)
  {
    $idToItemMap[$item->id] = $item;
  }

  $order = $_POST['order'];

  $offer->title = str_replace(array("\r", "\n"), array('', ' '), $offer->title);
  $offer->url = url_for("offers/view.php?id={$offer->id}");

  if (is_empty($order['title']))
  {
    $order['title'] = $offer->title;
  }

  $description = <<<MARKDOWN
*Zgłoszenie stworzone jako zamówienie z oferty: [{$offer->title}]({$offer->url})*
MARKDOWN;

  foreach ($order['descriptionOptions'] as $option) switch ($option)
  {
    case 'client':
      $client = nl2brmd($offer->client);
      $contact = nl2brmd($offer->clientContact);

      $description .= "\n\n## Klient\n{$client}\n\n### Kontakt\n{$contact}";
      break;

    case 'supplier':
      $supplier = nl2brmd($offer->supplier);
      $contact = nl2brmd($offer->supplierContact);

      $description .= "\n\n## Dostawca\n{$client}\n\n### Kontakt\n{$contact}";
      break;

    case 'intro':
      $description .= "\n\n## Uzgodnienia wstępne\n{$offer->intro}";
      break;

    case 'outro':
      $description .= "\n\n## Uzgodnienia końcowe\n{$offer->outro}";
      break;
  }

  $currentUser = $_SESSION['user'];

  $mainIssue = array(
    'status' => 1,
    'creator' => $currentUser->getId(),
    'createdAt' => time(),
    'updatedAt' => time(),
    'owner' => $currentUser->getId(),
    'subject' => $order['title'],
    'description' => $description,
    'relatedFactory' => $DEFAULT_RELATED_FACTORY,
    'priority' => 2,
    'kind' => 3,
    'type' => 4,
    'orderNumber' => $order['number'],
    'orderDate' => date('Y-m-d'),
    'quantity' => 1,
    'unit' => 'szt.',
    'price' => 0,
    'per' => 1,
    'vat' => 23
  );

  $itemIssues = array();

  foreach ($order['items'] as $item)
  {
    if (empty($item['id']))
    {
      continue;
    }

    $name = empty($idToItemMap[$item['id']]) ? $item['description'] : $idToItemMap[$item['id']]->description;

    $description = <<<MARKDOWN
*Zgłoszenie stworzone jako część zamówienia z oferty: [{$offer->title}]({$offer->url})*

Opis
: {$name}

Ilość
: {$item['quantity']}

Jednostka
: {$item['unit']}
MARKDOWN;

    $itemIssues[$item['id']] = array(
      'status' => 1,
      'creator' => $currentUser->getId(),
      'createdAt' => time(),
      'updatedAt' => time(),
      'owner' => $currentUser->getId(),
      'subject' => $item['description'],
      'description' => $description,
      'relatedFactory' => $DEFAULT_RELATED_FACTORY,
      'priority' => 2,
      'kind' => 3,
      'type' => 4,
      'orderNumber' => $order['number'],
      'orderDate' => date('Y-m-d'),
      'quantity' => $item['quantity'],
      'unit' => $item['unit'],
      'currency' => $item['currency'],
      'price' => $item['price'],
      'per' => $item['per'],
      'vat' => $item['vat']
    );

    $mainIssue['currency'] = $item['currency'];
    $mainIssue['price'] += (float)$item['price'];
  }

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    $bindings = array('updatedAt' => time());

    if ($createMain)
    {
      exec_insert('issues', $mainIssue);

      $mainIssue['id'] = $conn->lastInsertId();
      $bindings['issue'] = $mainIssue['id'];
    }

    exec_update('offers', $bindings, "id={$offer->id}");

    foreach ($itemIssues as $id => $itemIssue)
    {
      exec_insert('issues', $itemIssue);

      $itemIssues[$id]['id'] = $conn->lastInsertId();

      exec_update('offer_items', array('issue' => $itemIssues[$id]['id']), "id={$id}");
    }

    if ($createMain)
    {
      $linkStmt = prepare_stmt('INSERT INTO issue_relations SET issue1=?, issue2=?');

      foreach ($itemIssues as $itemIssue)
      {
        $linkStmt->execute(array($mainIssue['id'], $itemIssue['id']));
        $linkStmt->execute(array($itemIssue['id'], $mainIssue['id']));
      }
    }

    $conn->commit();

    go_to("offers/view.php?id={$offer->id}");
  }
  catch (PDOException $x)
  {
    $conn->rollBack();

    set_flash($x->getMessage(), 'error');
    go_to($referer);
  }
}

$descriptionOptions = array(
  'client' => 'Informacje o kliencie',
  'supplier' => 'Informacje o dostawcy',
  'intro' => 'Uzgodnienia wstępne',
  'outro' => 'Uzgodnienia końcowe'
);
$selectedDescriptionOptions = array('client', 'intro', 'outro');

escape_vars($offer->title);

?>

<? begin_slot('head') ?>
<style>
  .number { text-align: right; }
  #items th { border-bottom: 1px solid #246; }
  #items > tbody > tr:first-child > td { border-top-width: 1px; }
  #items td, #items th { font-size: 1em; padding: .5em .25em }
  .item-position { width: 1%; }
  .item-description textarea { height: 3.5em; }
  .item-quantity { width: 6%; }
  .item-unit { width: 5%; }
  .item-currency { width: 5%; }
  .item-price { width: 10%; }
  .item-per { width: 5%; }
  .item-vat { width: 5%; }
</style>
<? append_slot() ?>

<? decorate('Tworzenie zamówienia z oferty') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Tworzenie zamówienia dla oferty</h1>
  </div>
  <div class="block-body">
    <form class="form" action="<?= url_for("offers/order.php?offer={$offer->id}") ?>" method=post autocomplete=off>
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <input type="hidden" name="createMain" value="1">
      <fieldset>
        <legend>Zamówienie dla oferty</legend>
        <ol class="form-fields">
          <li>
            <?= label('offerTitle', 'Oferta') ?>
            <p id=offerTitle><?= $offer->title ?></p>
          <li>
            <?= label('orderTitle', 'Temat zamówienia głównego*') ?>
            <input id=orderTitle name="order[title]" type="text" value="<?= $offer->title ?>" autofocus>
          <li>
            <?= label('orderNumber', 'Numer zamówienia') ?>
            <input id=orderNumber name="order[number]" type="text" value="">
          <li class="form-choice">
            <?= render_choice('W opisie zawrzyj', 'descriptionOptions', 'order[descriptionOptions][]', $descriptionOptions, $selectedDescriptionOptions, true) ?>
          <li>
            <?= label('offerItems-0', 'Stwórz oddzielne zamówienia dla pozycji') ?>
            <table id=items>
              <thead>
                <tr>
                  <th><input id=toggleItems type=checkbox checked>
                  <th>Temat zamówienia
                  <th>Ilość
                  <th>Jednostka
                  <th>Waluta
                  <th>Cena
                  <th>Za
                  <th>VAT
              <tbody>
                <? foreach ($offer->items as $i => $item): ?>
                <tr>
                  <td class="item-position"><input class=item-toggle name="order[items][<?= $i ?>][id]" type="checkbox" value="<?= $item->id ?>" checked>
                  <td class="item-description"><input name="order[items][<?= $i ?>][description]" type=text value="<?= e($item->description) ?>">
                  <td class="item-quantity" style="width: 5%"><input name="order[items][<?= $i ?>][quantity]" type="text" value="<?= (float)$item->quantity ?>" class="number" maxlength="10">
                  <td class="item-unit" style="width: 5%"><input name="order[items][<?= $i ?>][unit]" type="text" value="<?= e($item->unit) ?>" maxlength="10">
                  <td class="item-currency" style="width: 5%"><input name="order[items][<?= $i ?>][currency]" type="text" value="<?= $item->currency ?>" maxlength="3">
                  <td class="item-price" style="width: 10%"><input name="order[items][<?= $i ?>][price]" type="text" value="<?= $item->price ?>" class="number">
                  <td class="item-per" style="width: 5%"><input name="order[items][<?= $i ?>][per]" type="text" value="<?= $item->per ?>" class="number">
                  <td class="item-vat" style="width: 5%"><input name="order[items][<?= $i ?>][vat]" type="text" value="<?= $item->vat ?>" class="number" maxlength="2">
                <? endforeach ?>
            </table>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Stwórz zamówienie">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#toggleItems').click(function()
  {
    var state = this.checked;

    $('#items input.item-toggle').each(function()
    {
      this.checked = state;
    });
  });
});
</script>
<? append_slot() ?>
