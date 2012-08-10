<?php

include '../_common.php';

no_access_if_not_allowed('documentation/add');

if (!empty($_GET['machine']))
{
	no_access_if_not(has_access_to_machine($_GET['machine']));
}

$errors  = array();
$referer = get_referer('documentation/');

if (isset($_POST['doc']))
{
	$doc = $_POST['doc'];

	if (!between(1, $doc['title'], 128))
	{
		$errors[] = 'Tytuł musi się składać z od 1 do 128 znaków.';
	}

	if (empty($doc['factory']) || empty($doc['machine']))
	{
		$errors[] = 'Pole dotyczy jest wymagane.';
	}

	if (!empty($doc['machine']) && !has_access_to_machine($doc['machine']))
	{
		$errors[] = 'Nie masz uprawnień do wybranej maszyny.';
	}

	if (empty($errors))
	{
		$bindings = array(
			':machine'     => $doc['machine'],
			':device'      => empty($doc['device']) ? NULL : $doc['device'],
			':title'       => $doc['title'],
			':description' => $doc['description'],
		);

		$conn = get_conn();
		
		try
		{
			$conn->beginTransaction();

			exec_stmt('INSERT INTO documentations SET title=:title, description=:description, machine=:machine, device=:device', $bindings);

			$id = get_conn()->lastInsertId();

			if (!empty($doc['filepaths']))
			{
				$dstDir = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/documentation';
				$srcDir = $dstDir . '-tmp';

				$stmt = prepare_stmt('INSERT INTO documentation_files SET documentation=:doc, file=:file, name=:name');

				foreach ($doc['filepaths'] as $i => $filepath)
				{
					$filepath = $srcDir . '/' . $doc['id'] . $filepath;

					if (!file_exists($filepath) || empty($doc['filenames'][$i])) continue;

					$file = md5(microtime() . $filepath) . strrchr($filepath, '.');

					rename($filepath, $dstDir . '/' . $file);
					
					exec_stmt($stmt, array(
						'doc'  => $id,
						'file' => $file,
						'name' => $doc['filenames'][$i],
					));
				}
			}

			$conn->commit();

			log_info('Dodano dokumentację <%s>.', $doc['title']);

			set_flash(sprintf('Dokumentacja <%s> została dodana pomyślnie.', $doc['title']));

			go_to('documentation/view.php?id=' . $id);
		}
		catch (PDOException $x)
		{
			$conn->rollBack();

			set_flash('Dokumentacja nie została dodana. ' . $x, 'error');

			go_to($referer);
		}
	}
}
else
{
	$doc = array(
		'factory'     => null,
		'machine'     => null,
		'device'      => null,
		'title'       => '',
		'description' => '',
		'id'          => md5(microtime()),
	);
}

$doc += array(
	'filenames' => array(),
	'filepaths' => array(),
);

escape_array($doc);

$where = '';

if (!$_SESSION['user']->isSuper())
{
	$where = 'f.id IN(' . implode(',', $_SESSION['user']->getAllowedFactoryIds()) . ') AND';
}

$factories = fetch_array('SELECT f.id AS `key`, f.name AS value FROM factories f WHERE ' . $where . ' (SELECT COUNT(*) FROM machines m WHERE m.factory=f.id) > 0 ORDER BY f.name');
$machines  = array();
$devices   = array();

if (!empty($_GET['factory']) && !empty($_GET['machine']))
{
	$doc['factory'] = (int)$_GET['factory'];
	$doc['machine'] = $_GET['machine'];

	if (!empty($_GET['device']))
	{
		$doc['device'] = $_GET['device'];
	}
}

if (!empty($doc['factory']))
{
	$where = '';

	if (!$_SESSION['user']->isSuper())
	{
		$where = ' AND id IN(' . list_quoted($_SESSION['user']->getAllowedMachineIds()) . ')';
	}

	$machines = fetch_array('SELECT id AS `key`, name AS value FROM machines WHERE factory=:factory ' . $where . ' ORDER BY name', array(':factory' => $doc['factory']));
}

if (!empty($doc['machine']))
{
	$devices = fetch_array('SELECT id AS `key`, name AS value FROM engines WHERE machine=:machine ORDER BY name', array(':machine' => $doc['machine']));
}

$i = -1;

?>
<? begin_slot('head') ?>
<link rel="stylesheet" href="<?= url_for_media('jquery-plugins/uploadify/2.0.3/uploadify.css') ?>">
<style>
	#doc-fileList
	{
		margin-left: 0;
	}
	#doc-fileList li
	{
		list-style: none;
	}
	#doc-fileList li:last-child
	{
		margin-bottom: 0.5em;
	}
	#doc-fileList input[type="text"]
	{
		width: 20em;
		margin-left: 0.5em;
	}
</style>
<? append_slot() ?>

