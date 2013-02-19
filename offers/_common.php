<?php

include_once __DIR__ . '/../service/_common.php';

$defaultOfferSupplier = <<<TXT
Walkner elektronika przemysłowa Zbigniew Walukiewicz
Nowa Wieś Kętrzyńska 7
11-400 Kętrzyn
NIP: 742-100-54-87
REGON: 510329685
TXT;

$defaultOfferSupplierContact = <<<TXT
Zbigniew Walukiewicz
Tel.: 0 603 930 725
E-mail: walkner@walkner.pl
TXT;

function fetch_next_offer_number()
{
  $number = 'SEK' . date('dmY') . '/';
  $lastOffer = fetch_one('SELECT number FROM offers WHERE createdAt=?', array(1 => date('Y-m-d')));

  if (empty($lastOffer))
  {
    return $number . '1';
  }

  $parts = explode('/', $lastOffer->number);

  return $number . ((int)$parts[1] + 1);
}

function make_offer_file($id, $format)
{
  return ENVIS_UPLOADS_PATH . '/offers/' . $id . '.' . $format;
}

function summarize_offer($offer)
{
  $offer->summary = array();

  foreach ($offer->items as $item)
  {
    if (!isset($offer->summary[$item->currency]))
    {
      $offer->summary[$item->currency] = 0;
    }

    $offer->summary[$item->currency] += $item->quantity * $item->price / $item->per;
  }

  $offer->summary = array_map(function($money)
  {
    $parts = explode('.', (string)$money);

    if (empty($parts[1]))
    {
      $parts[1] = '';
    }

    return $parts[0] . '.' . $parts[1] . str_repeat('0', 2 - strlen($parts[1]));
  }, $offer->summary);
}

function fetch_offer_templates()
{
  $allTemplates = fetch_all('SELECT * FROM offer_templates');
  $templates = array(
    'client' => array(new_object(array('id' => 0, 'name' => '', 'template' => e(json_encode(array('clientName' => '', 'clientContact' => '')))))),
    'intro' => array(new_object(array('id' => 0, 'name' => '', 'template' => e(json_encode(array('intro' => '')))))),
    'outro' => array(new_object(array('id' => 0, 'name' => '', 'template' => e(json_encode(array('outro' => ''))))))
  );

  foreach ($allTemplates as $template)
  {
    $template->template = e(json_encode(unserialize($template->template)));

    $templates[$template->type][$template->id] = $template;
  }

  return $templates;
}

function render_offer_templates($templates, $type)
{
  if (count($templates[$type]) > 1): ?>
  <select id="templates<?= ucfirst($type) ?>" class="templates" name="template[id]" data-type="<?= $type ?>">
    <? foreach ($templates[$type] as $template): ?>
    <option value="<?= $template->id ?>" data-template="<?= $template->template ?>"><?= e($template->name) ?></option>
    <? endforeach ?>
  </select>
<? endif;
}

function fetch_and_prepare_offer_for_printing($id)
{
  $offer = fetch_one('SELECT * FROM offers WHERE id=? LIMIT 1', array(1 => $id));

  if (empty($offer)) return null;

  $offer->items = fetch_all('SELECT position, description, quantity, unit, price, currency, per, vat FROM offer_items WHERE offer=?', array(1 => $offer->id));

  if (empty($offer->closedAt))
  {
    $offer->closedAt = '-';
  }

  foreach (array('intro', 'outro') as $field)
  {
    $offer->$field = empty($offer->$field) ? '-' : $offer->$field;
  }

  foreach (array('supplier', 'supplierContact', 'client', 'clientContact') as $field)
  {
    $offer->$field = nl2br(e($offer->$field));
  }

  foreach ($offer->items as $item)
  {
    $item->description = nl2br(e($item->description));
    $item->quantity = (string)(float)$item->quantity;
    $item->unit = e($item->unit);
  }

  summarize_offer($offer);

  return $offer;
}
