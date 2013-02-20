<?php

include_once __DIR__ . '/_common.php';

no_access_if_not_allowed('service*');

$baseUrl = url_for('service/?');
$related = false;

if (!empty($_GET['relate']))
{
  $baseUrl .= '&relate=' . $_GET['relate'];
  $related = fetch_one('SELECT id, subject, owner FROM issues WHERE id=?', array(1 => $_GET['relate']));

  bad_request_if(empty($related));

  $currentUser = $_SESSION['user'];

  no_access_if_not($currentUser->isSuper()
                   || $related->owner == $currentUser->getId()
                   || (!$related->owner && is_allowed_to('service/edit')));

  $related->related = array_map(function($issue) { return $issue->issue2; },
                                fetch_all('SELECT issue2 FROM issue_relations WHERE issue1=?', array(1 => $related->id)));

  $related->related[] = $related->id;
}

include_once __DIR__ . '/../_lib_/PagedData.php';

function construct_issues_grid_queries(array $options)
{
  global $statuses, $priorities, $kinds, $types, $related;

  $bindings = array();
  $columns = array();
  $query = "SELECT %s\nFROM issues i ";

  foreach ($options['c'] as $column)
  {
    switch ($column)
    {
      case 'id':
      case 'subject':
      case 'priority':
      case 'type':
      case 'kind':
      case 'createdAt':
      case 'updatedAt':
      case 'expectedFinishAt':
      case 'orderNumber':
      case 'orderDate':
      case 'orderInvoice':
      case 'orderInvoiceDate':
        $columns[$column] = 'i.' . $column;
        break;

      case 'status':
        $columns['status'] = 'i.status';
        $columns['createdAt'] = 'i.createdAt';
        $columns['expectedFinishAt'] = 'i.expectedFinishAt';
        break;

      case 'owner':
        $columns['owner'] = 'i.owner';
        $columns['ownerName'] = 'o.name AS ownerName';

        $query .= "\nLEFT JOIN users o ON o.id=i.owner";
        break;

      case 'creator':
        $columns['creator'] = 'i.creator';
        $columns['creatorName'] = 'c.name AS creatorName';

        $query .= "\nINNER JOIN users c ON c.id=i.creator";
        break;

      case 'percent':
        $columns['percent'] = 'i.percent';

        $columns += array(
          'allTasks' => '(SELECT COUNT(*) FROM issue_tasks t WHERE t.issue=i.id) AS allTasks',
          'completedTasks' => '(SELECT COUNT(*) FROM issue_tasks t WHERE t.issue=i.id AND t.completed=1) AS completedTasks',
        );
        break;

      case 'relatedProduct':
        $columns['relatedProduct'] = 'i.relatedProduct';
        $columns['relatedProductName'] = 'p.name AS relatedProductName';

        $query .= "\nLEFT JOIN catalog_products p ON p.id=i.relatedProduct";
        break;
    }
  }

  $allowedFactories = implode(', ', $_SESSION['user']->getAllowedFactoryIds());
  $allowedMachines = '"' . implode('", "', $_SESSION['user']->getAllowedMachineIds()) . '"';

  $query .= <<<SQL

WHERE (1=:admin
  OR i.creator=:user
  OR i.owner=:user
  OR :user IN(SELECT assignee FROM issue_assignees WHERE issue=i.id)
  OR (((1=:editor AND i.owner IS NULL) OR 1=:docsViewer)
    AND (i.relatedFactory IS NULL
      OR i.relatedFactory IN({$allowedFactories})
    )
    AND (i.relatedMachine IS NULL
      OR i.relatedMachine IN({$allowedMachines})
    )
  )
)
SQL;

  if ($related)
  {
    $query .= sprintf('AND (i.id NOT IN(%s))', implode(',', $related->related));
  }

  if (!empty($options['f']['c']))
  {
    $conditions = array();

    foreach ($options['f']['c'] as $k => $column)
    {
      $bindingKey = ':' . $column . '_' . $k;
      $bindingValue = empty($options['f']['v'][$k]) ? 0 : $options['f']['v'][$k];

      if (empty($bindingValue)) continue;

      switch ($column)
      {
        case 'subject':
        case 'description':
        case 'owner':
        case 'creator':
        case 'orderNumber':
        case 'orderInvoice':
          if     ($column == 'owner')    $column = 'o.name';
          elseif ($column === 'creator') $column = 'c.name';
          else                           $column = 'i.' . $column;

          $condition = "{$column} LIKE {$bindingKey}";

          $escape = function($value) { return str_replace(array('%', '_'), array('\%', '\_'), $value); };

          switch ($options['f']['i'][$k])
          {
            case 'equals':
              $condition = "{$column} = {$bindingKey}";
              break;

            case 'starts':
              $bindingValue = $escape($bindingValue) . '%';
              break;

            case 'ends':
              $bindingValue = '%' . $escape($bindingValue);
              break;

            default:
              $bindingValue = '%' . $escape($bindingValue) . '%';
              break;
          }

          $conditions[] = $condition;
          $bindings[$bindingKey] = $bindingValue;
          break;

        case 'status':
        case 'kind':
        case 'type':
        case 'priority':
          if     ($column === 'status')   $values = $statuses;
          elseif ($column === 'priority') $values = $priorities;
          else                            $values = ${$column . 's'};

          if (is_array($bindingValue))
          {
            $condition = '(1=0';

            foreach ($bindingValue as $pk => $value)
            {
              if (!isset($values[$value])) continue;

              $condition .= ' OR i.' . $column . ' = ' . $bindingKey . '_' . $pk;

              $bindings[$bindingKey . '_' . $pk] = $value;
            }

            $condition .= ')';

            $conditions[] = $condition;
          }
          elseif (isset($values[$bindingValue]))
          {
            $conditions[] = "i.{$column} = {$bindingKey}";
            $bindings[$bindingKey] = $bindingValue;
          }
          break;

        case 'percent':
          switch ($options['f']['i'][$k])
          {
            case 'lt': $cmp = '<'; break;
            case 'gt': $cmp = '>'; break;
            default:   $cmp = '='; break;
          }

          $conditions[] = "i.percent {$cmp} {$bindingKey}";
          $bindings[$bindingKey] = (float)$bindingValue;
          break;

        case 'createdAt':
        case 'expectedFinishAt':
        case 'orderDate':
        case 'orderInvoiceDate':
          settype($bindingValue, 'array');

          if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $bindingValue[0]))
          {
            break;
          }

          $isDateType = $column !== 'createdAt';

          if (!$isDateType) foreach ($bindingValue as &$v)
          {
            $v = strtotime($v);
          }

          $condition = "i.{$column} ";

          switch ($options['f']['i'][$k])
          {
            case 'from':
              $condition .= '>= ' . ($isDateType ? "CAST({$bindingKey} AS DATE)" : $bindingKey);

              $bindings[$bindingKey] = $bindingValue[0];
              break;

            case 'between':
              $condition .= sprintf('BETWEEN %s AND %s',
                                    $isDateType ? "CAST({$bindingKey}_from AS DATE)" : "{$bindingKey}_from",
                                    $isDateType ? "CAST({$bindingKey}_to AS DATE)" : "{$bindingKey}_to");

              $bindings[$bindingKey . '_from'] = $bindingValue[0];
              $bindings[$bindingKey . '_to'] = $isDateType ? $bindingValue[1] : ($bindingValue[1] + 3600 * 24 - 1);
              break;

            default: // to
              $condition .= '<= ' . ($isDateType ? "CAST({$bindingKey} AS DATE)" : $bindingKey);

              $bindings[$bindingKey] = $isDateType ? $bindingValue[0] : ($bindingValue[0] + 3600 * 24 - 1);
              break;
          }

          $conditions[] = $condition;
          break;
      }
    }

    if (!empty($conditions))
    {
      if (!empty($options['AND']))
      {
        $queryConditions = array_slice($conditions, $options['AND']);
        $conditions = array_slice($conditions, 0, $options['AND']);
      }

      $query .= "\n\tAND (" . implode(' ' . ((int)$options['f']['j'] === 0 ? 'AND' : 'OR') . ' ', $conditions) . ')';

      if (!empty($queryConditions))
      {
        $query .= "\n\tAND (" . implode('OR ', $queryConditions) . ')';
      }
    }
  }

  $countSql = sprintf($query, 'COUNT(*) AS count');

  if (!empty($options['o']))
  {
    $orderBy = array();

    foreach ($options['o']['f'] as $k => $field)
    {
      switch ($field)
      {
        case 'id':
        case 'subject':
        case 'priority':
        case 'type':
        case 'kind':
        case 'createdAt':
        case 'updatedAt':
        case 'expectedFinishAt':
        case 'percent':
          $field = 'i.' . $field;
          break;

        case 'owner':
          $field = 'ownerName';
          break;

        case 'creator':
          $field = 'creatorName';
          break;

        default: continue;
      }

      $orderBy[] = $field . ' ' . ($options['o']['d'][$k] == 1 ? 'ASC' : 'DESC');
    }

    $query .= "\nORDER BY\n\t" . implode(",\n\t", $orderBy);
  }

  $fetchSql = sprintf($query, "\n\ti.id,\n\t" . implode(",\n\t", $columns));

  return array(
    'countSql' => $countSql,
    'fetchSql' => $fetchSql,
    'bindings' => $bindings,
  );
}

