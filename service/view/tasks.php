<?php

include __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT
  i.id,
  i.owner,
  i.creator
FROM issues i
WHERE i.id=?
LIMIT 1
SQL;

$issue = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($issue));

$query = <<<SQL
SELECT
  t.*,
  c.name AS creatorName,
  a.name AS assigneeName,
  f.name AS finisherName
FROM issue_tasks t
INNER JOIN users c ON c.id=t.createdBy
LEFT JOIN users a ON a.id=t.assignedTo
LEFT JOIN users f ON f.id=t.completedBy
WHERE t.issue=?
ORDER BY t.position ASC
SQL;

$currentUser = $_SESSION['user'];

$tasks = array_map(function($task) use($currentUser)
{
  $task->classNames = $task->completed ? 'resolved' : 'unresolved';

  if (!$task->assignedTo)
  {
    $task->classNames .= ' free';
  }
  else
  {
    $task->classNames .= $currentUser->getId() == $task->assignedTo ? ' own' : ' others';
  }

  return $task;
}, fetch_all($query, array(1 => $issue->id)));

$canAddTasks =
$canEditTasks =
$canDeleteTasks = $currentUser->isSuper() || $issue->owner == $currentUser->getId() || (!$issue->owner && is_allowed_to('service/edit'));

?>

<p id=taskFilters>
  <a href="#tasks?own" data-filter="own" data-group="user">Przypisane do mnie</a><a
  href="#tasks?others" data-filter="others" data-group="user">Przypisane do innych</a><a
  href="#tasks?others" data-filter="free" data-group="user">Nieprzypisane</a>
  &nbsp;
  <a href="#tasks?unresolved" data-filter="unresolved" data-group="state">Niewykonane</a><a
  href="#tasks?resolved" data-filter="resolved" data-group="state">Wykonane</a>
</p>

<div id=tasks>
  <? foreach ($tasks as $task): ?>
  <div class="task <?= $task->classNames ?>" data-id="<?= $task->id ?>">
    <h1 class="hd" title="Rozwiń">
      <?= $task->completed ? '&#x2611;' : '&#x2610;' ?> <span class="summary" title="Przenieś"><?= e($task->summary) ?></span>
      <? if ($canEditTasks): ?><span class="edit"><?= fff('Edytuj', 'pencil', "service/tasks/edit.php?id={$task->id}") ?></span><? endif ?>
      <? if ($canDeleteTasks): ?><span class="delete"><?= fff('Usuń', 'cross', "service/update.php?issue={$issue->id}&amp;what=deleteTask&amp;task={$task->id}") ?></span><? endif ?>
    </h1>
    <div class="bd">
      <div class="description"><?= markdown($task->description) ?></div>
      <dl>
        <dt class="createdBy">Dodane przez:
        <dd><?= e($task->creatorName) ?> @ <?= date('Y-m-d, H:i', $task->createdAt) ?>
        <? if (!empty($task->assigneeName)): ?>
        <dt class="assignedTo" data-id="<?= $task->assignedTo ?>">Przypisane do:
        <dd><?= e($task->assigneeName) ?>
        <? endif ?>
        <? if ($task->completed): ?>
        <dt class="completedBy" data-id="<?= $task->completedBy ?>">Wykonane przez:
        <dd><?= e($task->finisherName) ?> @ <?= date('Y-m-d, H:i', $task->completedAt) ?>
        <? endif ?>
      </dl>
    </div>
  </div>
  <? endforeach ?>
</div>

<? if ($canAddTasks): ?>
<form id=addTaskForm method=post action="<?= url_for("service/update.php?issue={$issue->id}&amp;what=newTask") ?>">
  <h1>Nowe zadanie</h1>
  <ol class="form-fields">
    <li>
      <?= label('addTaskFormSummary', 'Nazwa*') ?>
      <input id=addTaskFormSummary name="newTask[summary]" type="text">
    <li>
      <?= label('addTaskFormDescription', 'Opis') ?>
      <textarea id=addTaskFormDescription class="markdown resizable" name="newTask[description]"></textarea>
    <li>
      <?= label('addTaskFormAssignee', 'Wykonawca') ?>
      <select id=addTaskFormAssignee name="newTask[assignee]">
        <option value=->
      </select>
    <li>
      <input type="submit" value="Dodaj nowe zadanie">
  </ol>
