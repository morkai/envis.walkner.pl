<?php

prepare_offer($offer);
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
  $offer->relatedIssues = fetch_all("SELECT i.id, i.subject, i.status, i.percent, i.orderNumber, i.orderInvoice FROM issues i WHERE i.id IN(" . implode(',', $relatedIssues) . ")");

  usort($offer->relatedIssues, function($a, $b) use($offer)
  {
    if ($a->id === $offer->issue)
    {
      return -1;
    }

    if ($b->id === $offer->issue)
    {
      return 1;
    }

    return $a->id - $b->id;
  });
}

?>

<? begin_slot('head') ?>
<style>
  #items th
  {
    text-align: left;
  }
  #items td
  {
    text-align: right;
  }
  #items .l
  {
    text-align: left;
  }
  #items p
  {
    float: right;
    margin-left: 1em;
    margin-bottom: 0;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(() =>
{
  $('#relatedIssues').makeClickable();

  $('#invoiceClipboard').on('click', () =>
  {
    copyToClipboard(clipboardData =>
    {
      const lines = [`Oferta: <?= $offer->number ?>`];

      <? if (!empty($offer->relatedIssues) && !empty($offer->relatedIssues[0]->orderNumber)): ?>
      lines.push(`PO: <?= $offer->relatedIssues[0]->orderNumber ?>`);
      <? endif ?>

      clipboardData.setData('text/plain', lines.join('\n'));
    });

    return false;
  });

  $('#invoiceMailClipboard').on('click', () =>
  {
    copyToClipboard(clipboardData =>
    {
      const lines = [``, ``, `<?= e($offer->title) ?>`, `Oferta: <?= $offer->number ?> (https://walkner.pl/r/offer/<?= $offer->id ?>)`];

      <? if (!empty($offer->relatedIssues) && !empty($offer->relatedIssues[0]->orderNumber)): ?>
      lines.push(`PO: <?= $offer->relatedIssues[0]->orderNumber ?> (https://walkner.pl/r/order/<?= $offer->relatedIssues[0]->id ?>)`);
      <? endif ?>

      clipboardData.setData('text/plain', lines.join('\n'));
    });

    return false;
  });
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
              <th>PO
              <th>FV
              <th>Status
              <th>Temat
          <tbody>
            <? foreach ($offer->relatedIssues as $relatedIssue): ?>
            <tr>
              <td class="min"><?= $relatedIssue->id ?>
              <td class="min"><?= dash_if_empty($relatedIssue->orderNumber) ?>
              <td class="min"><?= dash_if_empty($relatedIssue->orderInvoice) ?>
              <td class="min"><?= $statuses[$relatedIssue->status] ?>
              <td class="clickable"><a href="<?= url_for("service/view.php?id={$relatedIssue->id}") ?>"><?= $relatedIssue->subject ?></a>
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
        <p><?= $offer->client ?></p>
      </div>
      <div class="yui-u">
        <h2>Kontakt:</h2>
        <p><?= $offer->clientContact ?></p>
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
            <p><? foreach ($offer->summary as $summary): ?><?= $summary['money'] ?><?= $summary['newLine'] ? '<br>' : '' ?><? endforeach ?></p>
            <p>W sumie (netto):</p>
      <tbody>
        <? foreach ($offer->items as $item): ?>
        <tr>
          <td class="min"><?= $item->position ?>.
          <td class="l"><?= nl2br($item->description) ?>
          <td class="min"><?= (float)$item->quantity ?>
          <td class="min"><?= e($item->unit) ?>
          <td class="min"><?= $item->currency ?>
          <td class="min"><?= $item->priceFmt ?>
          <td class="min"><?= $item->per ?>
          <td class="min"><?= $item->vat ?>
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
        <p><?= $offer->supplier ?></p>
      </div>
      <div class="yui-u">
        <h2>Kontakt:</h2>
        <p><?= $offer->supplierContact ?></p>
      </div>
    </div>
  </div>
</div>
