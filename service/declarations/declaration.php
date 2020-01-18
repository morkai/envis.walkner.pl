<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['issue']) || empty($_POST['declaration']));

no_access_if_not_allowed('service/declare');

$declaration = $_POST['declaration'];

if (empty($declaration['template']))
{
  set_flash('Proszę wybrać szablon deklaracji zgodności.', 'error');
  go_to(empty($_SERVER['HTTP_REFERER']) ? get_referer() : $_SERVER['HTTP_REFERER']);
}

$query = <<<SQL
SELECT i.*
FROM issues i
WHERE i.id=?
LIMIT 1
SQL;

$issue = fetch_one($query, array(1 => $_GET['issue']));

not_found_if(empty($issue));

$currentUser = $_SESSION['user'];

$template = fetch_one('SELECT code FROM declaration_templates WHERE id=? LIMIT 1', array(1 => $declaration['template']));

not_found_if(empty($template));

?>

<? begin_slot('head') ?>
<style>

</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{

});
</script>
<? append_slot() ?>

<!DOCTYPE html>
<html>
<head>
  <meta charset=utf-8>
  <title>Deklaracja zgodności</title>
  <style>
    body
    {
      font: .8em/1.4 Arial, sans-serif;
      margin: 0;
    }
    h1
    {
      margin: 0 0 20px 0;
      border-bottom: 2px solid #000;
    }
    #ce
    {
      float: right;
    }
    .property
    {
      margin-bottom: 10px;
    }
    .name
    {
      vertical-align: top;
      margin: 0;
      padding: 0;
      display: inline-block;
      width: 25%;
    }
    .value
    {
      vertical-align: top;
      margin: 0;
      padding: 0;
      display: inline-block;
      width: 70%;
    }
    .blank
    {
      display: inline-block;
      text-align: center;
      margin: 0 1em;
    }
    .blank-value
    {
      display: block;
      padding: 0 1em;
    }
    .blank-description
    {
      display: block;
      padding: .2em 2em;
      border-top: 1px dotted #000;
      font-size: .8em;
    }
    #blanks
    {
      text-align: center;
      margin-top: 2em;
    }
  </style>
</head>
<body>
<h1><?= e($declaration['header']) ?> <img id=ce src="<?= url_for_media('img/CE.png', true) ?>" alt="CE" height=40></h1>
<div class=property>
  <p class=name>Numer deklaracji:</p>
  <p class=value><?= e($declaration['number']) ?></p>
</div>
<? if (!empty($declaration['orderNumber'])): ?>
<div class=property>
  <p class=name>Numer zamówienia:</p>
  <p class=value><?= e($declaration['orderNumber']) ?></p>
</div>
<? endif ?>
<? if (!empty($declaration['productNumber'])): ?>
  <div class=property>
    <p class=name>Numer produktu:</p>
    <p class=value><?= e($declaration['productNumber']) ?></p>
  </div>
<? endif ?>
<div class=property>
  <p class=name>Wytwórca:</p>
  <p class=value>Walkner elektronika przemysłowa<br>Zbigniew Walukiewicz</p>
</div>
<div class=property>
  <p class=name>Adres:</p>
  <p class=value>Nowa Wieś Kętrzyńska 7<br>11-400 Nowa Wieś Kętrzyńska<br>Polska</p>
</div>
<div class=property>
  <p class=name>Deklaruje, że:</p>
  <p class=value>
    <strong><?= e($declaration['subject']) ?></strong>
    <br>
    Numer fabryczny: <?= e($declaration['serial']) ?><br>
    <? if (!empty($declaration['productType'])): ?>
    Typ produktu: <?= e($declaration['productType']) ?><br>
    <? endif ?>
    Rok produkcji: <?= e($declaration['year']) ?>
  </p>
</div>
<div id=content>
  <?= $template->code ?>
</div>
<div id=blanks>
  <p id=date class=blank>
    <span class="blank-value">Nowa Wieś Kętrzyńska, <?= e($declaration['date']) ?></span>
    <span class="blank-description">(miejsce i data wydania)</span>
  </p>
  <p id=name class=blank>
    <span class="blank-value">Walukiewicz Zbigniew</span>
    <span class="blank-description">(nazwisko, stanowisko)</span>
  </p>
  <p id=signature class=blank>
    <span class="blank-value"><img src="<?= url_for_media('img/signature.png', true) ?>" alt="Pieczątka Walkner" height=75></span>
    <span class="blank-description">(podpis)</span>
  </p>
</div>
</body>
</html>