function fetch_issues_grid($queries, PagedData $issues)
{
  $rowCount = fetch_one($queries['countSql'], $queries['bindings'])->count;
  $rows = array();

  if ($rowCount != 0)
  {
    $rows = fetch_all($queries['fetchSql'] . "\nLIMIT {$issues->getOffset()}, {$issues->getPerPage()}",
                      $queries['bindings']);
  }

  $issues->fill($rowCount, $rows);

  return $issues;
}


function construct_issues_grid_row(array $columns, $issue)
{
  global $types, $kinds, $priorities, $statuses;

  $docsViewerSuffix = isset($_GET['docs']) ? '&amp;docs=1' : '';
  $row = array();

  foreach ($columns as $column) switch ($column)
  {
    case 'type':
      $row[] = isset($types[$issue->type]) ? $types[$issue->type] : '-';
      break;

    case 'kind':
      $row[] = isset($kinds[$issue->kind]) ? $kinds[$issue->kind] : '-';
      break;

    case 'priority':
      $row[] = isset($priorities[$issue->priority]) ? $priorities[$issue->priority] : '-';
      break;

    case 'subject':
      $row[] = array('<a href="' . url_for("service/view.php?id={$issue->id}{$docsViewerSuffix}") . '">' . e($issue->subject) . '</a>', 'class' => 'subject clickable');
      break;

    case 'status':
      $td = array($statuses[$issue->status]);

      if (is_resolved_issue_status($issue->status))
      {
        $td['class'] = 'resolved';
      }
      elseif (!empty($issue->expectedFinishAt))
      {
        $finishAt = new DateTime($issue->expectedFinishAt);
        $createdAt = new DateTime('@' . $issue->createdAt);
        $now = new DateTime();

        $minutesFromNow = date_interval_to_minutes($finishAt->diff($now));
        $minutesFromCreation = date_interval_to_minutes($finishAt->diff($createdAt));

        $percentTillFinish = 100 - (($minutesFromNow * 100) / $minutesFromCreation);

        if ($percentTillFinish < 0)
        {
          $percentTillFinish = 100;
        }

        $td['class'] = 'over100';
        $td['title'] = '';

        $boundries = array(
          10 => 'lt10',
          25 => 'lt25',
          50 => 'lt50',
          75 => 'lt75',
          100 => 'lt100'
        );

        foreach ($boundries as $boundry => $class)
        {
          if ($percentTillFinish < $boundry)
          {
            $td['class'] = $class;
            $td['title'] = trim(minutes_to_text($minutesFromNow));

            break;
          }
        }
      }

      $row[] = $td;
      break;

    case 'percent':
      if ($issue->allTasks)
      {
        $row[] = array(number_format($issue->percent) . '%',
                       'title' => "Ukończono {$issue->completedTasks} z {$issue->allTasks} zadań.",);
      }
      else
      {
        $row[] = '-';
      }
      break;

    case 'owner':
      $row[] = $issue->owner ? $issue->ownerName : '-';
      break;

    case 'creator':
      $row[] = $issue->creator ? $issue->creatorName : '-';
      break;

    case 'createdAt':
    case 'updatedAt':
      $row[] = date('Y-m-d, H:i', $issue->$column);
      break;

    case 'relatedProduct':
      if ($issue->relatedProduct)
      {
        $row[] = '<a href="/catalog/?product=' . $issue->relatedProduct . '">' . e($issue->relatedProductName) . '</a>';
      }
      else
      {
        $row[] = '-';
      }
      break;

    default:
      $row[] = isset($issue->$column) ? e($issue->$column) : '-';
      break;
  }

  return $row;
}

