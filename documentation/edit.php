<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('documentation/edit');

$oldDoc = fetch_one('SELECT d.*, m.factory FROM documentations d LEFT JOIN machines m ON m.id=d.machine WHERE d.id=:id', array(':id' => $_GET['id']));

not_found_if(empty($oldDoc));

no_access_if_not(has_access_to_machine($oldDoc->machine));

$files = fetch_all('SELECT id, name, file FROM documentation_files WHERE documentation=:doc ORDER BY name ASC', array(':doc' => $_GET['id']));
$fileMap = array();

foreach ($files as $file)
{
  $fileMap[$file->id] = $file->name;
}

$product = empty($_GET['product']) || !is_numeric($_GET['product']) ? 0 : $_GET['product'];

$errors = array();
$referer = get_referer("documentation/view.php?id={$oldDoc->id}");

if (isset($_POST['doc']))
{
  $doc = $_POST['doc'];

  if (!between(1, $doc['title'], 128))
  {
    $errors[] = 'Tytuł musi się składać z od 1 do 128 znaków.';
  }

  if (!empty($doc['machine']) && !has_access_to_machine($doc['machine']))
  {
    $errors[] = 'Nie masz uprawnień do wybranej maszyny.';
  }

  if (empty($errors))
  {
    $bindings = array(
      ':id' => $oldDoc->id,
      ':machine' => empty($doc['machine']) ? null : $doc['machine'],
      ':device' => empty($doc['device']) ? null : $doc['device'],
      ':title' => $doc['title'],
      ':description' => $doc['description'],
    );

    $conn = get_conn();

    try
    {
      $conn->beginTransaction();

      exec_stmt('UPDATE documentations SET title=:title, description=:description, machine=:machine, device=:device WHERE id=:id', $bindings);

      $dstDir = ENVIS_UPLOADS_PATH . '/documentation';

      $doc['files'] = $fileMap;

      $currentFiles = empty($doc['currentFiles']) ? array() : $doc['currentFiles'];

      $diff = array_diff(array_keys($doc['files']), $currentFiles);

      if (!empty($diff))
      {
        exec_stmt(sprintf('DELETE FROM documentation_files WHERE id IN(%s)', implode(', ', $diff)));

        foreach ($diff as $id)
        {
          $file = $dstDir . '/' . $doc['files'][$id];

          if (file_exists($file))
          {
            unlink($file);
          }
        }
      }

      $stmt = prepare_stmt('UPDATE documentation_files SET name=:name WHERE id=:id');

      foreach ($currentFiles as $fileId)
      {
        $oldName = trim($doc['files'][$fileId]);
        $newName = trim($doc['currentFileNames'][$fileId]);

        if ($newName === $oldName)
        {
          continue;
        }

        $stmt->execute(array(':name' => $newName, ':id' => $fileId));
      }

      if (!empty($doc['filepaths']))
      {
        $srcDir = $dstDir . '-tmp';

        $stmt = prepare_stmt('INSERT INTO documentation_files SET documentation=:doc, file=:file, name=:name');

        foreach ($doc['filepaths'] as $i => $filepath)
        {
          $filepath = $_SERVER['DOCUMENT_ROOT'] . $filepath;

          if (!file_exists($filepath) || empty($doc['filenames'][$i])) continue;

          $file = md5(microtime() . $filepath) . strrchr($filepath, '.');

          rename($filepath, $dstDir . '/' . $file);

          exec_stmt($stmt, array(
            'doc' => $oldDoc->id,
            'file' => $file,
            'name' => $doc['filenames'][$i],
          ));
        }
      }

      $conn->commit();

      log_info('Zmodyfikowano dokumentację <%s>.', $_POST['doc']['title']);

      set_flash(sprintf('Dokumentacja <%s> została zmodyfikowana pomyślnie.', $_POST['doc']['title']));

      if (empty($product))
      {
        go_to("documentation/view.php?id={$oldDoc->id}");
      }
      else
      {
        go_to(get_referer("catalog/?product={$product}") . '#docs');
      }
    }
    catch (PDOException $x)
    {
      $conn->rollBack();

      set_flash('Dokumentacja nie została zmodyfikowana. ' . $x, 'error');

      go_to($referer);
    }
  }
}
else
{
  $doc = array(
    'factory' => $oldDoc->factory,
    'machine' => $oldDoc->machine,
    'device' => $oldDoc->device,
    'title' => $oldDoc->title,
    'description' => $oldDoc->description,
    'id' => md5(microtime()),
  );
}

