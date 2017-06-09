<?php

include_once __DIR__ . '/../service/_common.php';

$defaultOfferSupplier = <<<TXT
Walkner elektronika przemysłowa Zbigniew Walukiewicz (48004308)
Nowa Wieś Kętrzyńska 7
11-400 Nowa Wieś Kętrzyńska, POLAND
NIP: 7421005487
REGON: 510329685
TXT;

$defaultOfferSupplierContact = <<<TXT
Zbigniew Walukiewicz
Tel.: +48 603 930 725
E-mail: walkner@walkner.pl
TXT;

function fetch_next_offer_number()
{
  $number = 'SEK' . date('dmY') . '/';
  $offers = fetch_all('SELECT number FROM offers WHERE createdAt=?', array(1 => date('Y-m-d')));

  if (empty($offers))
  {
    return $number . '1';
  }

  $lastNumber = 0;

  foreach ($offers as $offer)
  {
    $parts = explode('/', $offer->number);

    if (count($parts) > 1 && is_numeric($parts[1]) && $parts[1] > $lastNumber)
    {
      $lastNumber = (int)$parts[1];
    }
  }

  return $number . ($lastNumber + 1);
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

  $fmt = new NumberFormatter('pl_PL', NumberFormatter::CURRENCY);

  foreach ($offer->summary as $currency => $money)
  {
    $offer->summary[$currency] = $fmt->formatCurrency($money, $currency);
  }
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

  $qtyFmt = new NumberFormatter('pl_PL', NumberFormatter::DECIMAL);
  $curFmt = new NumberFormatter('pl_PL', NumberFormatter::CURRENCY);

  foreach ($offer->items as $item)
  {
    $item->description = nl2br(e($item->description));
    $item->priceFmt = $curFmt->formatCurrency((float)$item->price, $item->currency);
    $item->quantityFmt = $qtyFmt->format((float)$item->quantity);
    $item->perFmt = $qtyFmt->format((float)$item->per);
    $item->valueFmt = $curFmt->formatCurrency((float)$item->price * (float)$item->quantity, $item->currency);
    $item->unit = e($item->unit);
  }

  summarize_offer($offer);

  return $offer;
}
