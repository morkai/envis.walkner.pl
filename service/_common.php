<?php

include_once __DIR__ . '/../_common.php';

$statuses = array(0 => 'Nowe',
                  1 => 'Zaakceptowane',
                  2 => 'Rozpoczęte',
                  6 => 'Przekazane',
                  7 => 'Dokumenty',
                  3 => 'Odrzucone',
                  4 => 'Rozwiązane',
                  5 => 'Wznowione',);

function is_resolved_issue_status($status)
{
  return $status == 3 || $status == 4;
}

$priorities = array(1 => 'Wysoki', 'Normalny', 'Niski');

$kinds = array(1 => 'Elektryczne', 'Mechaniczne', 'Inne');

$types = array(1 => 'Awaria', 'Zmiana', 'Prewencja', 'Zamówienie');

define('ISSUE_TYPE_ORDER', 4);

$columnNames = array(
  'id' => 'ID',
  'subject' => 'Temat',
  'status' => 'Status',
  'percent' => '%',
  'priority' => 'Priorytet',
  'type' => 'Typ',
  'kind' => 'Rodzaj',
  'creator' => 'Zgłaszający',
  'owner' => 'Właściciel',
  'createdAt' => 'Czas zgłoszenia',
  'updatedAt' => 'Czas ostatniej zmiany',
  'expectedFinishAt' => 'Przew. data zak.',
  'orderNumber' => 'Numer zamówienia',
  'orderDate' => 'Data zamówienia',
  'orderInvoice' => 'Numer faktury',
  'orderInvoiceDate' => 'Data faktury',
  'relatedProduct' => 'Powiązany produkt'
);

function send_assign_email($receivers, $subject, $issue)
{
  $url = url_for('service/view.php?id=' . $issue, true);
  $creator = $_SESSION['user']->getName();
  $message = <<<MSG
Witaj!

{$creator} przypisał Cię do zlecenia '{$subject}' i prosił abyś został o tym poinformowany.

Możesz je szybko wyświetlić klikając poniższy odnośnik:
{$url}

Pozdrawiam, envis.

--

Ta wiadomość została wygenerowana automatycznie.
MSG;

  send_email($receivers, 'Nowe zlecenie', $message);
}

function send_issue_removal_email($issue)
{
  $subscribers = get_issue_subscribers($issue->id, false);

  $subject = 'Usunięto zgłoszenie: ' . $issue->subject;

  foreach ($subscribers as $subscriber)
  {
    if ($subscriber == $_SESSION['user']->getId()) continue;

    $message = <<<MSG
Witaj, {$subscriber->name}!

{$_SESSION['user']->getName()} usunął zgłoszenie, które obserwowałeś:
{$issue->subject}

--

Ta wiadomość została wygenerowana automatycznie.
MSG;

    send_email($subscriber->email, $subject, $message);
  }
}

function get_issue_subscribers($issue, $excludeRecentlyNotified = true)
{
  $query = <<<SQL
SELECT u.id, u.name, u.email
FROM issue_subscribers s
INNER JOIN users u ON u.id=s.user
WHERE issue=:issue
  AND (0=:exclude OR (1=:exclude AND recentlyNotifiedAt < UNIX_TIMESTAMP() - 1800))
SQL;

  $bindings = array(':issue' => $issue, ':exclude' => $excludeRecentlyNotified ? 1 : 0);

  return fetch_all($query, $bindings);
}

function update_issue_completion_percent($issue)
{
  $query = <<<SQL
UPDATE issues
SET percent =
    (
        (SELECT COUNT(*) FROM issue_tasks WHERE issue=:issue AND completed=1)
        /
        (SELECT COUNT(*) FROM issue_tasks WHERE issue=:issue)
    ) * 100
WHERE id=:issue
SQL;

  exec_stmt($query, array(':issue' => $issue));
}

function fetch_issues_grid_options($view = null)
{
  if (func_num_args() === 0)
    $view = isset($_GET['v']) ? $_GET['v'] : null;

  $defaultOptions = array(
    'n' => '', // name
    'p' => 20, // per page
    'c' => array('subject', 'status', 'percent', 'priority', 'type',), // columns
    'f' => array(              // filters
      'j' => 0,                // 0=AND or 1=OR
      'i' => array(),          // additional info
      'c' => array(),          // columns
      'v' => array(),          // values
    ),
    'o' => array(                     // order
      'f' => array('updatedAt',),      // fields
      'd' => array(-1,         ),      // directions
    ),
  );

  if (empty($view))
  {
    $options = fetch_grid_options('service/issues', 'default') + $defaultOptions;
  }
  elseif (is_array($view))
  {
    $options = $view + $defaultOptions;

    if (empty($options['f']['c']))
    {
      $options['f'] = $defaultOptions['f'];
    }
    else
    {
      $options['f']['c'] = array_filter($options['f']['c'], function($column) { return !empty($column); });
    }

    if ($options['p'] > 50)
    {
      $options['p'] = 50;
    }
    elseif ($options['p'] < 5)
    {
      $options['p'] = 5;
    }
  }
  else
  {
    $options = fetch_grid_options('service/issues', $view) + $defaultOptions;
  }

  $options['n'] = trim($options['n']);

  apply_issue_query($options);

  return $options;
}

