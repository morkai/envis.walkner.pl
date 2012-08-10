<?php

include './_common.php';

if (empty($_POST['id']) || empty($_POST['ref']) || empty($_POST['type']) || !in_array($_POST['type'], array('inside', 'after', 'before'), true)) bad_request();

no_access_if_not_allowed('help*');

$id    = (int)$_POST['id'];
$refId = (int)$_POST['ref'];
$type  = $_POST['type'];

$conn = get_conn();

try
{
	$conn->beginTransaction();

	$rows = fetch_all('SELECT id, parent, position FROM help WHERE id=:id OR id=:ref', array(':id' => $id, ':ref' => $refId));

	$item = null;
	$ref  = null;

	foreach ($rows as $row)
	{
		if ($row->id == $id)
		{
			$item = $row;
		}
		elseif ($row->id == $refId)
		{
			$ref = $row;
		}
	}

	if (empty($item)) output_json('item_not_found');
	if (empty($ref))  output_json('ref_not_found');

	unset($rows, $id, $refId);

	switch ($type)
	{
		case 'inside':
		{
			$last = fetch_one('SELECT MAX(position)+1 AS position FROM help WHERE parent=:ref', array(':ref' => $ref->id));
			
			$position = empty($last) || empty($last->position) ? 1 : (int)$last->position;

			exec_stmt(
				'UPDATE help SET parent=:parent, position=:position WHERE id=:id',
				array(':parent' => $ref->id, ':position' => $position, ':id' => $item->id)
			);

			$conn->exec(sprintf(
				'UPDATE help SET position=position-1 WHERE parent %s AND position > %d',
				help_get_parent($item->parent),
				$item->position
			));
			break;
		}

		case 'before':
		{
			if ($item->parent === $ref->parent)
			{
				$conn->exec(sprintf(
					'UPDATE help SET position=position+1 WHERE parent %s AND position >= %d AND position < %d',
					help_get_parent($item->parent), $ref->position, $item->position
				));

				exec_stmt('UPDATE help SET position=:position WHERE id=:id', array(':position' => $ref->position, ':id' => $item->id));
			}
			else
			{
				$conn->exec(sprintf(
					'UPDATE help SET position=position-1 WHERE parent %s AND position > %d',
					help_get_parent($item->parent),
					$item->position
				));

				$conn->exec($sql = sprintf(
					'UPDATE help SET position=position+1 WHERE parent %s AND position >= %d',
					help_get_parent($ref->parent),
					$ref->position
				));
				
				exec_stmt('UPDATE help SET parent=:parent, position=:position WHERE id=:id', array(':parent' => $ref->parent, ':position' => $ref->position, ':id' => $item->id));
			}

			break;
		}

		case 'after':
		{
			if ($item->parent === $ref->parent)
			{
				if ($item->position > $ref->position)
				{
					$conn->exec(sprintf(
						'UPDATE help SET position=position+1 WHERE parent %s AND position > %d AND position < %d',
						help_get_parent($item->parent), $ref->position, $item->position
					));

					exec_stmt('UPDATE help SET position=:position WHERE id=:id', array(':position' => $ref->position + 1, ':id' => $item->id));
				}
				elseif ($item->position < $ref->position)
				{
					$conn->exec(sprintf(
						'UPDATE help SET position=position-1 WHERE parent %s AND position <= %d AND position > %d',
						help_get_parent($item->parent), $ref->position, $item->position
					));

					exec_stmt('UPDATE help SET position=:position WHERE id=:id', array(':position' => $ref->position, ':id' => $item->id));
				}
			}
			else
			{
				$conn->exec(sprintf(
					'UPDATE help SET position=position-1 WHERE parent %s AND position > %d',
					help_get_parent($item->parent),
					$item->position
				));

				$conn->exec($sql = sprintf(
					'UPDATE help SET position=position+1 WHERE parent %s AND position > %d',
					help_get_parent($ref->parent),
					$ref->position
				));

				exec_stmt('UPDATE help SET parent=:parent, position=:position WHERE id=:id', array(':parent' => $ref->parent, ':position' => $ref->position + 1, ':id' => $item->id));
			}

			break;
		}
	}

	$conn->commit();

	output_json(true);
}
catch (PDOException $x)
{
	$conn->rollBack();

	header('HTTP/1.1 400 Bad Request');
	echo $x;
	exit;
}