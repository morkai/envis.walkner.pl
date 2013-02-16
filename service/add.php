<?php

include_once __DIR__ . '/_common.php';

no_access_if_not_allowed('service/add');

$referer = get_referer('service');

$newIssue = array(
  'owner' => '',
  'assignees' => '',
  'relatedFactory' => 0,
  'relatedMachine' => 0,
  'relatedDevice' => 0,
  'relatedProduct' => null,
  'priority' => 2,
  'kind' => 3,
  'type' => 3,
  'subject' => '',
  'description' => '',
  'informPeople' => 0,
  'orderNumber' => '',
  'orderDate' => '',
  'orderInvoice' => '',
  'orderInvoiceDate' => '',
  'expectedFinishAt' => '',
  'quantity' => 1,
  'unit' => 'szt.',
  'per' => 1,
  'price' => '0.00',
  'currency' => 'PLN',
  'vat' => 23
);

$canAssign = is_allowed_to('service/assigning');

$errors = array();

if (!empty($_POST['newIssue']))
{
  $newIssue = $issue = array_merge($newIssue, $_POST['newIssue']);

  if (is_empty($issue['subject']))
  {
    $errors[] = 'Temat jest wymagany.';
  }

  if (!empty($errors)) goto VIEW;

  if (!$canAssign)
  {
    $issue['owner'] = null;
    $issue['assignees'] = array();
    $informPeople = false;
  }
  else
  {
    $informPeople = $issue['informPeople'] == 1;

    if (!empty($issue['owner']))
    {
      $owner = fetch_one('SELECT id, email, name FROM users WHERE name=?', array(1 => $issue['owner']));

      $issue['owner'] = empty($owner) ? null : (int)$owner->id;
    }
    else
    {
      $issue['owner'] = null;
    }

    $assignees = preg_split('/\s*,\s*/', $issue['assignees']);

    if (!empty($assignees))
    {
      $assignees = fetch_all(sprintf('SELECT id, name, email FROM users WHERE name IN("%s")', implode('","', $assignees)));
    }
  }

  unset($issue['informPeople'], $issue['assignees']);

  $issue['creator'] = $_SESSION['user']->getId();
  $issue['createdAt'] = $issue['updatedAt'] = time();

  foreach (array('relatedFactory', 'relatedMachine', 'relatedDevice', 'relatedProduct', 'kind', 'type', 'orderNumber', 'orderDate', 'orderInvoice', 'orderInvoiceDate', 'expectedFinishAt') as $field)
    if (empty($issue[$field])) $issue[$field] = null;

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    exec_insert('issues', $issue);

    $issueId = $conn->lastInsertId();

    $stmt = prepare_stmt('INSERT INTO issue_assignees SET issue=?, assignee=?');

    foreach ($assignees as $assignee)
    {
      if ($assignee->id === $issue['owner'] || $assignee->id === $issue['creator']) continue;

      $stmt->execute(array($issueId, $assignee->id));
    }

    $conn->commit();
  }
  catch (PDOException $x)
  {
    $conn->rollBack();

    throw $x;
  }

  if ($informPeople)
  {
    $receivers = empty($assignees) ? array()
                                   : array_map(function($assignee) { return $assignee->email; }, $assignees);

    if (!empty($owner->email)) $receivers[] = $owner->email;

    foreach ((array)array_search($_SESSION['user']->getEmail(), $receivers) as $key)
      unset($receivers[$key]);

    send_assign_email($receivers, $issue['subject'], $issueId);
  }

  log_info('Dodano nowe zgłoszenie <%s>.', $issue['subject']);

  go_to('service/view.php?id=' . $issueId);
}

VIEW:

$factories = fetch_array(sprintf('SELECT id AS `key`, name AS value FROM factories %s ORDER BY name', get_allowed_factories('WHERE id IN(%s)')));
$machines = array();
$devices = array();

if (!empty($newIssue['relatedFactory']) && has_access_to_factory($newIssue['relatedFactory']))
{
  $machines = fetch_array(sprintf('SELECT id AS `key`, name AS value FROM machines WHERE factory=? %s ORDER BY name', get_allowed_machines('AND id IN(%s)')), array(1 => $newIssue['relatedFactory']));
}