<? decorate("Dodawanie nowej dokumentacji") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Nowa dokumentacja</h1>
	</div>
	<div class="block-body">
		<form name="newdoc" method="post" action="<?= url_for('documentation/add.php') ?>">
			<input type="hidden" name="referer" value="<?= $referer ?>">
			<input type="hidden" name="doc[id]" value="<?= $doc['id'] ?>">
			<fieldset>
				<legend>Nowa dokumentacja</legend>
				<? display_errors($errors) ?>
				<ol class="form-fields">
					<li>
						<fieldset>
							<legend>Dotyczy</legend>
							<ol class="form-fields">
								<li class="horizontal">
									<ol>
										<li>
											<label for="doc-factory">Fabryka<span class="form-field-required" title="Wymagane">*</span></label>
											<select id="doc-factory" name="doc[factory]">
												<option value="0"></option>
												<?= render_options($factories, $doc['factory']) ?>
											</select>
										<li>
											<label for="doc-machine">Maszyna<span class="form-field-required" title="Wymagane">*</span></label>
											<select id="doc-machine" name="doc[machine]">
												<option value="0"></option>
												<?= render_options($machines, $doc['machine']) ?>
											</select>
										<li>
											<label for="doc-device">Urządzenie</label>
											<select id="doc-device" name="doc[device]">
												<option value="0"></option>
												<?= render_options($devices, $doc['device']) ?>
											</select>
									</ol>
							</ol>
						</fieldset>
					<li>
						<label for="doc-title">Tytuł<span class="form-field-required" title="Wymagane">*</span></label>
						<input id="doc-title" name="doc[title]" type="text" maxlength="128" value="<?= $doc['title'] ?>">
						<p class="form-field-help">Od 1 do 128 znaków.</p>
					<li>
						<label for="doc-description">Opis</label>
						<textarea id="doc-description" class="markdown resizable" name="doc[description]"><?= $doc['description'] ?></textarea>
					<li>
						<label for="doc-files">Pliki</label>
						<ul id="doc-fileList">
						<? foreach ($doc['filepaths'] as $i => $filepath): ?>
							<li><input name="doc[filepaths][<?= $i ?>]" type="checkbox" checked="checked" value="<?= $filepath ?>"><input name="doc[filenames][<?= $i ?>]" type="text" value="<?= $doc['filenames'][$i] ?>">
						<? endforeach ?>
						</ul>
						<input id="doc-files" type="file">
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Dodaj dokumentację">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>

<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-plugins/uploadify/2.0.3/swfobject.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/uploadify/2.0.3/jquery.uploadify.min.js') ?>"></script>
<script>
	$(document).ready(function()
	{
		var factory = $('#doc-factory');
		var machine = $('#doc-machine');
		var device  = $('#doc-device');

		if (machine.get(0).length == 1)
		{
			machine.parent().hide();
		}

		if (device.get(0).length == 1)
		{
			device.parent().hide();
		}

		factory.change(function()
		{
			device.parent().fadeOut(250, function() { device.empty(); });

			if (factory.val() == 0)
			{
				machine.parent().fadeOut(500, function() { machine.empty(); });

				return true;
			}

			startWaiting();

			$.get(
				'<?= url_for('service/fetch_objects.php') ?>',
				{
          type: 1,
					parent: factory.val()
				},
				function(data)
				{
					if (data.length > 0)
					{
						machine.empty().append('<option value="0"></option>');

						for (var i in data)
						{
							machine.append(render('<option value="${value}">${label}</option>', data[i]));
						}

						machine.parent().fadeIn(500, function() { machine.focus(); });
					}
					else
					{
						machine.parent().fadeOut(500, function() { machine.empty(); });
					}

					stopWaiting();
				},
				'json'
			);
		});

		machine.change(function()
		{
			device.parent().fadeOut(500, function() { device.empty(); });

			if (machine.val() == 0) return true;

			startWaiting();

			$.get(
				'<?= url_for('service/fetch_objects.php') ?>',
				{
          type: 2,
					parent: machine.val()
				},
				function(data)
				{
					if (data.length > 0)
					{
						device.append('<option value="0"></option>');

						for (var i in data)
						{
							device.append(render('<option value="${value}">${label}</option>', data[i]));
						}

						device.parent().fadeIn(500, function() { device.focus() });
					}

					stopWaiting();
				},
				'json'
			);
		});

		var fileList  = $('#doc-fileList');
		var fileCount = <?= $i + 1 ?>;

		$('#doc-files').uploadify({
			'scriptAccess': 'always',
			'uploader'   : '<?= url_for_media('uploadify/uploadify.swf', true) ?>',
			'script'     : 'http://<?= ENVIS_DOMAIN . url_for('_files_/uploadify_multi.php') ?>',
			'checkScript': 'http://<?= ENVIS_DOMAIN . url_for('_files_/check.php') ?>',
			'cancelImg'  : '<?= url_for_media('jquery-plugins/uploadify/2.0.3/cancel.png') ?>',
			'auto'       : true,
			'folder'     : '<?= ENVIS_UPLOADS_DIR ?>/documentation-tmp',
			'fileDesc'   : 'Plik dokumentacji',
			'fileExt'    : '*.pdf;*.doc;*.docx;*.xls;*.xlsx;*.odt;*.zip;*.rar;*.png;*.jpg;*.jpeg;*.gif',
			'sizeLimit'  : 6291456,
			'buttonText' : 'Wybierz',
			'scriptData' : {id: '<?= $doc['id'] ?>'},
			'multi'      : true,
			onComplete   : function(event, queueID, file, response, data)
			{
				fileList.append(render('<li><input name="doc[filepaths][${i}]" type="checkbox" checked="checked" value="${file}"><input name="doc[filenames][${i}]" type="text" value="${name}">', {i: fileCount++, file: file.name, name: file.name}));
			}
		});
	});
</script>
<? append_slot() ?>
