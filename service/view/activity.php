<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$issue = fetch_one('SELECT id, owner, creator, relatedFactory, relatedMachine FROM issues WHERE id=? LIMIT 1', array(1 => $_GET['id']));

not_found_if(empty($issue));

$query = <<<SQL
SELECT
  h.id, h.createdAt, h.createdBy, h.changes, h.tasks, h.comment, h.system,
  c.name AS creator
FROM issue_history h
INNER JOIN users c ON c.id=h.createdBy
WHERE h.issue=?
ORDER BY h.createdAt DESC
SQL;

$entries = array_map(function($entry)
{
  $entry->timeAgo      = format_time_ago($entry->createdAt);
  $entry->creationTime = date('Y-m-d, H:i', $entry->createdAt);
  $entry->creator      = e($entry->creator);

  if (!$entry->system)
  {
    $entry->comment = markdown(trim($entry->comment));
  }

  if ($entry->changes !== null)
  {
    $entry->showChanges = true;
    $entry->changes     = array_map('prepare_issue_changes', unserialize($entry->changes));
  }
  else
  {
    $entry->showChanges = false;
    $entry->changes     = array();
  }

  if (!empty($entry->tasks))
  {
    $entry->showChanges = true;
    $entry->tasks       = unserialize($entry->tasks);
  }
  else
  {
    $entry->tasks = array();
  }

  return $entry;
}, fetch_all($query, array(1 => $issue->id)));

$history   = array();
$lastEntry = null;

foreach ($entries as $entry)
{
  if ($lastEntry === null)
  {
    $lastEntry = $entry;

    continue;
  }

  if ($entry->system && $lastEntry->system && abs($entry->createdAt - $lastEntry->createdAt) < 300 && $entry->createdBy === $lastEntry->createdBy)
  {
    $lastEntry->comment .= '<li>' . $entry->comment;
  }
  else
  {
    if ($lastEntry->system)
    {
      $lastEntry->comment = '<ul class="systemChanges"><li>' . $lastEntry->comment . '</ul>';
    }
    $history[] = $lastEntry;
    $lastEntry = $entry;
  }
}

$count = count($history);

if ($count && $history[$count - 1] !== $lastEntry)
{
  $history[] = $lastEntry;
}

$currentUser = $_SESSION['user'];

$unresolvedTasks = fetch_array('SELECT id AS `key`, summary AS `value` FROM issue_tasks WHERE issue=? AND completed=0 AND (assignedTo IS NULL OR assignedTo=?) ORDER BY position', array(1 => $issue->id, $currentUser->getId()));

$isCreator  = $currentUser->getId() == $issue->creator;
$isOwner    = $currentUser->getId() == $issue->owner;
$isAssignee = fetch_one('SELECT 1 FROM issue_assignees WHERE issue=? AND assignee=? LIMIT 1', array(1 => $issue->id, $currentUser->getId())) ? true : false;

$docsViewer       = is_issue_docs_viewer($currentUser, $issue);
$docsViewerSuffix = $docsViewer ? '&docs=1' : '';

$canUpdateIssue   = $currentUser->isSuper() || $isCreator || $isOwner || $isAssignee || $docsViewer;
$canCompleteTasks = $currentUser->isSuper() || $isOwner || $isAssignee;
$canUpdateTime    = $canCompleteTasks;

$now  = time();
$week = 24 * 3600 * 7;

?>

<div id=entries>
  <? foreach ($history as $i => $entry): ?>
  <div id=entry-<?= $entry->id ?> data-id=<?= $entry->id ?> class="entry <?= $entry->system ? '' : 'quotable' ?> <?= $now - $entry->createdAt > $week ? 'collapsed' : '' ?> <?= $i < 2 ? 'expanded' : '' ?>">
    <h3><a href="<?= url_for("user/view.php?id={$entry->createdBy}") ?>"><?= $entry->creator ?></a>, <span title="<?= $entry->creationTime ?>"><?= $entry->timeAgo ?></span>:</h3>
    <? if ($entry->showChanges): ?>
    <ul class="changes">
      <? foreach ($entry->changes as $change): ?>
      <li>
        <span class="field"><?= $change['field'] ?></span>
        <? if (empty($change['old'])): ?>
        ustawiono na
        <? else: ?>
        zmieniono z &lt;<span class="oldValue"><?= $change['old'] ?></span>&gt; na
        <? endif ?>
        &lt;<span class="newValue"><?= $change['new'] ?></span>&gt;
      <? endforeach ?>
      <? foreach ($entry->tasks as $task): ?>
      <li>Wykonano zadanie &lt;<span class="field"><?= $task ?></span>&gt;
      <? endforeach ?>
    </ul>
    <? endif ?>
    <? if (!empty($entry->comment)): ?>
    <div class="comment">
      <? if (!$entry->system && $canUpdateIssue): ?>
      <a class="reply" href="<?= url_for("service/update.php?issue={$issue->id}&amp;what=reply{$docsViewerSuffix}") ?>"><?= fff('Cytuj', 'comment') ?></a>
      <? endif ?>
      <?= $entry->comment ?>
    </div>
    <? endif ?>
  </div>
  <? endforeach ?>
