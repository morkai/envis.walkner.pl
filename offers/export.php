<?php

include_once __DIR__ . '/_common.php';

if (empty($_GET['id']) || !is_numeric($_GET['id'])) bad_request();

$allowedFormats = array(
  'html' => 'text/html',
  'pdf'  => 'application/pdf',
);
$format = isset($_GET['format']) && isset($allowedFormats[$_GET['format']]) ? $_GET['format'] : 'html';

$offerFile = make_offer_file($_GET['id'], $format);

if (file_exists($offerFile))
{
  header(sprintf('Content-Type: %s', $allowedFormats[$format]));
  header(sprintf('Content-Length: %s', filesize($offerFile)));
  readfile($offerFile);
  exit;
}

$offer = fetch_and_prepare_offer_for_printing($_GET['id']);

if (empty($offer)) not_found();

include './print/full.php';