if (isset($_GET['v']))
{
  $options = fetch_issues_grid_options($_GET['v']);
}
else
{
  $defaultView = fetch_one("SELECT view FROM grid_view_defaults WHERE grid='service/issues' AND user=? LIMIT 1",
                           array(1 => $_SESSION['user']->getId()));

  if (!empty($defaultView->view))
    go_to($baseUrl . '&v=' . $defaultView->view);

  $options = fetch_issues_grid_options();
}

if (empty($options['@']))
{
  $options['@'] = construct_issues_grid_queries($options);
}

$options['@']['bindings'][':admin'] = $_SESSION['user']->isSuper() ? 1 : 0;
$options['@']['bindings'][':user'] = $_SESSION['user']->getId();
$options['@']['bindings'][':editor'] = is_allowed_to('service/edit') ? 1 : 0;
$options['@']['bindings'][':docsViewer'] = isset($_GET['docs']);

try
{
  $issues = fetch_issues_grid($options['@'],
                              new PagedData(empty($_GET['page']) ? 1 : ($_GET['page'] < 1 ? 1 : (int)$_GET['page']), $options['p']));
}
catch (PDOException $x)
{
  echo '<pre>';
  var_dump($options);
  echo $x;
  exit;
}

foreach ($issues as $issue)
{
  $issue->row = construct_issues_grid_row($options['c'], $issue);
}