if (!empty($newIssue['relatedMachine']) && has_access_to_machine($newIssue['relatedMachine']))
{
  $devices = fetch_array('SELECT id AS `key`, name AS value FROM engines WHERE machine=? ORDER BY name', array(1 => $newIssue['relatedMachine']));
}

escape_array($newIssue);

$canAddDevice = is_allowed_to('machine/device/add');

?>

<? begin_slot('head') ?>
<style>
  .form-choice label { cursor: pointer; }
  .form-choice legend label { cursor: default; }
  .order { padding-top: 0; }
  .order ol label { font-weight: normal; }
  #newIssueDescription { height: 11.5em; }
  <? if ($newIssue['type'] != ISSUE_TYPE_ORDER): ?>
  .order { display: none; }
  <? endif ?>
  <? if (empty($machines)): ?>
  #newIssueRelatedMachine { display: none; }
  <? endif ?>
  <? if (empty($devices)): ?>
  #newIssueRelatedDevice { display: none; }
  #relatedDevices { display: none; }
  <? endif ?>
  #relatedObjects select,
  #relatedObjects img
  {
    vertical-align: middle;
    cursor: pointer;
  }
  #newDeviceForm { display: none; }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<? if ($canAssign): ?>
<script src="<?= url_for_media('jquery-ui/1.8.11/development-bundle/ui/i18n/jquery.ui.datepicker-pl.js') ?>"></script>
<? endif ?>
<? if ($canAddDevice): ?>
<script src="<?= url_for_media('jquery-plugins/simplemodal/1.3/jquery.simplemodal.min.js') ?>"></script>
<? endif ?>
<script>
$(function()
{
  $(document.getElementById('newIssue')['newIssue[type]']).change(function()
  {
    if (this.value == 4)
    {
      $('.order').fadeIn();
    }
    else
    {
      $('.order').fadeOut();
    }
  });

  <? if ($canAssign): ?>
  function fixAutocomplete(e, ui)
  {
    $(this).data('autocomplete').menu.element.css('width', $(this).width() + 'px');
  }

  $('#newIssueOwner').autocomplete({
    source: '<?= url_for('service/fetch_people.php') ?>',
    minLength: 2,
    open: fixAutocomplete
  });

  function split(val)
  {
    return val.split(/,\s*/);
  }

  function extractLast(term)
  {
    return split(term).pop();
  }

  $('#newIssueAssignees').autocomplete(
  {
    source: function(request, response)
    {
      var names = split(this.element.val());
      names.push($('#newIssueOwner').val());

      $.getJSON(
        '<?= url_for('service/fetch_people.php') ?>',
        {term: extractLast(request.term)},
        function(people)
        {
          var peoples = [];

          for (var i in people)
          {
            if ($.inArray(people[i], names) == -1)
            {
              peoples.push(people[i]);
            }
          }

          response(peoples);
        }
      );
    },
    search: function()
    {
      return extractLast(this.value).length >= 2;
    },
    focus: function()
    {
      return false;
    },
    select: function(event, ui)
    {
      var terms = split(this.value);
      terms.pop();
      terms.push(ui.item.value);
      terms.push('');

      this.value = terms.join(', ');

      return false;
    },
    open: fixAutocomplete
  });

  $('#newIssueExpectedFinishAt').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: new Date()
  });
  <? endif ?>

  $('#newIssueOrderDate').datepicker({
    dateFormat: 'yy-mm-dd'
  });

  $('#newIssueOrderInvoiceDate').datepicker({
    dateFormat: 'yy-mm-dd'
  });

  $('#newIssueRelatedFactory').change(function()
  {
    $('#newIssueRelatedDevice').html('<option value=0>').parent().fadeOut().end().hide();

    var relatedMachine = $('#newIssueRelatedMachine').html('<option value=0>');

    if (this.value == 0)
    {
      relatedMachine.fadeOut();

      return;
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

  $('#newIssueRelatedMachine').change(function()
  {
    var relatedDevice = $('#newIssueRelatedDevice').html('<option value=0>');

    if (this.value == 0)
    {
      relatedDevice.parent().fadeOut();

      return;
    }
    else
    {
      relatedDevice.parent().fadeIn();
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

  <? if ($canAddDevice): ?>
  function restoreNewDeviceForm()
  {
    $.modal.close();

    $('#newDeviceForm').html('<p class=block>Ładowanie...</p>');
  }

  $('#relatedDevices > img').click(function()
  {
    var el = $('#newDeviceForm');

    el.modal({
      overlayCss: {
        backgroundColor: '#000',
        cursor: 'wait'
      },
      containerCss: {
        textAlign: 'left',
        width: '500px'
      },
      onClose: restoreNewDeviceForm
    });

    el.load('<?= url_for('factory/machine/engine/add.php?body=only&machine=') ?>' + $('#newIssueRelatedMachine').val(), function()
    {
      center($('#simplemodal-container'), el);

      el.find('ol.form-actions a').click(function()
      {
        restoreNewDeviceForm();

        return false;
      });

      el.find('form').submit(function()
      {
        var form = $(this);
        var submit = form.find('ol.form-actions input').attr('disabled', 'disabled');

        $.post(this.action, form.serializeArray(), function(response)
        {
          if (response.status)
          {
            $('#newIssueRelatedDevice').append('<option value="' + response.data.id + '">' + response.data.name)
                                       .val(response.data.id)
                                       .show()
                                       .focus();

            restoreNewDeviceForm();
          }
          else
          {
            console.log(response.errors);

            var errors = form.find('ul.form-errors');

            if (errors.size())
            {
              errors.replaceWith(response.errors);
            }
            else
            {
              form.find('ol.form-fields').before(response.errors);
            }
          }

          submit.removeAttr('disabled');
        });

        return false;
      });
    });
  });
  <? endif ?>
});
</script>
<? append_slot() ?>

<? decorate("Dodawanie nowego zgłoszenia - Serwis") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowe zgłoszenie</h1>
  </div>
  <div class="block-body">
    <form id="newIssue" class="form" method="post" action="<?= url_for('service/add.php') ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Nowe zgłoszenie</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label('newIssueSubject', 'Temat', true) ?>
            <input id="newIssueSubject" name="newIssue[subject]" type="text" maxlength="100" autofocus value="<?= $newIssue['subject'] ?>">
          <li>
            <?= label('newIssueDescription', 'Opis') ?>
            <textarea id="newIssueDescription" class="markdown resizable" name="newIssue[description]" rows="5"><?= $newIssue['description'] ?></textarea>
          <? if ($canAssign): ?>
          <li>
            <label for="newIssueExpectedFinishAt">Przewidywana data zakończenia</label>
            <input id="newIssueExpectedFinishAt" name="newIssue[expectedFinishAt]" type="text" value="<?= $newIssue['expectedFinishAt'] ?>" class="date" placeholder="YYYY-MM-DD" maxlength="10">
          <li>
            <div class="yui-gd">
              <div class="yui-u first">
                <?= label('newIssueOwner', 'Właściciel') ?>
                <input id="newIssueOwner" name="newIssue[owner]" type="text" maxlength="50" value="<?= $newIssue['owner'] ?>">
              </div>
              <div class="yui-u">
                <?= label('newIssueAssignees', 'Przypisane osoby') ?>
                <input id="newIssueAssignees" name="newIssue[assignees]" type="text" maxlength="250" value="<?= $newIssue['assignees'] ?>">
              </div>
            </div>
          <li class="form-choice">
            <input id="newIssueInformPeople" name="newIssue[informPeople]" type="checkbox" value="1" <?= checked_if($newIssue['informPeople']) ?>>
            <?= label('newIssueInformPeople', 'Poinformuj właściciela i przypisane osoby o tym zgłoszeniu.') ?>
          <? endif ?>
          <li class="horizontal">
            <?= label('newIssueRelatedFactory', 'Powiązany obiekt') ?>
            <ol id="relatedObjects">
              <li>
                <select id="newIssueRelatedFactory" name="newIssue[relatedFactory]">
                  <option value="0"></option>
                  <?= render_options($factories, $newIssue['relatedFactory']) ?>
                </select>
              <li>
                <select id="newIssueRelatedMachine" name="newIssue[relatedMachine]">
                  <option value="0"></option>
                  <?= render_options($machines, $newIssue['relatedMachine']) ?>
                </select>
              <li id="relatedDevices">
                <select id="newIssueRelatedDevice" name="newIssue[relatedDevice]">
                  <option value="0"></option>
                  <?= render_options($devices, $newIssue['relatedDevice']) ?>
                </select>
                <?= $canAddDevice ? fff('Stwórz nowe urządzenie', 'add') : '' ?>
            </ol>
          <li class="horizontal">
            <ol id="newIssueChoices">
              <li class="form-choice">
                <?= render_choice('Priorytet', 'newIssuePriority', 'newIssue[priority]', $priorities, $newIssue['priority']) ?>
              <li class="form-choice">
                <?= render_choice('Rodzaj', 'newIssueKind', 'newIssue[kind]', $kinds, $newIssue['kind']) ?>
              <li class="form-choice">
                <?= render_choice('Typ', 'newIssueType', 'newIssue[type]', $types, $newIssue['type']) ?>
              <li class="order">
                <fieldset>
                  <legend>
                    <label for="newIssueOrderNumber">Zamówienie</label>
                  </legend>
                  <ol class="form-fields">
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="newIssueOrderNumber">Numer zamówienia</label>
                          <input id="newIssueOrderNumber" name="newIssue[orderNumber]" type="text" maxlength="30" value="<?= $newIssue['orderNumber'] ?>">
                        <li>
                          <label for="newIssueOrderDate">Data zamówienia</label>
                          <input id="newIssueOrderDate" name="newIssue[orderDate]" type="text" value="<?= $newIssue['orderDate'] ?>" class="date" placeholder="YYYY-MM-DD" maxlength="10">
                      </ol>
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="newIssueOrderInvoice">Numer faktury <strong>i pozycja</strong></label>
                          <input id="newIssueOrderInvoice" name="newIssue[orderInvoice]" type="text" maxlength="30" value="<?= $newIssue['orderInvoice'] ?>">
                        <li>
                          <label for="newIssueOrderInvoiceDate">Data faktury</label>
                          <input id="newIssueOrderInvoiceDate" name="newIssue[orderInvoiceDate]" type="text" value="<?= $newIssue['orderInvoiceDate'] ?>" class="date" placeholder="YYYY-MM-DD" maxlength="10">
                      </ol>
                  </ol>
                </fieldset>
              <li class="order">
                <fieldset>
                  <legend>
                    <label for="newIssueOrderPrice">Przedmiot</label>
                  </legend>
                  <ol class="form-fields">
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="newIssueOrderPrice">Cena</label>
                          <input id="newIssueOrderPrice" name="newIssue[price]" type="text" maxlength="30" value="<?= $newIssue['price'] ?>">
                        <li>
                          <label for="newIssueOrderCurrency">Waluta</label>
                          <input id="newIssueOrderCurrency" name="newIssue[currency]" type="text" value="<?= $newIssue['currency'] ?>" maxlength="3">
                        <li>
                          <label for="newIssueOrderVat">VAT</label>
                          <input id="newIssueOrderVat" name="newIssue[vat]" type="text" value="<?= $newIssue['vat'] ?>" maxlength="2">
                      </ol>
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="newIssueOrderQuantity">Ilość</label>
                          <input id="newIssueOrderQuantity" name="newIssue[quantity]" type="text" maxlength="30" value="<?= $newIssue['quantity'] ?>">
                        <li>
                          <label for="newIssueOrderUnit">Jednostka</label>
                          <input id="newIssueOrderUnit" name="newIssue[unit]" type="text" value="<?= $newIssue['unit'] ?>" maxlength="30">
                        <li>
                          <label for="newIssueOrderPer">Za</label>
                          <input id="newIssueOrderPer" name="newIssue[per]" type="text" value="<?= $newIssue['per'] ?>" maxlength="11">
                      </ol>
                  </ol>
                </fieldset>
            </ol>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj nowe zgłoszenie">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
<? if ($canAddDevice): ?>
<div id="newDeviceForm">
  <p class="block">Ładowanie...</p>
</div>
<? endif ?>