function apply_issue_query(&$options)
{
  static $aliasToColumn = array('nr' => 'orderNumber', 'invoice' => 'orderInvoice');
  global $columnNames;

  if (empty($_GET['q']))
  {
    return;
  }

  $parts = preg_split('/(:|=)/', $_GET['q'], -1, PREG_SPLIT_DELIM_CAPTURE);
  $partsCount = count($parts);

  $k = count($options['f']['c']);

  $options['AND'] = $k;

  if ($partsCount === 1)
  {
    $options['f']['c'][$k] = 'orderNumber';
    $options['f']['i'][$k] = 'contains';
    $options['f']['v'][$k] = $_GET['q'];

    if (!in_array($options['f']['c'][$k], $options['c']))
    {
      $options['c'][] = $options['f']['c'][$k];
    }
  }
  else
  {
    $j = 0;

    while ($j < $partsCount)
    {
      $c = $parts[$j++];

      if (isset($aliasToColumn[$c]))
      {
        $c = $aliasToColumn[$c];
      }

      $i = $parts[$j++] === '=' ? 'equals' : 'contains';

      if ($j + 1 === $partsCount)
      {
        $v = $parts[$j++];
      }
      else
      {
        $pos = strrpos($parts[$j], ' ');
        $v = substr($parts[$j], 0, $pos);
        $parts[$j] = substr($parts[$j], $pos + 1);
      }

      if (isset($columnNames[$c]))
      {
        $options['f']['c'][$k] = $c;
        $options['f']['i'][$k] = $i;
        $options['f']['v'][$k] = $v;

        if (!in_array($c, $options['c']))
        {
          $options['c'][] = $c;
        }
      }
    }
  }
}

function prepare_issue_changes($change)
{
  global $statuses, $priorities, $kinds, $types, $columnNames;

  $columnNames += array('description' => 'Opis', 'relatedObject' => 'Powiązany obiekt');

  if (!isset($columnNames[$change['field']]))
    return $change;

  switch ($change['field'])
  {
    case 'status':
      $change['old'] = $statuses[$change['old']];
      $change['new'] = $statuses[$change['new']];
      break;

    case 'priority':
      $change['old'] = $priorities[$change['old']];
      $change['new'] = $priorities[$change['new']];
      break;

    case 'kind':
      $change['old'] = $kinds[$change['old']];
      $change['new'] = $kinds[$change['new']];
      break;

    case 'type':
      $change['old'] = $types[$change['old']];
      $change['new'] = $types[$change['new']];
      break;

    case 'relatedObject':
      $change['old'] = implode(' \ ', array_filter($change['old']));
      $change['new'] = implode(' \ ', array_filter($change['new']));
      break;

    case 'subject':
    case 'description':
      foreach (array('old', 'new') as $k)
        if (strlen($change[$k]) > 25)
        {
          $spacePos = strpos($change[$k], ' ', 25);

          $change[$k] = substr($change[$k], 0, $spacePos ?: 25) . '...';
        }
      break;
  }

  $change['field'] = $columnNames[$change['field']];

  return $change;
}