$views = fetch_array("SELECT view AS `key`, name AS `value` FROM grid_views WHERE grid='service/issues' AND (public=1 OR creator=?) ORDER BY name",
                     array(1 => $_SESSION['user']->getId()));

$v = empty($_GET['v']) ? null : $_GET['v'];

$view = http_build_query(array('v' => $v));
$currentView = is_valid_view_id($v) ? $v : null;

unset($v);

?>

<? if (!$related): ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if (is_allowed_to('service/add')): ?>
  <li><a href="<?= url_for('service/add.php') ?>">Dodaj nowe zgłoszenie</a>
  <? endif ?>
  <li><a href="<?= url_for('service/filter.php') ?>">Zarządzaj widokami</a>
  <? if (is_allowed_to('service/templates*')): ?>
  <li><a href="<?= url_for('service/templates/') ?>">Zarządzaj szablonami zadań</a>
  <? endif ?>
</ul>
<? append_slot() ?>
<? endif ?>

<? begin_slot('head') ?>
<style>
  #grid .block-body { overflow: auto; }
  #issues td,
  #issues th
  {
    white-space: nowrap;
    padding-right: 0.5em;
  }
  #issues td
  {
    padding: 0.5em 0.25em;
  }
  #issues td.subject
  {
    white-space: normal!important;
  }
  #issues a
  {
    text-decoration: none;
  }
  .resolved
  {
    background: #BFFFBF;
  }
  .over100
  {
    color: #FFF;
    background: #F00;
  }
  .lt100
  {
    color: #FFF;
    background: #FF4040;
  }
  .lt75
  {
    color: #FFF;
    background: #FF7F7F;
  }
  .lt50
  {
    background: #FFBFBF;
  }
  .lt25
  {
    background: #FEE5E5;
  }
  .lt10
  {
    background: transparent;
  }
  tr:hover .over100, tr:hover .lt100, tr:hover .lt75 { color: #FFF; }
  #view { font-size: 1em; }
  #query {
    display: inline-block;
    margin: 0;
    white-space: nowrap;
    position: relative;
  }
  #query input { width: 200px; }
  #queryHelp {
    position: absolute;
    top: 24px;
    left: 0;
    width: 380px;
    background: #FFF;
    box-shadow: 0 1px 1px #909090;
    white-space: normal;
    padding: .5em;
    box-sizing: border-box;
    font-size: .9em;
  }
  #queryHelp ul {
    margin-top: -.5em;
  }
  #queryHelp li {
    display: list-item;
    list-style: square;
    list-style-position: inside;
    padding-left: 0;
    width: auto;
    white-space: normal;
  }
  #queryHelp li > * {
    display: inline;
    margin-left: 0;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#issues').makeClickable();

  $('#view').change(function()
  {
    window.location.href = '<?= $baseUrl ?>' + (this.value != 0 ? '&v=' + this.value : '');
  });

  <? if ($related): ?>
  $('.actions a').click(function() {
    var tr = $(this).closest('tr');
    $.ajax({
      url: this.href,
      success: function() {
        tr.fadeOut(function() { tr.remove(); });
      },
      error: function() {
        tr.fadeOut(function() { tr.fadeIn(); });
      }
    });
    return false;
  });
  <? endif ?>

  $('#toggleQueryHelp').click(function()
  {
    $('#queryHelp').toggle();

    if ($('#queryHelp').is(':visible'))
    {
      $('#query').find('input[name="q"]').focus();
    }

    return false;
  }).click();
});
</script>
<? append_slot() ?>

