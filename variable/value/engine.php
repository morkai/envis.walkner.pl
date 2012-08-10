<?php

include '../../_common.php';

if (empty($_GET['machine']) || empty($_GET['id']) || empty($_GET['variable'])) bad_request();

no_access_if_not_allowed('variable/value');

$engine = fetch_one('SELECT `name`, machine FROM `engines` WHERE `id`=? AND machine=?', array(1 => $_GET['id'], $_GET['machine']));

if (empty($engine)) not_found();

no_access_if_not(has_access_to_machine($engine->machine));

$variable = fetch_one('SELECT `name` FROM `variables` WHERE `id`=?', array(1 => $_GET['variable']));

if (empty($variable))
{
	not_found();
}

include '../../_lib_/PagedData.php';

$page    = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$perPage = 15;

$values = new PagedData($page, $perPage);

$bindings = array(1 => $_GET['machine'], $_GET['id'], $_GET['variable']);

$totalItems = fetch_one('SELECT COUNT(*) AS `count` FROM `values` WHERE machine=? AND `engine`=? AND `variable`=?', $bindings)->count;

$query  = 'SELECT `value`, `createdAt` FROM `values` WHERE machine=? AND `engine`=? AND `variable`=? ORDER BY `createdAt` DESC';

$items = fetch_all($q = sprintf("%s LIMIT %s,%s", $query, $values->getOffset(), $values->getPerPage()), $bindings);

$values->fill($totalItems, $items);

?>

<? decorate("Lista wartości zmiennej danego urządzenia") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Wartość &lt;<?= $variable->name ?>&gt; urządzenia &lt;<?= $engine->name ?>&gt;</h1>
	</div>
	<div class="block-body">
		<table>
			<thead>
				<tr>
					<th>Czas wystąpienia</th>
					<th>Wartość</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2" class="table-options">
						<?= $values->render(url_for('variable/value/engine.php?machine=' . $_GET['machine'] . '&amp;id=' . $_GET['id'] . '&amp;variable=' . $_GET['variable'])) ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<? foreach ($values as $value): ?>
				<tr>
					<td><?= $value->createdAt ?></td>
					<td><?= ($v = round($value->value, 2)) == 0 ? '-' : $v ?></td>
				</tr>
			<? endforeach ?>
			</tbody>
		</table>
	</div>
</div>