function record_issue_change($issue, $system, $comment, array $changes = array(), array $tasks = array(), $parent = null)
{
  $creator = $_SESSION['user']->getId();

  $bindings = array(
    'issue' => (int)$issue,
    'parent' => $parent,
    'system' => $system ? 1 : 0,
    'comment' => $comment,
    'changes' => empty($changes) ? null : serialize($changes),
    'tasks' => empty($tasks) ? null : serialize($tasks),
    'createdAt' => time(),
    'createdBy' => $creator,
  );

  exec_insert('issue_history', $bindings);

  $id = get_conn()->lastInsertId();

  exec_update('issues', array('updatedAt' => $bindings['createdAt']), 'id=' . $bindings['issue']);

  $subscribers = get_issue_subscribers($issue);

  if (!empty($subscribers))
  {
    $issue = fetch_one('SELECT id, subject FROM issues WHERE id=? LIMIT 1', array(1 => $issue));
    $creatorName = $_SESSION['user']->getName();

    $issueUrl = url_for('service/view.php?id=' . $issue->id, true);
    $unsubscribeUrl = url_for('service/update.php?issue=' . $issue->id . '&what=subscription', true);

    $changedProperties = '';
    $completedTasks = '';
    $creatorComment = empty($comment) ? '' : trim(strip_tags($comment)) . "\n\n";

    if (!empty($changes))
    {
      $changedProperties = "Zmienione właściwości:";

      foreach (array_map('prepare_issue_changes', $changes) as $change)
      {
        $changedProperties .= "\n * {$change['field']} ";

        if (empty($change['old']))
          $changedProperties .= 'ustawiono na ';
        else
          $changedProperties .= "zmieniono z [{$change['old']}] na ";

        $changedProperties .= "[{$change['new']}]";
      }

      $changedProperties .= "\n\n";
    }

    if (!empty($tasks))
    {
      $completedTasks = 'Ukończone zadania:';

      foreach ($tasks as $task)
      {
        $completedTasks .= "\n * {$task}";
      }

      $completedTasks .= "\n\n";
    }

    $subject = 'Zmiana zgłoszenia: ' . $issue->subject;

    foreach ($subscribers as $subscriber)
    {
      if ($subscriber->id == $creator) continue;

      $message = <<<MSG
Witaj, {$subscriber->name}!

{$creatorName} dokonał zmian w obserwowanym przez Ciebie zgłoszeniu:
{$issue->subject}
dostępnym pod adresem
{$issueUrl}

--

{$creatorComment}{$changedProperties}{$completedTasks}--

Ta wiadomość została wygenerowana automatycznie na Twoje życzenie.
Użyj poniższego odnośnika, jeżeli nie chcesz być informowany o zmianach w tym zgłoszeniu:
{$unsubscribeUrl}
MSG;

      send_email($subscriber->email, $subject, $message);
    }

    exec_update('issue_subscribers', array('recentlyNotifiedAt' => time()), 'issue=' . $issue->id);
  }

  return $id;
}

/**
 * < 60             przed chwilą
 * < 60*2           minutę temu
 * < 3600           x minut temu
 * < 3600*2         godzinę temu
 * < 3600*24        x godzin temu
 * < 3600*24*2      wczoraj
 * < 3600*24*30     x dni temu
 * < 3600*24*30*2   miesiąc temu
 * < 3600*24*30*12  x miesięcy temu
 * < INF            ponad rok temu
 */
function format_time_ago($time)
{
  $diff = time() - $time;

  if ($diff < 60) return 'przed chwilą';

  if ($diff < 120) return 'minutę temu';

  if ($diff < 3600)
  {
    $minutes = (int)($diff / 60);

    if ($minutes < 5) return $minutes . ' minuty temu';

    return $minutes . ' minut temu';
  }

  if ($diff < 7200) return 'godzinę temu';

  if ($diff < 86400)
  {
    $hours = (int)($diff / 3600);

    if ($hours < 5) return $hours . ' godziny temu';

    return $hours . ' godzin temu';
  }

  if ($diff < 172800) return 'wczoraj';

  if ($diff < 2592000)
  {
    return ((int)($diff / 86400)) . ' dni temu';
  }

  if ($diff < 5184000) return 'miesiąc temu';

  if ($diff < 31104000)
  {
    $months = (int)($diff / 5184000);

    if ($months === 1) return 'miesiąc temu';

    if ($months < 5) return $months . ' miesiące temu';

    return $months . ' miesięcy temu';
  }

  return 'ponad rok temu';
}

function is_valid_view_id($id)
{
  return is_string($id) && preg_match('/^[A-Za-z0-9]{32}$/', $id);
}

function is_issue_participant($user, $issue)
{
  return $issue->owner == $user->getId()
         || $issue->creator == $user->getId()
         || fetch_one('SELECT 1 FROM issue_assignees WHERE issue=? AND assignee=?', array(1 => isset($issue->issue) ? $issue->issue : $issue->id, $user->getId()));
}

function is_issue_docs_viewer($user, $issue)
{
  if (!isset($_GET['docs']))
  {
    return false;
  }

  if ($issue->relatedFactory && !$user->hasAccessToFactory($issue->relatedFactory))
  {
    return false;
  }

  if ($issue->relatedMachine && !$user->hasAccessToMachine($issue->relatedMachine))
  {
    return false;
  }

  return true;
}