<? decorate("Serwis") ?>

<div class="block" id="grid">
  <ul class="block-header">
    <li>
      <h1 class="block-name">
        <? if ($related): ?>
        Dodawanie powiązanych zgłoszeń do &lt;<?= e($related->subject) ?>&gt;
        <? else: ?>
        Zgłoszenia
        <? endif ?>
      </h1>
    </li>
    <li>
      <? if (empty($_GET['v']) || is_string($_GET['v'])): ?>
      <form id="query" action="<?= url_for("/service/") ?>">
        <input type="hidden" name="v" value="<?= e(isset($_GET['v']) ? $_GET['v'] : '') ?>">
        <? if ($related): ?>
        <input type="hidden" name="relate" value="<?= $related->id ?>">
        <? endif ?>
        <?= fff('OCB?', 'help', '/service/#queryHelp', 'toggleQueryHelp') ?>
        <input type="text" name="q" value="<?= e(isset($_GET['q']) ? $_GET['q'] : '') ?>">
        <div id="queryHelp">
          <p>
            <code>{FIELD}={VALUE}</code>
            <br>
            Pole <code>{FIELD}</code> równa się <code>{VALUE}</code>.
            Np. <code>nr=123456789</code>
          </p>
          <p>
            <code>{FIELD}:{VALUE}</code>
            <br>
            <code>{VALUE}</code> zawarte jest w polu <code>{FIELD}</code>.
            Np. <code>nr:1234</code>
          </p>
          <p>
            <code>{VALUE}</code>
            <br>
            <code>{VALUE}</code> zawarte jest w polu nr zamówienia (<code>nr</code>).
            Np. <code>1234</code>
          </p>
          <p>Dostępne pola {FIELD}:</p>
          <ul>
            <li><code>id</code> - ID</li>
            <li><code>subject</code> - Temat</li>
            <li><code>status</code> - Status<br>0=Nowe, 1=Zaakceptowane, 2=Rozpoczęte, 3=Odrzucone, 4=Rozwiązane, 5=Wznowione, 6=Przekazane, 7=Dokumenty</li>
            <li><code>priority</code> - Priorytet<br>1=Wysoki, 2=Normalny, 3=Niski</li>
            <li><code>type</code> - Typ<br>1=Awaria, 2=Zmiana, 3=Prewencja, 4=Zamówienie</li>
            <li><code>kind</code> - Rodzaj<br>1=Elektryczne, 2=Mechaniczne, 3=Inne</li>
            <li><code>creator</code> - Zgłaszający</li>
            <li><code>owner</code> - Właściciel</li>
            <li><code>nr</code> lub <code>orderNumber</code> - Numer zamówienia</li>
            <li><code>invoice</code> lub <code>orderInvoice</code> - Numer faktury</li>
          </ul>
          <p>W przypadku podania kilku warunków, dowolny z nich musi być spełniony (OR).</p>
        </div>
      </form>
    </li>
    <li>
      <? endif ?>
      <select id="view">
        <option value="standard">Standardowy widok
        <?= render_options($views, $currentView) ?>
      </select>
    </li>
  </ul>
  <div class="block-body">
    <? if (!$issues->isEmpty()): ?>
    <table id="issues">
      <thead>
        <tr>
          <? foreach ($options['c'] as $column): ?>
          <th><?= $columnNames[$column] ?>
          <? endforeach ?>
          <? if ($related): ?>
          <th>Akcje
          <? endif ?>
      </thead>
      <tfoot>
        <tr>
          <td colspan="99" class="table-options">
            <?= $issues->render($baseUrl . '&' . $view) ?>
        </tr>
      </tfoot>
      <tbody>
        <? foreach ($issues as $issue): ?>
        <tr class="issue" data-id="<?= $issue->id ?>">
          <?= render_grid_row($issue->row) ?>
          <? if ($related): ?>
          <td class="actions">
            <ul>
              <li><?= fff('Dodaj powiązanie jednostronne', 'link_add', "service/links/add.php?issue1={$related->id}&issue2={$issue->id}") ?>
              <li><?= fff('Dodaj powiązanie obustronne', 'link_add_dual', "service/links/add.php?dual=1&issue1={$related->id}&issue2={$issue->id}") ?>
            </ul>
          <? endif ?>
        <? endforeach ?>
      </tbody>
    </table>
    <? else: ?>
    <p>Nie znaleziono żadnych zgłoszeń dla zadanych kryteriów.</p>
    <? endif ?>
  </div>
</div>
