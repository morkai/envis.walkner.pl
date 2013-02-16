<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

$issue = fetch_one('SELECT id, subject, owner, ownerStakes, ownerStakesType FROM issues WHERE id=? LIMIT 1', array(1 => $_GET['id']));

not_found_if(empty($issue));

$user = $_SESSION['user']->getId();
$isOwner = false;

if ($user == $issue->owner)
{
  $isOwner = true;

  $issue->stakes = $issue->ownerStakes;
  $issue->stakesType = $issue->ownerStakesType;
}
else
{
  $assignee = fetch_one('SELECT stakes, stakesType FROM issue_assignees WHERE issue=? AND assignee=? LIMIT 1', array(1 => $issue->id, $user));

  no_access_if(empty($assignee));

  $issue->stakes = $assignee->stakes;
  $issue->stakesType = $assignee->stakesType;
}

$issue->timeEntries = fetch_all('SELECT createdAt, timeSpent, comment FROM issue_times WHERE issue=? AND user=? ORDER BY createdAt ASC', array(1 => $issue->id, $user));

$issue->timeSpent = 0;

foreach ($issue->timeEntries as $timeEntry)
{
  $issue->timeSpent += $timeEntry->timeSpent;
}

$canViewAssignees = is_allowed_to('super') || $isOwner;

if ($canViewAssignees)
{
  $query = <<<SQL
SELECT
  u.id,
  u.name,
  a.stakes,
  a.stakesType,
  (SELECT SUM(t.timeSpent) FROM issue_times t WHERE t.issue=:issue AND t.user=a.assignee) AS timeSpent
FROM issue_assignees a
INNER JOIN users u
  ON u.id=a.assignee
WHERE a.issue=:issue AND a.assignee <> :user
SQL;

  $assignees = fetch_all($query, array(':issue' => $issue->id, ':user' => $user));

  if (!$isOwner)
  {
    $query = <<<SQL
SELECT
  u.id,
  u.name,
  i.ownerStakes AS stakes,
  i.ownerstakesType AS stakesType,
  (SELECT SUM(t.timeSpent) FROM issue_times t WHERE t.issue=:issue AND t.user=i.owner) AS timeSpent
FROM issues i
INNER JOIN users u
  ON u.id=i.owner
WHERE i.id=:issue
SQL;

    $assignees[] = fetch_one($query, array(':issue' => $issue->id));
  }
}

?>

<? begin_slot('head') ?>
<style>
  #updateTimeFormTimeSpent {
    width: 10em;
    text-align: right;
  }
  #stakes {
    float: left;
  }
  #stakesType {
    float: left;
    margin: 1.7em 0 1.25em 1em;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#updateTimeFormStakes').bind('blur', function()
  {
    this.value = parseFloat(this.value.replace(',', '.').replace(/[^0-9\.]/g, '')).toFixed(2);
  });
});
</script>
<? append_slot() ?>

<? decorate("Czas pracy") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Czas pracy przy &lt;<?= e($issue->subject) ?>&gt;</h1>
  </div>
  <div class="block-body">
    <p>Przy zgłoszeniu <strong>&lt;<a href="<?= url_for("service/view.php?id={$issue->id}") ?>"><?= e($issue->subject) ?></a>&gt;</strong> pracowałeś w sumie <strong>&lt;<?= $issue->timeSpent ?>&gt;</strong> godzin.</p>
    <p>
      Twoje należne wynagrodzenie wynosi
      <? if ($issue->stakesType == 0): ?>
      <strong>&lt;<?= $issue->stakes ?>&gt;</strong> zł za godzinę co daje w sumie <strong>&lt;<?= $issue->stakes * $issue->timeSpent ?>&gt;</strong> zł.
      <? else: ?>
      <strong>&lt;<?= $issue->stakes ?>&gt;</strong> zł.
      <? endif ?>
    </p>
    <? if (!empty($issue->timeEntries)): ?>
    <table>
      <thead>
        <tr>
          <th>Czas aktualizacji
          <th>Przepracowane godziny
          <th>Komentarz
      <tbody>
        <? foreach ($issue->timeEntries as $timeEntry): ?>
        <tr>
          <td><?= date('Y-m-d, H:i', $timeEntry->createdAt) ?>
          <td><?= $timeEntry->timeSpent ?>
          <td><?= empty($timeEntry->comment) ? '-' : e($timeEntry->comment) ?>
        <? endforeach ?>
    </table>
    <? endif ?>
  </div>
</div>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Aktualizacja czasu pracy</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("service/update.php?issue={$issue->id}&amp;what=time") ?>">
      <ol class="form-fields">
        <li>
          <?= label('updateTimeFormComment', 'Komentarz') ?>
          <input id="updateTimeFormComment" name="updateTime[comment]" type="text" maxlength="250" value="">
        <li>
          <?= label('updateTimeFormTimeSpent', 'Przepracowany czas') ?>
          <input id="updateTimeFormTimeSpent" name="updateTime[timeSpent]" type="number" value="0" min="0" max="999">
        <li>
          <div id="stakes">
            <?= label('updateTimeFormStakes', 'Stawka (zł)') ?>
            <input id="updateTimeFormStakes" name="updateTime[stakes]" type="text" value="<?= $issue->stakes ?>">
          </div>
          <div id="stakesType">
            <input id="updateTimeFormStakesType0" name="updateTime[stakesType]" type="radio" value="0" <?= checked_if($issue->stakesType == 0) ?>>
            <?= label('updateTimeFormStakesType0', 'godzinna') ?>
            &nbsp;
            <input id="updateTimeFormStakesType1" name="updateTime[stakesType]" type="radio" value="1" <?= checked_if($issue->stakesType == 1) ?>>
            <?= label('updateTimeFormStakesType1', 'stała') ?>
          </div>
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Aktualizuj czas pracy">
          </ol>
      </ol>
    </form>
  </div>
</div>

<? if (!empty($assignees)): ?>
<div class="block">
  <div class="block-header">
    <h1 class="block-name">Czas pracy przypisanych osób</h1>
  </div>
  <div class="block-body">
    <table>
      <thead>
        <tr>
          <th>Imię i nazwisko
          <th>Przepracowane godziny
          <th>Stawka
          <th>Należność
      <tbody>
        <? foreach ($assignees as $assignee): ?>
        <tr>
          <td><a href="<?= url_for("user/view.php?id={$assignee->id}") ?>"><?= e($assignee->name) ?></a>
          <td><?= $assignee->timeSpent ? $assignee->timeSpent : '0' ?>
          <td><?= $assignee->stakes ?> zł <?= $assignee->stakesType == 0 ? 'za godz.' : '' ?>
          <td>
            <? if($assignee->stakesType == 0): ?>
              <?= $assignee->timeSpent ? round($assignee->timeSpent * $assignee->stakes, 2) : '0.00' ?>
            <? else: ?>
              <?= $assignee->stakes ?>
            <? endif ?>
            zł
        <? endforeach ?>
    </table>
  </div>
</div>
<? endif ?>
