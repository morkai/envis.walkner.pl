<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

$query = <<<SQL
SELECT
  i.*,
  o.name AS owner,
  f.name AS factoryName,
  m.name AS machineName,
  d.name AS deviceName,
  p.nr AS productNr,
  p.name AS productName
FROM issues i
LEFT JOIN users o ON o.id=i.owner
LEFT JOIN factories f ON f.id=i.relatedFactory
LEFT JOIN machines m ON m.id=i.relatedMachine
LEFT JOIN engines d ON d.id=i.relatedDevice
LEFT JOIN catalog_products p ON p.id=i.relatedProduct
WHERE i.id=?
LIMIT 1
SQL;

$oldIssue = (array)fetch_one($query, array(1 => $_GET['id']));

if (empty($oldIssue)) not_found();

$currentUser = $_SESSION['user'];

if (!$currentUser->isSuper())
{
  if ($oldIssue['owner'] != $currentUser->getId())
  {
    if (!$oldIssue['owner'] && !is_allowed_to('service/edit'))
    {
      no_access();
    }
  }
}

$oldIssue['tasks'] = fetch_array('SELECT id AS `key`, summary AS value FROM issue_tasks WHERE issue=? AND completed=0 ORDER BY createdAt ASC',
                                 array(1 => $oldIssue['id']));

$referer = get_referer("service/view.php?id={$oldIssue['id']}");
$errors = array();

