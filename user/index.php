<?php

include '../_common.php';

no_access_if_not_allowed('user*');

include '../_lib_/PagedData.php';

$page    = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$perPage = 15;

$users = new PagedData($page, $perPage);

$totalItems = fetch_one('SELECT COUNT(*) AS `count` FROM users')->count;

$query  = 'SELECT u.super, u.id, u.name, u.email, r.name AS role FROM users u LEFT JOIN roles r ON r.id=u.role ORDER BY u.name';

$items = fetch_all(sprintf("%s LIMIT %s,%s", $query, $users->getOffset(), $users->getPerPage()));

$users->fill($totalItems, $items);

$hasAnyUsers  = $totalItems > 0;
$canAdd       = is_allowed_to('user/add');
$canEdit      = is_allowed_to('user/edit');
$canDelete    = is_allowed_to('user/delete');
$canEditRoles = is_allowed_to('user/edit/roles');

?>
<? begin_slot('submenu') ?>
<ul id="submenu">
	<? if ($canAdd): ?><li><a href="<?= url_for("user/add.php") ?>">Dodaj nowego użytkownika</a><? endif ?>
	<li><a href="<?= url_for("user/logs.php") ?>">Pokaż logi</a>
	<? if ($canEditRoles): ?><li><a href="<?= url_for("user/role/") ?>">Zarządzaj rolami</a><? endif ?>
</ul>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('#userList').makeClickable();
});
</script>
<? append_slot() ?>

<? decorate("Lista użytkowników") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Użytkownicy</h1>
  </div>
  <div class="block-body">
  <? if ($hasAnyUsers): ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Imię i nazwisko</th>
          <th>E-mail</th>
          <th>Rola</th>
          <th>Akcje</th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <td colspan="5" class="table-options">
            <?= $users->render(url_for('user/?')) ?>
          </td>
        </tr>
      </tfoot>
      <tbody id="userList">
      <? foreach ($users as $user): ?>
        <tr>
          <td><?= $user->id ?></td>
          <td class="clickable"><a href="<?= url_for("user/view.php?id={$user->id}") ?>"><?= $user->name ?></a></td>
          <td><?= $user->email ?></td>
          <td><?= $user->super ? 'Super administrator' : $user->role ?></td>
          <td class="actions">
            <ul>
              <li><?= fff('Pokaż', 'user', 'user/view.php?id=' . $user->id) ?>
              <? if ($canEdit): ?><li><?= fff('Edytuj', 'user_edit', 'user/edit.php?id=' . $user->id) ?><? endif ?>
              <? if ($canDelete): ?><li><?= fff('Usuń', 'user_delete', 'user/delete.php?id=' . $user->id) ?><? endif ?>
            </ul>
          </td>
        </tr>
      <? endforeach ?>
      </tbody>
    </table>
  <? else: ?>
    <p>Aktualnie nie ma żadnych użytkowników.</p>
    <? if ($canAdd): ?>
    <p><a href="<?= url_for('user/add.php') ?>">Dodaj nowego użytkownika</a>
    <? endif ?>
  <? endif ?>
  </div>
</div>