<?php

include __DIR__ . '/_common.php';

$factories = fetch_all('SELECT * FROM factories');

if (is_allowed_to('global_activity'))
{
	$logs = fetch_all('SELECT l.message, l.time, u.name AS logger FROM logs l LEFT JOIN users u ON u.id=l.user ORDER BY l.time DESC, l.id DESC LIMIT 20');
}
else
{
	$logs = fetch_all('SELECT l.message, l.time, u.name AS logger FROM logs l LEFT JOIN users u ON u.id=l.user WHERE u.id=:id ORDER BY l.time DESC, l.id DESC LIMIT 20', array(':id' => $_SESSION['user']->getId()));
}

$canAddFactory = is_allowed_to('factory/add');

?>

<? begin_slot('head') ?>
<style>
#logsBlock .logger { font-size: 0.8em; font-weight: normal; font-style: oblique; }
#logsBlock .block-body { overflow: auto; }
</style>
<? append_slot() ?>

<? decorate() ?>

<div class="yui-ge">
	<div class="yui-u first">
		<div id="map" style="width: 100%; height: 480px;"></div>
	</div>
	<div class="yui-u">
		<div class="block" id="logsBlock">
			<div class="block-header">
				<h1 class="block-name">Logi</h1>
				<ul class="block-options">
					<li><?= fff('Pokaż wszystkie logi', 'user_magnify', 'user/logs.php') ?>
				</ul>
			</div>
			<div class="block-body">
				<dl>
				<? foreach ($logs as $log): ?>
					<dt><?= $log->time ?> <span class="logger"><?= $log->logger ?></span></dt>
					<dd><?= escape($log->message) ?></dd>
				<? endforeach ?>
				</dl>
			</div>
		</div>
	</div>
</div>

<? if ($canAddFactory): ?>
<div id="newFactoryForm" class="block">
	<div class="block-header">
		<h1 class="block-name">Nowa fabryka</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('factory/add.php') ?>">
			<input type="hidden" id="newFactoryLatitude" name="factory[latitude]" value="">
			<input type="hidden" id="newFactoryLongitude" name="factory[longitude]" value="">
			<fieldset>
				<legend>Nowa fabryka</legend>
				<ol class="form-fields">
					<li>
						<label for="newFactoryName">Nazwa<span class="form-field-required" title="Wymagane">*</span></label>
						<input id="newFactoryName" name="factory[name]" type="text" maxlength="128" value="">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Dodaj fabrykę">
							<li><a id="closeNewFactoryForm" href="<?= url_for('') ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>
<? endif ?>

<div id="factory"></div>
<? begin_slot('js') ?>
<script src="http://www.google.com/jsapi?key=<?= ENVIS_GOOGLE_KEY ?>"></script>
<script src="<?= url_for_media('jquery-plugins/simplemodal/1.3/jquery.simplemodal.min.js') ?>"></script>
<script>
  google.load("maps", "2.x");

	var factories = new Array();
<? foreach ($factories as $factory): ?>
	<? if (has_access_to_factory($factory->id)): ?>
	factories[<?= $factory->id ?>] = <?= json_encode($factory) ?>;
	<? endif ?>
<? endforeach ?>

	var map;
	var lastLoc;
	
  function initialize()
	{
		resizeMap();

		$(window).resize(resizeMap);

		initializeForms();

		$('#factory').hide();
		
    map = new google.maps.Map2(document.getElementById('map'));
		<? if ($canAddFactory): ?>map.disableDoubleClickZoom();<? endif ?>
    map.setCenter(new google.maps.LatLng(52.069167, 19.480556), 7);
		map.addControl(new GLargeMapControl3D());

		new GKeyboardHandler(map);

		<? if ($canAddFactory): ?>
		GEvent.addListener(map, 'dblclick', function(overlay, location)
		{
			lastLoc = location;

			$('#newFactoryName').val('');
			
			$('#newFactoryForm').modal({
				overlayCss: {
					backgroundColor: '#000',
					cursor: 'wait'
				},
				containerCss: {
					textAlign: 'left',
					width: '500px'
				}
			});

			center($('#simplemodal-container'), $('#newFactoryForm'));
		});
		<? endif ?>

		for (var id in factories)
		{
			map.addOverlay(createFactoryMarker(id, new GLatLng(factories[id].latitude, factories[id].longitude)));
		}
  }

	function resizeMap()
	{
		var height = $(window).height() - $('#ft').outerHeight(true) - 60;

		if ($('body').hasClass('sos'))
		{
			height -= $('#submenu').size() ? $('#submenu').outerHeight(true) : 0;
		}
		else
		{
			height -= $('#hd').outerHeight(true);
		}

		$('#map').height(height);
		$('#logsBlock .block-body').height(height - 30 - $('#logsBlock .block-header').outerHeight(true));
	}

	function initializeForms()
	{
		<? if ($canAddFactory): ?>
		$('#newFactoryForm').submit(function()
		{
			$.post(
				'<?= url_for('factory/add.php') ?>',
				'factory[name]=' + $('#newFactoryName').val() + '&factory[latitude]=' + lastLoc.lat() + '&factory[longitude]=' + lastLoc.lng(),
				function(data)
				{
					if (data.status)
					{
						factories[data.factory.id] = data.factory;

						map.addOverlay(createFactoryMarker(data.factory.id, new GLatLng(data.factory.latitude, data.factory.longitude)));
					}

					$.modal.close();
				},
				'json'
			);

			return false;
		});
		$('#closeNewFactoryForm').click(function()
		{
			$.modal.close();

			return false;
		});
		<? endif ?>
	}

	function createFactoryMarker(id, location)
	{
		var marker = new GMarker(location, {draggable: <?= is_allowed_to('factory/edit') ? 'true' : 'false' ?>, title: factories[id].name});
		marker.factoryId = id;

		<? if (is_allowed_to('factory/edit')): ?>
		GEvent.addListener(marker, 'dragend', function()
		{
			$.post('<?= url_for('factory/move.php') ?>', {id: marker.factoryId, latitude: marker.getLatLng().lat(), longitude: marker.getLatLng().lng()});
		});
		<? endif ?>
		<? if (is_allowed_to('vis/factory')): ?>
		GEvent.addListener(marker, 'click', function()
		{
			window.location.href = '<?= url_for('factory.php') ?>?id=' + marker.factoryId;
		});
		<? endif ?>

		return marker;
	}

  google.setOnLoadCallback(initialize);
</script>
<? append_slot() ?>