<?php

$bypassAuth = true;

include_once __DIR__ . '/../_common.php';

$offer = fetch_and_prepare_offer_for_printing(isset($_GET['id']) ? $_GET['id'] : 0);

if (empty($offer))
{
  $offer = new stdClass;

  $offer->supplier = '-';
  $offer->supplierContact = '-';
  $offer->client = '-';
  $offer->clientContact = '-';
  $offer->intro = '-';
  $offer->outro = '-';
  $offer->items = array();
  $offer->summary = array();
}

$en = !empty($_GET['lang']) && $_GET['lang'] === 'en';

?><!DOCTYPE html>
<html lang=pl>
<head>
  <meta charset=utf-8>
  <title><?= $en ? 'Offer' : 'Oferta' ?> <?= $offer->number ?></title>
  <style>
    body {
      font-size: .75em;
      font-family: Arial, sans-serif;
      line-height: 1;
      margin: 0.5em 0 0 0;
      text-rendering: optimizeLegibility;
    }
    h1, h2, h3 {
      font-weight: normal;
      margin: 0;
      padding: 0;
      text-shadow: 0 0 1px #FFF;
    }
    h1 {
      font-size: 3em;
      margin-bottom: .25em;
    }
    h2 {
      font-size: 1.75em;
    }
    h3 {
      font-size: 1.5em;
    }
    h4 {
      font-weight: bold;
      font-size: 1em;
      line-height: 1.4;
      margin: 0;
    }
    fieldset {
      border: 1px solid #000;
      margin-top: 1em;
      padding: .25em .5em;
    }
    legend {
      font-size: 1.5em;
      background: #fff;
    }
    p {
      margin: .5em 0;
      line-height: 1.4;
    }
    ol, ul {
      margin: 0;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th {
      white-space: nowrap;
    }
    th, tbody td {
      border: 1px solid #000;
      padding: .25em .5em;
      text-align: left;
      width: 1px;
      white-space: nowrap;
    }
    tfoot td {
      padding: .25em;
      vertical-align: top;
    }
    hr {
      border-top: 1px solid #000;
      margin: 1em 0;
    }
    .left {
      width: 49%;
      float: left;
    }
    .right {
      width: 49%;
      float: right;
    }
    #supplier {
      margin-top: -1em;
    }
    #intro, #outro, #items {
      margin-top: .75em;
      line-height: 1.4;
    }
    #items {
      page-break-inside: avoid;
    }
    #items h3 {
      margin-bottom: .25em;
    }
    .item-description {
      text-align: left;
    }
    #items tfoot td {
      text-align: right;
    }
    #items p {
      float: right;
      margin-left: 1em;
    }
    #stamp {
      page-break-before: auto;
      page-break-after: avoid;
      page-break-inside: avoid;
      margin: 2em 5em 0 0;
      text-align: right;
    }
    .is-min {
      width: 1%;
      white-space: nowrap;
      text-align: right;
    }
  </style>
</head>
<body>
  <fieldset id="supplier">
    <legend><?= $en ? 'Supplier' : 'Dostawca' ?></legend>
    <p class="left">
      <?= $offer->supplier ?>
    </p>
    <div class="right">
      <h4><?= $en ? 'Contact' : 'Kontakt' ?>:</h4>
      <p><?= $offer->supplierContact ?></p>
    </div>
  </fieldset>
  <fieldset id="client">
    <legend><?= $en ? 'Client' : 'Klient' ?></legend>
    <p class="left">
      <?= $offer->client ?>
    </p>
    <div class="right">
      <h4><?= $en ? 'Contact' : 'Kontakt' ?>:</h4>
      <p><?= $offer->clientContact ?></p>
    </div>
  </fieldset>
  <div id="intro">
    <h3><?= $en ? 'Preliminary arrangements' : 'Uzgodnienia wstępne' ?></h3>
    <?= markdown($offer->intro) ?>
  </div>
  <div id="items">
    <h3><?= $en ? 'Specification' : 'Specyfikacja' ?></h3>
    <table>
      <thead>
        <tr>
          <th><?= $en ? 'No' : 'Lp.' ?></th>
          <th class="item-description"><?= $en ? 'Description' : 'Opis' ?></th>
          <th><?= $en ? 'Quantity' : 'Ilość' ?></th>
          <th>% VAT</th>
          <th><?= $en ? 'Price<br>netto' : 'Cena<br>netto' ?></th>
          <th><?= $en ? 'Value<br>netto' : 'Wartość<br>netto' ?></th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <td colspan="6">
            <p>
              <? foreach ($offer->summary as $summary): ?>
              <?= $summary['money'] ?><br>
              <? endforeach ?>
            </p>
            <p><?= $en ? 'Total' : 'W sumie' ?> (netto):</p>
          </td>
        </tr>
      </tfoot>
      <tbody>
        <? foreach ($offer->items as $item): ?>
        <tr>
          <td class="is-min"><?= $item->position ?>.</td>
          <td class="item-description"><?= $item->description ?></td>
          <td class="is-min"><?= $item->quantityFmt ?> <?= $item->unit ?></td>
          <td class="is-min"><?= $item->vat ?></td>
          <td class="is-min"><?= $item->priceFmt ?> /<?= $item->perFmt ?>&nbsp;<?= $item->unit ?></td>
          <td class="is-min"><?= $item->valueFmt ?></td>
        </tr>
        <? endforeach ?>
      </tbody>
    </table>
  </div>
  <div id="outro">
    <h3><?= $en ? 'Final arrangements' : 'Uzgodnienia końcowe' ?></h3>
    <?= markdown($offer->outro) ?>
  </div>
  <div id="stamp">
    <img width="225" height="105" src="data:image/png;base64,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" alt="STAMP">
  </div>
</body>
</html>