$doc['files'] = $fileMap;

if (!isset($doc['currentFiles']))
{
  $doc['currentFiles'] = array_keys($doc['files']);
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
$machines = array();
$devices = array();

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
<link rel="stylesheet" href="<?= url_for_media('uploadify/2.1.4/uploadify.css', true) ?>">
<style>
.doc-fileList {
  margin-left: 0;
}
.doc-fileList li {
  list-style: none;
}
.doc-fileList li:last-child {
  margin-bottom: 0.5em;
}
.doc-fileList input[type="text"] {
  width: 20em;
  margin-left: 0.5em;
}
</style>
<? append_slot() ?>

<? decorate("Edycja dokumentacji") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Edycja dokumentacji</h1>
  </div>
  <div class="block-body">
    <form name="newdoc" method="post" action="<?= url_for("documentation/edit.php?id={$oldDoc->id}&product={$product}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <input type="hidden" name="doc[id]" value="<?= $doc['id'] ?>">
      <fieldset>
        <legend>Edycja dokumentacji</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <fieldset>
              <legend>Dotyczy</legend>
              <ol class="form-fields">
                <li class="horizontal">
                  <ol>
                    <li>
                      <label for="doc-factory">Fabryka</label>
                      <select id="doc-factory" name="doc[factory]">
                        <option value="0"></option>
                        <?= render_options($factories, $doc['factory']) ?>
                      </select>
                    <li>
                      <label for="doc-machine">Maszyna</label>
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
          <? if (!empty($doc['files'])): ?>
          <li class="form-choice">
            <label>Aktualne pliki</label>
            <ul class="doc-fileList">
              <? foreach ($doc['files'] as $fileId => $fileName): ?>
              <li>
                <input type="checkbox" <?= checked_if(in_array($fileId, $doc['currentFiles'])) ?> name="doc[currentFiles][]" value="<?= $fileId ?>">
                <input type="text" name="doc[currentFileNames][<?= $fileId ?>]" value="<?= e($fileName) ?>">
              <? endforeach ?>
            </ul>
          <? endif ?>
          <li>
            <label for="doc-files">Nowe pliki</label>
            <ul id="doc-fileList" class="doc-fileList">
            <? foreach ($doc['filepaths'] as $i => $filepath): ?>
              <li><input name="doc[filepaths][<?= $i ?>]" type="checkbox" checked="checked" value="<?= $filepath ?>"><input name="doc[filenames][<?= $i ?>]" type="text" value="<?= $doc['filenames'][$i] ?>">
            <? endforeach ?>
            </ul>
            <input id="doc-files" type="file">
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Edytuj dokumentację">
              <li><a href="<?= $referer ?>#docs">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
<? begin_slot('js') ?>
<script src="<?= url_for_media("uploadify/2.1.4/swfobject.js", true) ?>"></script>
<script src="<?= url_for_media("uploadify/2.1.4/jquery.uploadify.min.js", true) ?>"></script>
<script>
  $(function()
  {
    var factory = $('#doc-factory');
    var machine = $('#doc-machine');
    var device = $('#doc-device');

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

    var fileList = $('#doc-fileList');
    var fileCount = <?= $i + 1 ?>;

    $('#doc-files').uploadify({
      uploader: '<?= url_for_media("uploadify/2.1.4/uploadify.swf", true) ?>',
      script: '<?= url_for_media("uploadify/2.1.4/uploadify.php", true) ?>',
      cancelImg: '<?= url_for_media("uploadify/2.1.4/cancel.png", true) ?>',
      folder: '/documentation-tmp',
      auto: true,
      multi: true,
      buttonText: 'Wybierz pliki',
      fileDesc: 'Plik dokumentacji',
      fileExt: '*.pdf;*.doc;*.docx;*.xls;*.xlsx;*.odt;*.zip;*.rar;*.gz;*.7z;*.png;*.jpg;*.jpeg;*.gif;*.txt;*.csv;*.md',
      scriptData: {id: '<?= $doc['id'] ?>'},
      onComplete   : function(e, id, file, response, data)
      {
        console.log(arguments);
        fileList.append(render(
          '<li><input name="doc[filepaths][${i}]" type="checkbox" checked="checked" value="${file}"><input name="doc[filenames][${i}]" type="text" value="${name}">',
          {i: fileCount++, file: response, name: file.name}
        ));
      }
    });
  });
</script>
<? append_slot() ?>