if (!empty($_POST['issue']))
{
  $issue = $_POST['issue'] + array('tasks' => array());

  if (is_empty($issue['subject']))
  {
    $errors[] = 'Temat jest wymagany.';
  }

  if (!empty($issue['owner']))
  {
    $owner = fetch_one('SELECT id, email, name FROM users WHERE name=?', array(1 => $issue['owner']));

    if (empty($owner))
    {
      $errors[] = 'Wybrana osoba nie istnieje w systemie.';
    }
  }
  else
  {
    $issue['owner'] = null;
  }

  if (!empty($issue['relatedProduct']))
  {
    $relatedProduct = fetch_one('SELECT id, nr, name FROM catalog_products WHERE id=? LIMIT 1', array(1 => $issue['relatedProduct']));
  }

  if (empty($relatedProduct))
  {
    $relatedProduct = (object)array('id' => 0, 'nr' => '-', 'name' => '-');
    $issue['relatedProduct'] = null;
  }

  if (!empty($errors)) goto VIEW;

  foreach (array('relatedFactory', 'relatedMachine', 'relatedDevice', 'kind', 'type', 'orderNumber', 'orderDate', 'orderInvoice', 'orderInvoiceDate', 'expectedFinishAt') as $field)
    if (empty($issue[$field])) $issue[$field] = null;

  $comment = $issue['comment'];
  unset($issue['comment']);

  $changes = array();
  $changedRelatedObject = false;
  $relatedObjectFields = array('relatedFactory', 'relatedMachine', 'relatedDevice');

  foreach ($issue as $field => $newValue)
  {
    $oldValue = prepare_issue_value($oldIssue[$field]);
    $newValue = prepare_issue_value($newValue);

    if ($newValue !== $oldValue)
    {
      if ($field === 'tasks') continue;

      if (in_array($field, $relatedObjectFields))
      {
        $changedRelatedObject = true;

        continue;
      }

      if ($field === 'relatedProduct')
      {
        $changes[] = array(
          'field' => $field,
          'old' => empty($oldIssue['relatedProduct']) ? '-' : "({$oldIssue['productNr']}) {$oldIssue['productName']}",
          'new' => empty($relatedProduct) ? '-' : "({$relatedProduct->nr}) {$relatedProduct->name}"
        );

        continue;
      }

      $changes[] = array('field' => $field,
                         'old' => $oldValue,
                         'new' => $newValue);
    }
  }

  if (empty($changes) && !$changedRelatedObject && empty($issue['tasks']))
  {
    $errors[] = 'Nie dokonano żadnych zmian.';

    goto VIEW;
  }

  if ($changedRelatedObject)
  {
    $oldRelatedObject = array($oldIssue['relatedFactory'] => $oldIssue['factoryName'],
                              $oldIssue['relatedMachine'] => $oldIssue['machineName'],
                              $oldIssue['relatedDevice'] => $oldIssue['deviceName'],);

    $newRelatedObject = array($issue['relatedFactory'] => null,
                              $issue['relatedMachine'] => null,
                              $issue['relatedDevice'] => null,);

    $tables = array('factories', 'machines', 'engines');

    foreach ($newRelatedObject as $id => $label)
    {
      if ($id === isset($oldRelatedObject[$id]))
      {
        $newRelatedObject[$id] = $oldRelatedObject[$id];
      }
      elseif (!empty($id))
      {
        $relatedObject = fetch_one('SELECT name FROM ' . current($tables) . ' WHERE id=?', array(1 => $id));

        $newRelatedObject[$id] = $relatedObject->name;
      }

      next($tables);
    }

    $changes[] = array(
      'field' => 'relatedObject',
      'old' => $oldRelatedObject,
      'new' => $newRelatedObject
    );
  }

  $informOwner = !empty($issue['informOwner']) && !empty($owner) && $owner->id !== $oldIssue['owner'];

  unset($issue['informOwner']);

  if (!empty($owner))
  {
    $issue['owner'] = $owner->id;
    $issue['ownerStakes'] = 0;
    $issue['ownerStakesType'] = 0;
  }

  $completedTasks = array();

  foreach ($oldIssue['tasks'] as $id => $summary)
  {
    if (in_array($id, $issue['tasks']))
    {
      $completedTasks[$id] = $summary;
    }
  }

  unset($issue['tasks']);

  $offer = fetch_one('SELECT id FROM offers WHERE issue=? LIMIT 1', array(1 => $oldIssue['id']));
  $relatedIssues = array();

  if (!empty($offer))
  {
    $offerItems = fetch_all('SELECT issue FROM offer_items WHERE offer=? AND issue IS NOT NULL', array(1 => $offer->id));

    foreach ($offerItems as $offerItem)
    {
      $relatedIssues[] = $offerItem->issue;
    }
  }

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    if (!empty($completedTasks))
    {
      exec_stmt('UPDATE issue_tasks SET completed=1, completedAt=' . time() . ', completedBy=' . $_SESSION['user']->getId() . ' WHERE id IN(' . implode(',', array_keys($completedTasks)) . ')');
    }

    if (!empty($owner))
    {
      exec_stmt('DELETE FROM issue_times WHERE issue=? and user=?', array(1 => $oldIssue['id'], $owner->id));
    }

    exec_update('issues', $issue, 'id=' . $oldIssue['id']);

    if (!empty($relatedIssues))
    {
      exec_update('issues', array(
        'orderNumber' => $issue['orderNumber'],
        'orderDate' => $issue['orderDate'],
        'orderInvoice' => $issue['orderInvoice'],
        'orderInvoiceDate' => $issue['orderInvoiceDate'],
        'status' => $issue['status'],
      ), 'id IN(' . implode(', ', $relatedIssues) . ')');
    }

    update_issue_completion_percent($oldIssue['id']);

    record_issue_change($oldIssue['id'], 0, $comment, $changes, $completedTasks);

    $conn->commit();
  }
  catch (PDOException $x)
  {
    $conn->rollBack();

    throw $x;
  }

  if ($informOwner)
  {
    send_assign_email($owner->email, $issue['subject'], $oldIssue['id']);
  }

  log_info('Zmodyfikowano zgłoszenie <%s>.', $issue['subject']);

  go_to('service/view.php?id=' . $oldIssue['id']);
}
else
{
  $issue = $oldIssue;
}

VIEW:

$issue += array('comment' => '', 'informOwner' => 0, 'tasks' => array());

$factories = fetch_array(sprintf('SELECT id AS `key`, name AS value FROM factories %s ORDER BY name', get_allowed_factories('WHERE id IN(%s)')));
$machines = array();
$devices = array();

