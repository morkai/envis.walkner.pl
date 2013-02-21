<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['issue']));

no_access_if_not_allowed('service/declare');

$query = <<<SQL
SELECT
  i.*,
  p.type AS productType,
  p.nr AS productNumber
FROM issues i
LEFT JOIN catalog_products p ON p.id=i.relatedProduct
WHERE i.id=?
LIMIT 1
SQL;

$issue = fetch_one($query, array(1 => $_GET['issue']));

not_found_if(empty($issue));

$currentUser = $_SESSION['user'];

$referer = get_referer("service/view.php?id={$issue->id}");
$errors = array();

$invoiceDate = array_reverse(explode('-', $issue->orderInvoiceDate));

if (count($invoiceDate) !== 3)
{
  $invoiceDate = array('<DATE>');
}

$invoiceParts = explode(' ', preg_replace('/[^0-9\/ ]/', ' ', trim($issue->orderInvoice)));
$invoiceNumber = '';
$invoicePos = '';

foreach ($invoiceParts as $part)
{
  if ($part === '' || ctype_space($part))
  {
    continue;
  }

  if ($invoiceNumber === '')
  {
    $part = explode('/', $part);
    $invoiceNumber = $part[0];

    continue;
  }

  if ($invoicePos === '')
  {
    $invoicePos = $part;

    break;
  }
}

if ($invoiceNumber === '')
{
  $invoiceNumber = '<NUM>';
}

if ($invoicePos === '')
{
  $invoicePos = '<POS>';
}

$issue->number = 'DZ' . implode('', $invoiceDate) . '/' . $invoiceNumber . '.' . $invoicePos;

$issue->serial = date('my', $issue->createdAt) . '.001';

preg_match('/Ilość\s+:\s+([0-9]+)/s', $issue->description, $matches);

if (!empty($matches[1]) && $matches[1] > 1)
{
  $issue->serial .= '-' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);
}

$issue->productionYear = date('Y', $issue->createdAt);

$templates = fetch_all('SELECT id, name, pattern FROM declaration_templates ORDER BY name');
$templateOptions = array();
$selectedTemplate = '';

foreach ($templates as $template)
{
  $templateOptions[$template->id] = $template->name;

  if (!empty($template->pattern) && strpos($issue->subject, $template->pattern) !== false)
  {
    $selectedTemplate = $template->id;
  }
}

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

<? begin_slot('submenu') ?>
<ul id="submenu">
  <li><a href="<?= url_for('service/declarations') ?>">Zarządzaj szablonami</a>
</ul>
<? append_slot() ?>

<? decorate("Deklarowanie zgodności") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Deklarowanie zgodności</h1>
  </div>
  <div class="block-body">
    <form id="issue" class="form" method="post" action="<?= url_for("service/declarations/declaration.php?issue={$issue->id}") ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Deklarowanie zgodności</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label('declarationSubject', 'Temat deklaracji') ?>
            <input id="declarationSubject" name="declaration[subject]" type="text" maxlength="200" autofocus value="<?= e($issue->subject) ?>">
          <li>
            <?= label('declarationNumber', 'Numer deklaracji') ?>
            <input id="declarationNumber" name="declaration[number]" type="text" maxlength="30" value="<?= e($issue->number) ?>">
          <li>
            <?= label('declarationOrderNumber', 'Numer zamówienia') ?>
            <input id="declarationOrderNumber" name="declaration[orderNumber]" type="text" maxlength="30" value="<?= e($issue->orderNumber) ?>">
          <li>
            <?= label('declarationProductNumber', 'Numer produktu') ?>
            <input id="declarationProductNumber" name="declaration[productNumber]" type="text" maxlength="30" value="<?= e($issue->productNumber) ?>">
          <li>
            <?= label('declarationSerial', 'Numer fabryczny') ?>
            <input id="declarationSerial" name="declaration[serial]" type="text" maxlength="30" value="<?= e($issue->serial) ?>">
          <li>
            <?= label('declarationProductType', 'Typ produktu') ?>
            <input id="declarationProductType" name="declaration[productType]" type="text" maxlength="100" value="<?= e($issue->productType) ?>">
          <li>
            <?= label('declarationYear', 'Rok produkcji') ?>
            <input id="declarationYear" name="declaration[year]" type="text" maxlength="4" value="<?= e($issue->productionYear) ?>">
          <li>
            <?= label('declarationDate', 'Data faktury') ?>
            <input id="declarationDate" name="declaration[date]" type="text" maxlength="10" value="<?= e($issue->orderInvoiceDate) ?>">
          <li class="form-choice">
            <?= render_choice('Szablon', 'declarationTemplate', 'declaration[template]', $templateOptions, $selectedTemplate) ?>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Generuj deklarację">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
