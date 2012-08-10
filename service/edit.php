<?php

include './_common.php';

if (empty($_GET['id'])) bad_request();

$query = <<<SQL
SELECT
  i.*,
  o.name AS owner,
  f.name AS factoryName,
  m.name AS machineName,
  d.name AS deviceName
FROM issues i
LEFT JOIN users o ON o.id=i.owner
LEFT JOIN factories f ON f.id=i.relatedFactory
LEFT JOIN machines m ON m.id=i.relatedMachine
LEFT JOIN engines d ON d.id=i.relatedDevice
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
$errors  = array();

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

  if (!empty($errors)) goto VIEW;

  foreach (array('relatedFactory', 'relatedMachine', 'relatedDevice', 'kind', 'type', 'orderNumber', 'orderDate', 'orderInvoice', 'orderInvoiceDate', 'expectedFinishAt') as $field)
    if (empty($issue[$field])) $issue[$field] = null;

  $comment = $issue['comment'];
  unset($issue['comment']);

  $changes              = array();
  $changedRelatedObject = false;

  foreach ($issue as $field => $newValue)
  {
    if ($newValue !== $oldIssue[$field])
    {
      if ($field === 'tasks') continue;
      
      if (substr($field, 0, 7) === 'related')
      {
        $changedRelatedObject = true;

        continue;
      }

      $changes[] = array('field' => $field,
                         'old'   => $oldIssue[$field],
                         'new'   => $newValue);
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
                              $oldIssue['relatedDevice']  => $oldIssue['deviceName'],);

    $newRelatedObject = array($issue['relatedFactory'] => null,
                              $issue['relatedMachine'] => null,
                              $issue['relatedDevice']  => null,);

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
      'old'   => $oldRelatedObject,
      'new'   => $newRelatedObject
    );
  }

  $informOwner = !empty($issue['informOwner']) && !empty($owner) && $owner->id !== $oldIssue['owner'];

  unset($issue['informOwner']);

  if (!empty($owner))
  {
    $issue['owner']           = $owner->id;
    $issue['ownerStakes']     = 0;
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
$machines  = array();
$devices   = array();

if (!empty($issue['relatedFactory']) && has_access_to_factory($issue['relatedFactory']))
{
	$machines = fetch_array(sprintf('SELECT id AS `key`, name AS value FROM machines WHERE factory=? %s ORDER BY name', get_allowed_machines('AND id IN(%s)')), array(1 => $issue['relatedFactory']));
}

if (!empty($issue['relatedMachine']) && has_access_to_machine($issue['relatedMachine']))
{
	$devices = fetch_array('SELECT id AS `key`, name AS value FROM engines WHERE machine=? ORDER BY name', array(1 => $issue['relatedMachine']));
}

escape_array($issue);


?>

<? begin_slot('head') ?>
<style>
  .form-choice label { cursor: pointer; }
  .form-choice legend label { cursor: default; }
  #order { padding-top: 0; }
  #order ol label { font-weight: normal; }
  #issueDescription { height: 11.5em; }
  <? if ($issue['type'] != ISSUE_TYPE_ORDER): ?>
  #order { display: none; }
  <? endif ?>
  #issueExpectedFinishAt { width: 7.5em; text-align: center; }
  <? if (empty($machines)): ?>
  #issueRelatedMachine { display: none; }
  <? endif ?>
  <? if (empty($devices)): ?>
  #issueRelatedDevice { display: none; }
  <? endif ?>
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-ui/1.8.11/development-bundle/ui/i18n/jquery.ui.datepicker-pl.js') ?>"></script>
<script>
$(function()
{
  $(document.getElementById('issue')['issue[type]']).change(function()
  {
    if (this.value == 4)
    {
      $('#order').fadeIn();
    }
    else
    {
      $('#order').fadeOut();
    }
  });

  function fixAutocomplete(e, ui)
  {
    $(this).data('autocomplete').menu.element.css('width', $(this).width() + 'px');
  }

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
            <input id="issueSubject" name="issue[subject]" type="text" maxlength="100" autofocus value="<?= $issue['subject'] ?>">
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
                          <input id="issueOrderNumber" name="issue[orderNumber]" type="text" maxlength="30" value="<?= $issue['orderNumber'] ?>">
                        <li>
                          <label for="issueOrderDate">Data zamówienia</label>
                          <input id="issueOrderDate" name="issue[orderDate]" type="text" value="<?= $issue['orderDate'] ?>" class="date" placeholder="YYYY-MM-DD" maxlength="10">
                      </ol>
                    <li class="horizontal">
                      <ol>
                        <li>
                          <label for="issueOrderInvoice">Numer faktury <strong>i pozycja</strong></label>
                          <input id="issueOrderInvoice" name="issue[orderInvoice]" type="text" maxlength="30" value="<?= $issue['orderInvoice'] ?>">
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