if (!empty($issue['relatedFactory']) && has_access_to_factory($issue['relatedFactory']))
{
  $machines = fetch_array(sprintf('SELECT id AS `key`, name AS value FROM machines WHERE factory=? %s ORDER BY name', get_allowed_machines('AND id IN(%s)')), array(1 => $issue['relatedFactory']));
}

if (!empty($issue['relatedMachine']) && has_access_to_machine($issue['relatedMachine']))
{
  $devices = fetch_array('SELECT id AS `key`, name AS value FROM engines WHERE machine=? ORDER BY name', array(1 => $issue['relatedMachine']));
}

escape_array($issue);

if (!empty($relatedProduct))
{
  $issue['productNr'] = $relatedProduct->nr;
  $issue['productName'] = $relatedProduct->name;
}

$productName = '';

if (!empty($issue['relatedProduct']))
{
  $productName = e("({$issue['productNr']}) {$issue['productName']}");
}

?>

<? begin_slot('head') ?>
<style>
  .form-choice label { cursor: pointer; }
  .form-choice legend label { cursor: default; }
  .order fieldset { padding-top: 0; }
  .order ol label { font-weight: normal; }
  #issueDescription { height: 11.5em; }
  <? if ($issue['type'] != ISSUE_TYPE_ORDER): ?>
  .order { display: none; }
  <? endif ?>
  #issueExpectedFinishAt { width: 7.5em; text-align: center; }
  <? if (empty($machines)): ?>
  #issueRelatedMachine { display: none; }
  <? endif ?>
  <? if (empty($devices)): ?>
  #issueRelatedDevice { display: none; }
  <? endif ?>
  #relatedProductPreview {
    margin: .5em 0;
  }
  .ui-autocomplete .nr {
   display: block;
    font-weight: bold;
  }
  .ui-autocomplete .name {
    display: block;
    font-size: .8em;
  }
  #removeRelatedProduct img {
    vertical-align: top;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-ui/1.8.11/js/jquery.ui.datepicker-pl.js') ?>"></script>
<script>
$(function()
{
  function fixAutocomplete(e, ui)
  {
    $(this).data('autocomplete').menu.element.css('width', $(this).width() + 'px');
  }

  var $relatedProductPreview = $('#relatedProductPreview');
  var $relatedProduct = $('#issueRelatedProduct');

  if ($relatedProduct.val() == 0)
  {
    $relatedProductPreview.hide();
  }

  $('#issueRelatedProductName').autocomplete({
    source: '<?= url_for('catalog/products/fetch.php') ?>',
    minLength: 2,
    open: fixAutocomplete,
    select: function(e, ui)
    {
      $('#issueRelatedProduct').val(ui.item.id);
      $('#relatedProductNr').text(ui.item.nr);
      $('#relatedProductName').text(ui.item.name);

      $relatedProductPreview.fadeIn();
    }
  }).data('autocomplete')._renderItem = function(ul, item)
  {
    var label = '<a>';

    if (item.nr)
    {
      label += '<span class="nr">' + item.nr + '</span>';
    }

    label += '<span class="name">' + item.name + '</span></a>';

    return $('<li>')
      .data('item.autocomplete', item)
      .append(label)
      .appendTo(ul);
  };

  $('#removeRelatedProduct').click(function()
  {
    $relatedProductPreview.fadeOut(function()
    {
      $('#issueRelatedProduct').val('0');
      $('#relatedProductNr').text('-');
      $('#relatedProductName').text('-');
    });

    return false;
  });

  var $order = $('.order');

  $('#issue input[name="issue[type]"]').change(function()
  {
    if (this.value == 4)
    {
      $order.fadeIn();
    }
    else
    {
      $order.fadeOut();
    }
  });

  $('#issueOwner').autocomplete({
    source: '<?= url_for('service/fetch_people.php') ?>',
    minLength: 2,
    open: fixAutocomplete
  });

  $('#issueExpectedFinishAt').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: new Date()
  });

  $('#issueOrderDate').datepicker({
    dateFormat: 'yy-mm-dd'
  });

  $('#issueOrderInvoiceDate').datepicker({
    dateFormat: 'yy-mm-dd'
  });

  $('#issueRelatedFactory').change(function()
  {
    $('#issueRelatedDevice').fadeOut().html('<option value=0>');

    var relatedMachine = $('#issueRelatedMachine').html('<option value=0>');

    if (this.value == 0)
    {
      relatedMachine.fadeOut();
    }

    $.getJSON('<?= url_for('service/fetch_objects.php') ?>', {type: 1, parent: this.value}, function(machines)
    {
      var options = '';

      for (var i in machines)
      {
        options += '<option value="' + machines[i].value + '">' + machines[i].label;
      }

      if (options == '')
      {
        relatedMachine.fadeOut();
      }
      else
      {
        relatedMachine.append(options).fadeIn();
      }
    });
  });

  $('#issueRelatedMachine').change(function()
  {
    var relatedDevice = $('#issueRelatedDevice').html('<option value=0>');

    if (this.value == 0)
    {
      relatedDevice.fadeOut();
    }

    $.getJSON('<?= url_for('service/fetch_objects.php') ?>', {type: 2, parent: this.value}, function(devices)
    {
      var options = '';

      for (var i in devices)
      {
        options += '<option value="' + devices[i].value + '">' + devices[i].label;
      }

      if (options == '')
      {
        relatedDevice.fadeOut();
      }
      else
      {
        relatedDevice.append(options).fadeIn();
      }
    });
  });
});
</script>
<? append_slot() ?>

