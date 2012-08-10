<?php

include '../_common.php';

no_access_if_not_allowed('factory*');

$where = '';

if (!$_SESSION['user']->isSuper())
{
	$where = 'WHERE id IN(' . implode(', ', $_SESSION['user']->getAllowedFactoryIds()) . ')';
}

$factories = fetch_all('SELECT id, name FROM factories ' . $where . ' ORDER BY name ASC');

$hasAnyFactories = !empty($factories);

$canEdit   = is_allowed_to('factory/edit');
$canDelete = is_allowed_to('factory/delete');

$renderFactoriesTable = function($factories) use($canEdit, $canDelete)
{
?>
<table>
  <thead>
    <tr>
      <th>Nazwa</th>
      <th>Akcje</th>
    </tr>
  </thead>
  <tbody class="domain">
  <? foreach ($factories as $factory): ?>
    <tr>
      <td><a class="factory" href="<?= url_for('factory/fetch.php?id=' . $factory->id) ?>" data-id="<?= $factory->id ?>"><?= $factory->name ?></a></td>
      <td class="actions">
        <ul>
          <li><?= fff('Pokaż', 'building', 'factory/view.php?id=' . $factory->id) ?>
          <? if ($canEdit): ?><li><?= fff('Edytuj', 'building_edit', 'factory/edit.php?id=' . $factory->id) ?><? endif ?>
          <? if ($canDelete): ?><li><?= fff('Usuń', 'building_delete', 'factory/delete.php?id=' . $factory->id) ?><? endif ?>
        </ul>
      </td>
    </tr>
  <? endforeach ?>
  </tbody>
</table>
<?php
};

$factoriesTables = array(array(), array(), array());

foreach ($factories as $i => $factory)
{
  $factoriesTables[$i % 3][] = $factory;
}

?>

<? begin_slot('submenu') ?>
<ul id="submenu">
	<? if (is_allowed_to('variable*')): ?><li><a href="<?= url_for("variable") ?>">Zarządzaj zmiennymi</a><? endif ?>
</ul>
<? append_slot() ?>

<? begin_slot('head') ?>
<style>
.domain .factory {}
.domain .machine { margin-left: 2em; }
.domain .engine { margin-left: 4em; }
</style>
<? append_slot() ?>

<? decorate("Lista fabryk") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Fabryki</h1>
	</div>
	<div class="block-body">
		<? if ($hasAnyFactories): ?>
		<div class="yui-gb">
		  <div class="yui-u first">
		    <? $renderFactoriesTable($factoriesTables[0]) ?>
		  </div>
      <div class="yui-u">
        <? $renderFactoriesTable($factoriesTables[1]) ?>
      </div>
      <div class="yui-u">
        <? $renderFactoriesTable($factoriesTables[2]) ?>
      </div>
		</div>
		<? else: ?>
		<p>Aktualnie nie ma żadnych fabryk.</p>
		<? endif ?>
	</div>
</div>
<? begin_slot('js') ?>
<script>
	var cache = new Array();

	$(document).ready(function()
	{
		$('.domain a.factory').each(prepareDomainLinks);
	});

	function prepareDomainLinks()
	{
		var a = $(this);

		a.click(function()
		{
			var href = a.attr('href');
			var id   = a.attr('data-id');
			
			if (cache[href] == undefined)
			{
				cache[href] = true;

				$('body').css('cursor', 'wait');
				
				$.get(href, function(code)
				{
					$('body').css('cursor', 'default');
					
					if (code.match(/^\s*$/))
					{
						a.replaceWith('<span class="' + a.attr('class') + '">' + a.text() + '</span>');
					}
					else
					{
						a.parent().parent().after(code);

						$('.domain tr.factory-' + id + '.machine a.machine').each(prepareDomainLinks);
					}
				});
			}
			else if (a.hasClass('factory'))
			{
				toggleMachines(id);
			}
			else if (a.hasClass('machine'))
			{
				toggleEngines(id);
			}

			return false;
		});
	}

	function toggleMachines(factory)
	{
		$('.domain tr.factory-' + factory).toggle();
		$('.domain tr.factory-' + factory + '.engine').hide();
	}

	function toggleEngines(machine)
	{
		$('.domain tr.machine-' + machine + '.engine').toggle();
	}
</script>
<? append_slot() ?>