</form>
<? endif ?>

<script id=taskTemplate type="text/x-jquery-tmpl">
  <div class="task ${classNames}" data-id="${id}">
    <h1 class="hd" title="Rozwiń">
      &#x2610; <span class="summary" title="Przenieś">${summary}</span>
      <? if ($canEditTasks): ?><span class="edit"><?= fff('Edytuj', 'pencil', "service/update.php?issue={$issue->id}&amp;what=editTask&amp;task=\${id}") ?></span><? endif ?>
      <? if ($canDeleteTasks): ?><span class="delete"><?= fff('Usuń', 'cross', "service/update.php?issue={$issue->id}&amp;what=deleteTask&amp;task=\${id}") ?></span><? endif ?>
    </h1>
    <div class="bd">
      <div class="description">{{html description}}</div>
      <dl>
        <dt class="createdBy">Dodane przez:
        <dd>${createdBy}
        {{if assigneeId}}
        <dt class="assignedTo" data-id="${assigneeId}">Przypisane do:
        <dd>${assigneeName}
        {{/if}}
      </dl>
    </div>
  </div>
</script>
<script>
$(function()
{
  var currentUser = <?= $currentUser->getId() ?>;

  $(document).delegate('a.modalFormCancel', 'click', function()
  {
    $.modal.close();

    return false;
  });

  var $tasks = $('#tasks');

  <? if ($canEditTasks): ?>
  $tasks.sortable({
    handle: '.summary',
    items: '> .task',
    helper: 'clone',
    axis: 'y',
    opacity: 0.8,
    update: function(e, ui)
    {
      var data = {
        task: ui.item.attr('data-id'),
        pos: $tasks.children().index(ui.item)
      };

      $.post('<?= url_for("service/update.php?issue={$issue->id}&what=reorderTasks") ?>', data);
    }
  });
  <? endif ?>

  if (localStorage)
  {
    $('.task', $tasks).each(function()
    {
      var $task = $(this);

      if (localStorage.getItem('tasks/' + $task.attr('data-id') + ':opened') == 1)
      {
        $task.find('.hd').attr('title', 'Zwiń');
        $task.find('.bd').show();
      }
    });
  }

  $tasks.delegate('h1.hd', 'click', function()
  {
    var $hd = $(this);
    var $bd = $hd.next('.bd');

    function persist()
    {
      if (!localStorage)
      {
        return;
      }

      var id = $hd.closest('.task').attr('data-id');

      if ($bd.is(':visible'))
      {
        localStorage.setItem('tasks/' + id + ':opened', 1);
      }
      else
      {
        localStorage.removeItem('tasks/' + id + ':opened');
      }
    }

    if ($bd.is(':visible'))
    {
      $bd.slideUp(function() { $hd.attr('title', 'Rozwiń'); persist(); });
    }
    else
    {
      $bd.slideDown(function() { $hd.attr('title', 'Zwiń'); persist(); });
    }
  });

  <? if ($canEditTasks): ?>
  $tasks.delegate('.edit a', 'click', function()
  {
    var $task = $(this).closest('.task');
    var taskId = $task.attr('data-id');
    var $form = $('<div></div>').appendTo(document.body);

    $form.load('<?= url_for("service/update.php?what=editTask&issue={$issue->id}&task=") ?>' + taskId, function()
    {
      $form.modal({
        onClose: function()
        {
          $.modal.close();

          $('#editTaskBlock').remove();
        }
      });

      $form.find('form').first().submit(function()
      {
        var $form = $(this);

        $.ajax({
          type: 'POST',
          url: this.action,
          data: $form.serialize(),
          success: function(data)
          {
            $.modal.close();

            $task.removeClass('free own others unresolved resolved');
            $task.find('.summary').text(data.summary);
            $task.find('.description').html(data.description);

            var $completedBy = $task.find('.completedBy');

            if (!data.completed)
            {
              $completedBy.hide().next().hide();
              $task.addClass('unresolved');
            }
            else
            {
              $task.addClass('resolved');
            }

            var $assignedTo = $task.find('.assignedTo');

            if (!$assignedTo.length)
            {
              $assignedTo = $('<dt class="assignedTo" data-id="0">Przypisane do:</dt>').insertAfter($task.find('.createdBy').next());

              $('<dd>-</dd>').insertAfter($assignedTo);
            }

            if (!data.assignedTo)
            {
              $assignedTo.hide().next().hide();
              $task.addClass('free');
            }
            else if (data.assignedTo != $assignedTo.attr('data-id'))
            {
              $assignedTo.attr('data-id', data.assignedTo).show().next().text(data.assignee).show();
            }
            else
            {
              $assignedTo.show().next().show();
            }

            if (data.assignedTo)
            {
              $task.addClass(data.assignedTo == currentUser ? 'own' : 'others');
            }

            if (typeof refreshTasks === 'function')
            {
              refreshTasks();
            }

            filterTasks();
          }
        });

        return false;
      });
    });

    return false;
  });
  <? endif ?>

  <? if ($canDeleteTasks): ?>
  $tasks.delegate('.delete a', 'click', function()
  {
    if (confirm('Na pewno chcesz usunąć wybrane zadanie?'))
    {
      var $task = $(this).closest('.task');

      $.ajax({
        type: 'POST',
        url: this.href,
        data: {_: 1},
        success: function()
        {
          $task.fadeOut(function()
          {
            $task.remove();

            if (typeof refreshTasks === 'function')
            {
              refreshTasks();
            }
          });
        }
      });
    }

    return false;
  });
  <? endif ?>

  var $owner = $('#owner');
  var assignees = [];
  <? if ($canAddTasks): ?>
  assignees.push($('#addTaskFormAssignee'));
  <? endif ?>
  <? if ($canEditTasks): ?>
  assignees.push($('#editTaskFormAssignee'));
  <? endif ?>

  if ($owner.length)
  {
    assignees.forEach(function($assignees)
    {
      $assignees.append('<option value=' + $owner.attr('data-id') + '>' + $owner.text());
    });
  }

  $('.assignee').each(function()
  {
    var $assignee = $(this);

    assignees.forEach(function($assignees)
    {
      $assignees.append('<option value=' + $assignee.attr('data-id') + '>' + $assignee.text());
    });
  });

  <? if ($canAddTasks): ?>
  $('#addTaskForm').submit(function()
  {
    var $form = $(this);

    $.ajax({
      type: 'POST',
      url: this.action,
      data: $form.serialize(),
      success: function(data)
      {
        $form[0].reset();

        $('#taskTemplate').tmpl(data).prependTo($tasks);

        if (typeof refreshTasks === 'function')
        {
          refreshTasks();
        }

        filterTasks();
      }
    });

    return false;
  });
  <? endif ?>

  $('#taskFilters').delegate('a', 'click', function()
  {
    var $filter = $(this);
    var setClass = $filter.hasClass('active') ? 'removeClass' : 'addClass';

    $filter.parent().find('.active[data-group="' + $filter.attr('data-group') + '"]').removeClass('active');
    $filter[setClass]('active');

    filterTasks();
  });

  filterTasks();

  function filterTasks()
  {
    var filters = [];

    $('#taskFilters a.active').each(function() { filters.push($(this).attr('data-filter')); });

    var classFilter = '.' + filters.join('.');

    $('.task', $tasks).each(function()
    {
      var $task = $(this);

      if (classFilter === '.')
      {
        $task.fadeIn('fast');
      }
      else
      {
        $task[$task.is(classFilter) ? 'fadeIn' : 'fadeOut']('fast');
      }
    });
  }
});
</script>