<? decorate("Edycja zgłoszenia") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja zgłoszenia</h1>
  </div>
  <div class="block-body">
    <form id="issue" class="form" method="post" action="<?= url_for("service/edit.php?id={$oldIssue['id']}") ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Edycja zgłoszenia</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label('issueSubject', 'Temat*') ?>
            <input id="issueSubject" name="issue[subject]" type="text" maxlength="200" autofocus value="<?= $issue['subject'] ?>">
          <li>
            <?= label('issueDescription', 'Opis') ?>
            <textarea id="issueDescription" class="markdown resizable" name="issue[description]" rows="5"><?= $issue['description'] ?></textarea>
          <li>
            <label for="issueExpectedFinishAt">Przewidywana data zakończenia</label>
            <input id="issueExpectedFinishAt" name="issue[expectedFinishAt]" type="text" value="<?= $issue['expectedFinishAt'] ?>" class="date" placeholder="YYYY-MM-DD" maxlength="10">
          <li>
            <?= label('issueOwner', 'Właściciel') ?>
            <input id="issueOwner" name="issue[owner]" type="text" maxlength="50" value="<?= $issue['owner'] ?>">
          <li class="form-choice">
            <input id="informOwner" name="issue[informOwner]" type="checkbox" value="1" <?= checked_if($issue['informOwner']) ?>>
            <?= label('informOwner', 'Poinformuj nowego właściciela o tym zgłoszeniu.') ?>
          <li class="horizontal">
            <?= label('issueRelatedFactory', 'Powiązany obiekt') ?>
            <ol>
              <li>
                <select id="issueRelatedFactory" name="issue[relatedFactory]">
                  <option value="0"></option>
                  <?= render_options($factories, $issue['relatedFactory']) ?>
                </select>
              <li>
                <select id="issueRelatedMachine" name="issue[relatedMachine]">
                  <option value="0"></option>
                  <?= render_options($machines, $issue['relatedMachine']) ?>
                </select>
              <li>
                <select id="issueRelatedDevice" name="issue[relatedDevice]">
                  <option value="0"></option>
                  <?= render_options($devices, $issue['relatedDevice']) ?>
                </select>
            </ol>
          <li>
            <?= label('issueRelatedProductName', 'Powiązany produkt') ?>
            <p id="relatedProductPreview">
              <?= fff('Usuń powiązanie', 'cross', '#removeRelatedProduct', 'removeRelatedProduct') ?>
              (<span id="relatedProductNr"><?= $issue['productNr'] ?></span>)
              <span id="relatedProductName"><?= $issue['productName'] ?></span>
            </p>
            <input id="issueRelatedProduct" name="issue[relatedProduct]" type="hidden" value="<?= (int)$issue['relatedProduct'] ?>">
            <input id="issueRelatedProductName" type="text" value="">
          <li class="horizontal">
            <ol id="issueChoices">
              <li class="form-choice">
                <?= render_choice('Priorytet', 'issuePriority', 'issue[priority]', $priorities, $issue['priority']) ?>
              <li class="form-choice">
                <?= render_choice('Rodzaj', 'issueKind', 'issue[kind]', $kinds, $issue['kind']) ?>
              <li class="form-choice">
                <?= render_choice('Typ', 'issueType', 'issue[type]', $types, $issue['type']) ?>
              <li class="order">
                <fieldset>
                  <legend>
                    <label for="issueOrderNumber">Zamówienie</label>
                  </legend>
                  <ol class="form-fields">
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="issueOrderNumber">Numer zamówienia</label>
                          <input id="issueOrderNumber" name="issue[orderNumber]" type="text" maxlength="200" value="<?= $issue['orderNumber'] ?>">
                        <li>
                          <label for="issueOrderDate">Data zamówienia</label>
                          <input id="issueOrderDate" name="issue[orderDate]" type="text" value="<?= $issue['orderDate'] ?>" class="date" placeholder="YYYY-MM-DD" maxlength="10">
                      </ol>
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="issueOrderInvoice">Numer faktury <strong>i pozycja</strong></label>
                          <input id="issueOrderInvoice" name="issue[orderInvoice]" type="text" maxlength="200" value="<?= $issue['orderInvoice'] ?>">
                        <li>
                          <label for="issueOrderInvoiceDate">Data faktury</label>
                          <input id="issueOrderInvoiceDate" name="issue[orderInvoiceDate]" type="text" value="<?= $issue['orderInvoiceDate'] ?>" class="date" placeholder="YYYY-MM-DD" maxlength="10">
                      </ol>
                  </ol>
                </fieldset>
              <li class="order">
                <fieldset>
                  <legend>
                    <label for="issueOrderPrice">Przedmiot</label>
                  </legend>
                  <ol class="form-fields">
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="issueOrderPrice">Cena</label>
                          <input id="issueOrderPrice" name="issue[price]" type="text" maxlength="30" value="<?= $issue['price'] ?>">
                        <li>
                          <label for="issueOrderCurrency">Waluta</label>
                          <input id="issueOrderCurrency" name="issue[currency]" type="text" value="<?= $issue['currency'] ?>" maxlength="3">
                        <li>
                          <label for="issueOrderVat">VAT</label>
                          <input id="issueOrderVat" name="issue[vat]" type="text" value="<?= $issue['vat'] ?>" maxlength="2">
                      </ol>
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="issueOrderQuantity">Ilość</label>
                          <input id="issueOrderQuantity" name="issue[quantity]" type="text" maxlength="30" value="<?= $issue['quantity'] ?>">
                        <li>
                          <label for="issueOrderUnit">Jednostka</label>
                          <input id="issueOrderUnit" name="issue[unit]" type="text" value="<?= $issue['unit'] ?>" maxlength="30">
                        <li>
                          <label for="issueOrderPer">Za</label>
                          <input id="issueOrderPer" name="issue[per]" type="text" value="<?= $issue['per'] ?>" maxlength="11">
                      </ol>
                  </ol>
                </fieldset>
            </ol>
              <li class="form-choice">
                <?= render_choice('Status', 'issueStatus', 'issue[status]', $statuses, $issue['status']) ?>
          <? if (!empty($oldIssue['tasks'])): ?>
          <li class="form-choice">
            <?= render_choice('Zadania', 'tasks', 'issue[tasks][]', $oldIssue['tasks'], $issue['tasks'], true) ?>
          <? endif ?>
          <li>
            <?= label('issueComment', 'Komentarz') ?>
            <textarea id="issueComment" class="markdown resizable" name="issue[comment]" rows="5"><?= $issue['comment'] ?></textarea>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Edytuj zgłoszenie">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
