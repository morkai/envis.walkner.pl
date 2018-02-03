<?php

include_once './_common.php';

bad_request_if(empty($_GET['id']));

$offer = fetch_one('SELECT id, number, title, clientContact, closedAt FROM offers WHERE id=? LIMIT 1', array(1 => $_GET['id']));

not_found_if(empty($offer));

$offerPdfFile = make_offer_file($offer->id, 'pdf') . '.pdf';
$lang = !empty($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'pl';

$cmd = sprintf('wkhtmltopdf -B 25mm -R 10mm -L 10mm -T 32mm --header-spacing 7 --header-html "%s" --footer-html "%s" "%s" "%s"',
  url_for("/offers/print/header.php?id={$offer->id}&lang={$lang}", true),
  url_for("/offers/print/footer.php?id={$offer->id}&lang={$lang}", true),
  url_for("/offers/print/body.php?id={$offer->id}&lang={$lang}", true),
  $offerPdfFile);

$errors[] = $cmd;

exec($cmd, $output, $result);

header('Content-Type: application/pdf');
readfile($offerPdfFile);
unlink($offerPdfFile);
