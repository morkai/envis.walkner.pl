<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['id']));

if (!is_allowed_to('machine*') || !has_access_to_machine($_GET['id'])) exit;

$engines = fetch_all(
  'SELECT e.id, e.name, e.machine, m.factory FROM engines e INNER JOIN machines m ON m.id=e.machine INNER JOIN factories f ON f.id=m.factory WHERE e.machine=? ORDER BY e.name ASC',
  array(1 => $_GET['id'])
);

$canEdit = is_allowed_to('machine/device/edit');
$canDelete = is_allowed_to('machine/device/delete');

?>
<? foreach ($engines as $engine): ?>
<tr class="factory-<?= $engine->factory ?> machine-<?= $engine->machine ?> engine">
  <td><span class="engine"><?= escape($engine->name) ?></span></td>
  <td class="actions">
    <ul>
      <li><?= fff('Pokaż', 'brick', 'factory/machine/engine/?machine=' . $engine->machine . '&amp;id=' . $engine->id) ?>
      <? if ($canEdit): ?><li><?= fff('Edytuj', 'brick_edit', 'factory/machine/engine/edit.php?machine=' . $engine->machine . '&amp;id=' . $engine->id) ?><? endif ?>
      <? if ($canDelete): ?><li><?= fff('Usuń', 'brick_delete', 'factory/machine/engine/delete.php?machine=' . $engine->machine . '&amp;id=' . $engine->id) ?><? endif ?>
    </ul>
  </td>
</tr>
<? endforeach ?>
