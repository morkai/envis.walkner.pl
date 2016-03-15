<?php

summarize_offer($offer);

$relatedIssues = array();

if (!empty($offer->issue))
{
  $relatedIssues[] = $offer->issue;
}

foreach ($offer->items as $item)
{
  if (!empty($item->issue))
  {
    $relatedIssues[] = $item->issue;
  }
}

if (!empty($relatedIssues))
{
  $offer->relatedIssues = fetch_all("SELECT i.id, i.subject, i.status, i.percent, i.orderNumber, i.orderInvoice FROM issues i WHERE i.id IN(" . implode(',', $relatedIssues) . ") ORDER BY id");
}

?>

<? begin_slot('head') ?>
<style>
  #items th
  {
    padding-left: .5em;
    padding-right: .5em;
    text-align: center;
  }
  #items td
  {
    text-align: center;
  }
  #items .l
  {
    text-align: left;
  }
  #items p
  {
    float: right;
    margin-left: 1em;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
  $(function()
  {
    $('#relatedIssues').makeClickable();
  });
</script>
<? append_slot() ?>

<? decorate("Oferta {$offer->number}") ?>

<div class="yui-gd">
  <div class="yui-u first">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name"><?= e($offer->title) ?></h1>
      </div>
      <div class="block-body">
        <dl>
          <dt>Numer dokumentu</dt>
          <dd><?= e($offer->number) ?></dd>
          <dt>Data stworzenia</dt>
          <dd><?= $offer->createdAt ?></dd>
          <dt>Data zamknięcia</dt>
          <dd><?= $offer->closedAt ?></dd>
          <? if (!empty($offer->sentTo)): ?>
          <dt>Adresat oferty</dt>
          <dd><?= e($offer->sentTo) ?></dd>
          <? endif ?>
        </dl>
      </div>
    </div>
  </div>
  <div class="yui-u">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Zamówienie</h1>
      </div>
      <div class="block-body">
        <? if ($offer->cancelled): ?>
        <p>Oferta anulowana. <a href="<?= url_for("offers/cancel.php?id={$offer->id}") ?>">Przywróć ofertę</a>.</p>
        <? else: ?>
        <? if (empty($offer->relatedIssues)): ?>
        <p>Zamówienie nie zostało jeszcze stworzone. <a href="<?= url_for("offers/order.php?offer={$offer->id}") ?>">Stwórz zgłoszenie związane z tą ofertą</a>...</p>
        <p>...lub <a href="<?= url_for("offers/cancel.php?id={$offer->id}") ?>">oznacz ofertę jako nieaktualną</a>!</p>
        <? else: ?>
        <table id=relatedIssues>
          <thead>
            <tr>
              <th>ID
              <th>Temat
              <th>Numer zamówienia
              <th>Numer faktury
              <th>Status
              <th>% wykonania
          <tbody>
            <? foreach ($offer->relatedIssues as $relatedIssue): ?>
            <tr>
              <td><?= $relatedIssue->id ?>
              <td class="clickable"><a href="<?= url_for("service/view.php?id={$relatedIssue->id}") ?>"><?= $relatedIssue->subject ?></a>
              <td><?= dash_if_empty($relatedIssue->orderNumber) ?>
              <td><?= dash_if_empty($relatedIssue->orderInvoice) ?>
              <td><?= $statuses[$relatedIssue->status] ?>
              <td><?= dash_if_empty($relatedIssue->percent) ?>
            <? endforeach ?>
        </table>
        <? endif ?>
        <? endif ?>
      </div>
    </div>
  </div>
</div>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Klient</h1>
  </div>
  <div class="block-body">
    <div class="yui-g">
      <div class="yui-u first">
        <p><?= nl2br(e($offer->client)) ?></p>
      </div>
      <div class="yui-u">
        <h2>Kontakt:</h2>
        <p><?= nl2br(e($offer->clientContact)) ?></p>
      </div>
    </div>
  </div>
</div>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Specyfikacja</h1>
  </div>
  <div class="block-body">
    <table id="items">
      <thead>
        <tr>
          <th>Lp.
          <th>Opis
          <th>Ilość
          <th>Jednostka
          <th>Waluta
          <th>Cena
          <th>Za
          <th>VAT
      <tfoot>
        <tr>
          <td colspan="8">
            <p>
              <? foreach ($offer->summary as $currency => $money): ?>
              <?= $money ?> <?= $currency ?><br>
              <? endforeach ?>
            </p>
            <p>W sumie (netto):</p>
      <tbody>
        <? foreach ($offer->items as $item): ?>
        <tr>
          <td class="l"><?= $item->position ?>.
          <td class="l""><?= nl2br($item->description) ?>
          <td><?= (float)$item->quantity ?>
          <td><?= e($item->unit) ?>
          <td><?= $item->currency ?>
          <td><?= $item->price ?>
          <td><?= $item->per ?>
          <td><?= $item->vat ?>
        <? endforeach ?>
    </table>
  </div>
</div>

<div class="yui-g">
  <div class="yui-u first">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Uzgodnienia wstępne</h1>
      </div>
      <div class="block-body">
        <?= markdown($offer->intro) ?>
      </div>
    </div>
  </div>
  <div class="yui-u">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Uzgodnienia końcowe</h1>
      </div>
      <div class="block-body">
        <?= markdown($offer->outro) ?>
      </div>
    </div>
  </div>
</div>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Dostawca</h1>
  </div>
  <div class="block-body">
    <div class="yui-g">
      <div class="yui-u first">
        <p><?= nl2br(e($offer->supplier)) ?></p>
      </div>
      <div class="yui-u">
        <h2>Kontakt:</h2>
        <p><?= nl2br(e($offer->supplierContact)) ?></p>
      </div>
    </div>
  </div>
</div>
