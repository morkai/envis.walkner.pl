<?php

include_once __DIR__ . '/_common.php';

no_access_if_not_allowed('documentation/add');

if (!empty($_GET['machine']))
{
  no_access_if_not(has_access_to_machine($_GET['machine']));
}

$product = empty($_GET['product']) ? 0 : $_GET['product'];

if ($product)
{
  $product = fetch_one('SELECT id, name FROM catalog_products WHERE id=? LIMIT 1', array(1 => $_GET['product']));

  bad_request_if(empty($product));
}

$errors = array();
$referer = get_referer('documentation/');

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
      ':machine' => empty($doc['machine']) ? null : $doc['machine'],
      ':device' => empty($doc['device']) ? null : $doc['device'],
      ':title' => $doc['title'],
      ':description' => $doc['description'],
    );

    $conn = get_conn();

    try
    {
      $conn->beginTransaction();

      exec_insert('documentations', $bindings);

      $id = $conn->lastInsertId();

      if (!empty($doc['filepaths']))
      {
        $dstDir = ENVIS_UPLOADS_PATH . '/documentation';
        $srcDir = $dstDir . '-tmp';

        $stmt = prepare_stmt('INSERT INTO documentation_files SET documentation=:doc, file=:file, name=:name');

        foreach ($doc['filepaths'] as $i => $filepath)
        {
          $filepath = $_SERVER['DOCUMENT_ROOT'] . $filepath;

          if (!file_exists($filepath) || empty($doc['filenames'][$i])) continue;

          $file = md5(microtime() . $filepath) . strrchr($filepath, '.');

          rename($filepath, $dstDir . '/' . $file);

          exec_stmt($stmt, array(
            'doc' => $id,
            'file' => $file,
            'name' => $doc['filenames'][$i],
          ));
        }
      }

      if (!empty($product))
      {
        exec_insert('catalog_product_documentations', array(
          'product' => $product->id,
          'documentation' => $id
        ));
      }

      $conn->commit();

      log_info('Dodano dokumentację <%s>.', $doc['title']);

      set_flash(sprintf('Dokumentacja <%s> została dodana pomyślnie.', $doc['title']));

      if (empty($product))
      {
        go_to("documentation/view.php?id={$id}");
      }
      else
      {
        go_to($referer . '#docs');
      }
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
    'factory' => null,
    'machine' => null,
    'device' => null,
    'title' => '',
    'description' => '',
    'id' => md5(microtime()),
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

$q = <<<SQL
SELECT f.id AS `key`, f.name AS value
FROM factories f
WHERE {$where} (SELECT COUNT(*) FROM machines m WHERE m.factory=f.id) > 0
ORDER BY f.name
SQL;

$factories = fetch_array($q);
$machines = array();
$devices = array();

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

$action = $product
  ? url_for("documentation/add.php?product={$product->id}")
  : url_for("documentation/add.php");

?>
<? begin_slot('head') ?>
<link rel="stylesheet" href="<?= url_for_media('uppy/uppy.min.css', true) ?>">
<style>
#doc-fileList {
  margin-left: 0;
}
#doc-fileList li {
  list-style: none;
}
#doc-fileList li:last-child {
  margin-bottom: 0.5em;
}
#doc-fileList input[type="text"] {
  width: 75%;
  margin-left: 0.5em;
}
#doc-product {
  clear: both;
}
#doc-product label {
  padding-top: .5em;
}
</style>
<? append_slot() ?>

<? decorate("Dodawanie nowej dokumentacji") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowa dokumentacja</h1>
  </div>
  <div class="block-body">
    <form name="newdoc" method="post" action="<?= $action ?>">
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
              <? if ($product): ?>
              <ol id="doc-product" class="form-fields">
                <li>
                  <label>Produkt</label>
                  <p><a href="<?= url_for("catalog/?product={$product->id}#docs") ?>"><?= e($product->name) ?></a></p>
              </ol>
              <? endif ?>
            </fieldset>
          <li>
            <label for="doc-title">Tytuł<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="doc-title" name="doc[title]" type="text" maxlength="128" value="<?= $doc['title'] ?>">
            <p class="form-field-help">Od 1 do 128 znaków.</p>
          <li>
            <label for="doc-description">Opis</label>
            <textarea id="doc-description" class="markdown resizable" name="doc[description]"><?= $doc['description'] ?></textarea>
          <li>
            <label>Pliki</label>
            <ul id="doc-fileList">
            <? foreach ($doc['filepaths'] as $i => $filepath): ?>
              <li><input name="doc[filepaths][<?= $i ?>]" type="checkbox" checked="checked" value="<?= $filepath ?>"><input name="doc[filenames][<?= $i ?>]" type="text" value="<?= $doc['filenames'][$i] ?>">
            <? endforeach ?>
            </ul>
            <div id="doc-files"></div>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj dokumentację">
              <li><a href="<?= $referer ?>#docs">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>

<? begin_slot('js') ?>
<script src="<?= url_for_media("uppy/uppy.min.js", true) ?>"></script>
<script>
  $(document).ready(function()
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

    var $fileList = $('#doc-fileList');
    var fileCount = <?= $i + 1 ?>;

    var uppy = Uppy.Core({
      autoProceed: true,
      meta: {
        folder: '/documentation-tmp'
      },
      restrictions: {
        allowedFileTypes: '.pdf;.doc;.docx;.xls;.xlsx;.odt;.zip;.rar;.gz;.7z;.png;.jpg;.jpeg;.gif;.txt;.csv;.md;.mp4'.split(';')
      }
    });

    uppy.use(Uppy.FileInput, {
      target: '#doc-files',
      pretty: false,
      replaceTargetContent: true,
      limit: 1
    });

    uppy.use(Uppy.XHRUpload, {
      endpoint: '<?= url_for_media("uppy/uppy.php", true) ?>',
      formData: true,
      fieldName: 'file'
    });

    uppy.on('file-added', file =>
    {
      var i = fileCount++;

      $fileList.append(`
<li data-id="${file.id}">
  <input name="doc[filepaths][${i}]" type="checkbox" checked="checked" disabled>
  <input name="doc[filenames][${i}]" type="text" value="${file.name}" disabled>
</li>
      `);
    });

    uppy.on('upload-error', (file) =>
    {
      $fileList.find('li[data-id="' + file.id + '"]').remove();
    });

    uppy.on('upload-success', (file, res) =>
    {
      var $li = $fileList.find('li[data-id="' + file.id + '"]');

      $li.find('input[type="checkbox"]').val(res.body.file).prop('disabled', false);
      $li.find('input[type="text"]').prop('disabled', false);
    });

    uppy.on('complete', (result) =>
    {
      result.failed.forEach(f => uppy.removeFile(f.id));
      result.successful.forEach(f => uppy.removeFile(f.id));
    });
  });
</script>
<? append_slot() ?>
