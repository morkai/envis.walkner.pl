<?php

include_once __DIR__ . '/../_common.php';

include_once __DIR__ . '/../_lib_/PagedData.php';

$canViewOthers = is_allowed_to('global_activity');

$bindings = array();
$where = 'WHERE 1=1';

$user = '';
$action = '';

if (!empty($_GET['user']) && $canViewOthers)
{
  $user = escape($_GET['user']);
  $where .= ' AND u.name LIKE "' . str_replace('*', '%', addslashes($user)) . '"';
}

if (!$canViewOthers)
{
  $bindings[':id'] = $_SESSION['user']->getId();

  $where .= ' AND u.id=:id';
}

if (!empty($_GET['action']))
{
  $action = escape($_GET['action']);
  $where .= ' AND l.message LIKE "' . str_replace('*', '%', addslashes($action)) . '"';
}

$page = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$perPage = 15;

$logs = new PagedData($page, $perPage);

$totalItems = fetch_one('SELECT COUNT(*) AS `count` FROM logs l LEFT JOIN users u ON u.id=l.user ' . $where, $bindings)->count;

$query = 'SELECT l.message, l.time, u.name AS logger FROM logs l LEFT JOIN users u ON u.id=l.user ' . $where . ' ORDER BY l.time DESC, l.id DESC';

$items = fetch_all(sprintf("%s LIMIT %s,%s", $query, $logs->getOffset(), $logs->getPerPage()), $bindings);

$logs->fill($totalItems, $items);

$hasAnyLogs = $totalItems > 0;

?>

<? decorate("Lista logów") ?>

<div class="yui-ge">
  <div class="yui-u first">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Logi</h1>
      </div>
      <div class="block-body">
      <? if ($hasAnyLogs): ?>
        <table>
          <thead>
            <tr>
              <th>Czas</th>
              <? if ($canViewOthers): ?>
              <th>Użytkownik</th>
              <? endif ?>
              <th>Akcja</th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <td colspan="5" class="table-options">
                <?= $logs->render(url_for('user/logs.php?user=' . $user . '&action=' . $action)) ?>
              </td>
            </tr>
          </tfoot>
          <tbody id="logs">
          <? foreach ($logs as $log): ?>
            <tr>
              <td><?= date('Y-m-d H:i:s', strtotime($log->time . ' GMT')) ?></td>
              <? if ($canViewOthers): ?>
              <td><?= $log->logger ?></td>
              <? endif ?>
              <td><?= escape($log->message) ?></td>
            </tr>
          <? endforeach ?>
          </tbody>
        </table>
      <? else: ?>
        <p>Aktualnie nie ma żadnych logów.</p>
      <? endif ?>
      </div>
    </div>
  </div>
  <div class="yui-u">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">Filtry</h1>
      </div>
      <div class="block-body">
        <form method="get" action="<?= url_for('user/logs.php') ?>">
          <fieldset>
            <legend>Filtry</legend>
            <ol class="form-fields">
              <? if ($canViewOthers): ?>
              <li>
                <label for="logs-filter-user">Użytkownik</label>
                <input id="logs-filter-user" name="user" type="text" value="<?= $user ?>">
              <? endif ?>
              <li>
                <label for="logs-filter-action">Akcja</label>
                <input id="logs-filter-action" name="action" type="text" value="<?= $action ?>">
              <li>
                <ol class="form-actions">
                  <li><input type="submit" value="Filtruj">
                  <li><a href="<?= url_for('user/logs.php') ?>">Wyczyść filtry</a>
                </ol>
            </ol>
          </fieldset>
        </form>
      </div>
  </div>
</div>
