<!DOCTYPE html>
<html lang=pl>
<head>
  <meta charset=utf-8>
  <title>Oferta <?= $offer->number ?></title>
  <style>
    body {
      font-size: .75em;
      font-family: Arial, sans-serif;
      line-height: 1;
      margin: 1em;
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
      padding: .25em;
      text-align: center;
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

    #top .left {
      text-align: left;
    }
    #top .right {
      text-align: right;
    }
    #intro, #outro, #items {
      margin-top: .75em;
      line-height: 1.4;
    }
    #items {
      page-break-after: avoid;
    }
    #items h3 {
      margin-bottom: .25em;
    }
    .item-position {
      text-align: right;
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
      page-break-inside: avoid;
      margin: 5em 5em 15em 0;
      text-align: right;
    }
    #address, #contact {
      float: left;
    }
    #address p, #contact p {
      margin: 0;
    }
    #contact {
      margin-left: 2em;
    }
    .property {
      clear: both;
    }
    .name {
      float: left;
      width: 7em;
    }
    #address .name {
      width: 4.5em;
    }
    .value {
      float: left;
    }
    .clearfix {
      zoom: 1;
    }
    .clearfix:after {
      content: ".";
      display: block;
      height: 0;
      clear: both;
      visibility: hidden;
    }
  </style>
</head>
<body>
  <div id="top" class="clearfix">
    <div class="left">
      <h1>OFERTA</h1>
      <h2><?= $offer->number ?></h2>
    </div>
    <div class="right">
      <h1>WALKNER</h1>
      <h2>Kętrzyn, <?= $offer->closedAt ?></h2>
    </div>
  </div>
  <fieldset id="supplier">
    <legend>Dostawca</legend>
    <p class="left">
      <?= $offer->supplier ?>
    </p>
    <div class="right">
      <h4>Kontakt:</h4>
      <p><?= $offer->supplierContact ?></p>
    </div>
  </fieldset>
  <fieldset id="client">
    <legend>Klient</legend>
    <p class="left">
      <?= $offer->client ?>
    </p>
    <div class="right">
      <h4>Kontakt:</h4>
      <p><?= $offer->clientContact ?></p>
    </div>
  </fieldset>
  <div id="intro">
    <h3>Uzgodnienia wstępne</h3>
    <?= markdown($offer->intro) ?>
  </div>
  <div id="items">
    <h3>Specyfikacja</h3>
    <table>
      <thead>
        <tr>
          <th>Lp.</th>
          <th>Opis</th>
          <th>Ilość</th>
          <th>Cena netto</th>
          <th>Za</th>
          <th>% VAT</th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <td colspan="6">
            <p>
              <? foreach ($offer->summary as $currency => $money): ?>
              <?= $money ?> <?= $currency ?><br>
              <? endforeach ?>
            </p>
            <p>W sumie (netto):</p>
          </td>
        </tr>
      </tfoot>
      <tbody>
        <? foreach ($offer->items as $item): ?>
        <tr>
          <td class="item-position"><?= $item->position ?>.</td>
          <td class="item-description"><?= $item->description ?></td>
          <td><?= $item->quantity ?> <?= $item->unit ?></td>
          <td><?= $item->price ?> <?= $item->currency ?></td>
          <td><?= $item->per ?></td>
          <td><?= $item->vat ?></td>
        </tr>
        <? endforeach ?>
      </tbody>
    </table>
  </div>
  <div id="outro">
    <h3>Uzgodnienia końcowe</h3>
    <?= markdown($offer->outro) ?>
  </div>
  <div id="stamp">
    <img src="<?= url_for("offers/print/stamp.png") ?>" alt="STAMP">
  </div>
  <hr>
  <div id="address" class="clearfix">
    <p>
      Walkner elektronika przemysłowa Zbigniew Walukiewicz<br>
      Nowa Wieś Kętrzyńska 7, 11-400 Kętrzyn, POLSKA
    </p>
    <div class="property">
      <p class="name">NIP:</p>
      <p class="value">742-100-54-87</p>
    </div>
    <div class="property">
      <p class="name">REGON:</p>
      <p class="value">510329685</p>
    </div>
  </div>
  <div id="contact" class="clearfix">
    <div class="property">
      <p class="name">Telefon stac.:</p>
      <p class="value">(89) 752 27 78</p>
    </div>
    <div class="property">
      <p class="name">Telefon kom.:</p>
      <p class="value">603 930 725</p>
    </div>
    <div class="property">
      <p class="name">Adres e-mail:</p>
      <p class="value">walkner@walkner.pl</p>
    </div>
    <div class="property">
      <p class="name">Strona WWW:</p>
      <p class="value">http://walkner.pl/</p>
    </div>
  </div>
</body>
</html>