</div>

<? if ($canUpdateIssue): ?>
<form id=updateIssueForm method=post action="<?= url_for("service/update.php?issue={$issue->id}&amp;what=reply{$docsViewerSuffix}") ?>">
  <h1>Aktualizuj zgłoszenie</h1>
  <ol class="form-fields">
    <li>
      <?= label('updateIssueFormComment', 'Komentarz') ?>
      <textarea id=updateIssueFormComment class="markdown resizable" name=comment accesskey="c" title="Komentarz"></textarea>
    <? if ($canCompleteTasks): ?>
    <li>
      <?= label('updateIssueFormTasks', 'Wykonane zadania') ?>
      <select id=updateIssueFormTasks name=tasks[] multiple>
        <?= render_options($unresolvedTasks) ?>
      </select>
    <? endif ?>
    <? if ($canUpdateTime): ?>
    <li id=timeSpentContainer>
      <div id=timeContainer>
        <?= label('updateIssueFormTime', 'Przepracowane godz.') ?>
        <input id=updateIssueFormTime name=timeSpent type=number min=0 max=999 step=1 value=0>
      </div>
      <div id=timeCommentContainer>
        <?= label('updateIssueFormTimeComment', 'Powód') ?>
        <input id=updateIssueFormTimeComment name=reason type=text maxlength=250>
      </div>
    <? endif ?>
    <li>
      <input type=submit value="Aktualizuj zgłoszenie">
  </ol>
</form>
<? endif ?>

<script>
function refreshTasks()
{
  var $tasks = $('#updateIssueFormTasks').empty();

  $('#tasks').find('.task.unresolved').each(function()
  {
    var $task = $(this);

    if ($task.hasClass('others'))
    {
      return;
    }

    $tasks.append('<option value=' + $task.attr('data-id') + '>' + $task.find('.summary').text());
  });
}

$(function()
{
  $.makeAutoResizable();

  var $activity = $('#activity');
  
  var $collapsed = $activity.find('.collapsed').hide();

  if ($collapsed.length)
  {
    $('<h3 id=moreActivities><a href="#moreActivities">Pokaż starszą aktywność...</h3>').click(function()
    {
      $collapsed.fadeIn().removeClass('collapsed');
      $(this).remove();
    }).insertAfter($('#entries'));
  }

  $activity.delegate('div.collapsed', 'click', function()
  {
    $(this).removeClass('collapsed').addClass('expanded');
  });

  $activity.delegate('div.entry', 'dblclick', function()
  {
    var $entry = $(this);

    if (!$entry.hasClass('expanded'))
    {
      $entry.addClass('expanded');
    }
  });

  $activity.delegate('a.reply', 'click', function()
  {
    var $comment = $('#updateIssueFormComment');

    $.ajax({
      type: 'GET',
      url: '<?= url_for("service/view/quote.php?id=") ?>' + $(this).closest('.entry').attr('data-id'),
      success: function(quote)
      {
        var val = $.trim($comment.val());

        $comment.val((val === '' ? quote : (val + (val[val.length - 1] === '\n' ? '' : '\n') + '\n' + quote)) + '\n\n');
        $comment.keydown().focus();
      }
    });

    return false;
  });

  $('#updateIssueForm').submit(function()
  {
    var $form = $(this);

    $.ajax({
      type: 'POST',
      url: this.action,
      data: $form.serialize(),
      success: function(entry)
      {
        location.reload();
      }
    });

    return false;
  });
});
</script>
