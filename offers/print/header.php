<?php

$bypassAuth = true;

include_once __DIR__ . '/../_common.php';

$offer = fetch_one('SELECT number, closedAt FROM offers WHERE id=? LIMIT 1', array(1 => isset($_GET['id']) ? $_GET['id'] : 0));

if (empty($offer))
{
  $offer = new stdClass;

  $offer->number = 'SEK00000000/0';
  $offer->closedAt = '-';
}

?>
<!DOCTYPE html>
<html lang=pl>
<head>
  <meta charset=utf-8>
  <title>#hd</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: .75em;
      margin: 1em;
      padding: 0;
    }
    h1 {
      margin: 0;
    }
    h2 {
      margin: .25em 0 0 0;
    }
    h1, h2 {
      text-shadow: 0 0 1px #FFF;
    }
    hr {
      border: 0;
      border-top: 1px solid #000;
      clear: both;
    }
    .right {
      float: right;
      text-align: right;
    }
  </style>
</head>
<body>
  <div class="right">
    <h1>WALKNER</h1>
    <h2>Kętrzyn, <?= date('Y-m-d') ?></h2>
  </div>
  <div>
    <h1>OFERTA</h1>
    <h2><?= $offer->number ?></h2>
  </div>
  <hr>
</body>
</html>
