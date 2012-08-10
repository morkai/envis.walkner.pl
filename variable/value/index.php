<?php

include '../../_common.php';

if (!isset($_GET['variable'])) bad_request();

no_access_if_not_allowed('variable/value');

$variable = fetch_one('SELECT `name` FROM `variables` WHERE `id`=?', array(1 => $_GET['variable']));

if (empty($variable)) not_found();

include '../../_lib_/PagedData.php';

$page    = !isset($_GET['page']) || ($_GET['page'] < 1) ? 1 : (int)$_GET['page'];
$perPage = 15;

$values = new PagedData($page, $perPage);

$bindings = array(1 => $_GET['variable']);

$where = ' ';

if (!$_SESSION['user']->isSuper())
{
	$where = ' AND e.machine IN(' . list_quoted($_SESSION['user']->getAllowedMachineIds()) . ') ';
}

$totalItems = fetch_one('SELECT COUNT(*) AS `count` FROM `values` v INNER JOIN `engines` e ON e.machine=v.machine AND e.`id`=v.`engine` WHERE v.`variable`=?' . $where, $bindings)->count;

$query  = 'SELECT v.`value`, v.`createdAt`, e.`name` AS `engine` FROM `values` v INNER JOIN `engines` e ON e.`id`=v.`engine` WHERE v.`variable`=?' . $where . 'ORDER BY v.`createdAt` DESC';

$items = fetch_all($q = sprintf("%s LIMIT %s,%s", $query, $values->getOffset(), $values->getPerPage()), $bindings);

$values->fill($totalItems, $items);

?>

<? decorate("Lista wartości zmiennej") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Wartość zmiennej &lt;<?= $variable->name ?>&gt;</h1>
	</div>
	<div class="block-body">
		<table>
			<thead>
				<tr>
					<th>Czas wystąpienia</th>
					<th>Wartość</th>
					<th>Urządzenie</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3" class="table-options">
						<?= $values->render(url_for('variable/value/?variable=' . $_GET['variable'])) ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<? foreach ($values as $value): ?>
				<tr>
					<td><?= $value->createdAt ?></td>
					<td><?= ($v = round($value->value, 2)) == 0 ? '-' : $v ?></td>
					<td><?= $value->engine ?></td>
				</tr>
			<? endforeach ?>
			</tbody>
		</table>
	</div>
</div>