<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']));

$storage = fetch_one('SELECT id, name, owner FROM storages WHERE id=:id', array(':id' => $_GET['id']));

not_found_if(empty($storage));

no_access_if_not($_SESSION['user']->isSuper() || ($storage->owner == $_SESSION['user']->getId()));

$referer = get_referer('storage/view.php?id=' . $storage->id);
$errors = array();

if (!empty($_POST['filepath']))
{
  $file = dirname(dirname(__FILE__)) . ENVIS_UPLOADS_DIR . '/imported/' . $storage->id . $_POST['filepath'];

  if (!file_exists($file))
  {
    $errors[] = 'Wybrany plik nie istnieje.';
  }

  $ext = ltrim(strrchr($_POST['filepath'], '.'), '.');

  switch ($ext)
  {
    case 'csv':
    {
      $handle = fopen($file, 'r');
      $products = array();

      while (($line = fgetcsv($handle)) !== false)
      {
        if (count($line) !== 6)
        {
          $errors[] = sprintf('Niepoprawny format pliku CSV. Nieprawidłowa liczba kolumn (%d/%d) w wierszu %d.', count($line), 6, $k + 1);

          break;
        }

        $products[] = array(
          'index' => (string)$line[0],
          'name' => (string)$line[1],
          'price' => (float)$line[2],
          'quantity' => (int)$line[3],
          'supplier' => (string)str_replace(array('\t', '\r', '\n'), array("\t", "\r", "\n"), $line[4]),
          'contact' => (string)str_replace(array('\t', '\r', '\n'), array("\t", "\r", "\n"), $line[5])
        );
      }

      array_shift($products);

      fclose($handle);

      break;
    }

    case 'xml':
    {
      $xml = simplexml_load_file($file);

      $headers = array('index', 'name', 'price', 'quantity', 'supplier', 'contact');
      $products = array();

      foreach ($xml->product as $k => $node)
      {
        $product = array();

        foreach ($headers as $header)
        {
          if (!isset($node->$header))
          {
            $errors[] = sprintf('Nieprawiodłowy format pliku XML. Wymagany węzęł %s nie istnieje (produkt %d).', $header, $k + 1);

            break 2;
          }

          $product[$header] = (string)$node->$header;
        }

        $products[] = $product;
      }

      break;
    }

    case 'xls':
    case 'xlsx':
    {
      include_once __DIR__ . '/../_lib_/PHPExcel.php';
      include_once __DIR__ . '/../_lib_/PHPExcel/IOFactory.php';

      $sheet = PHPExcel_IOFactory::createReader($ext === 'xls' ? 'Excel5' : 'Excel2007')
        ->load($file)
        ->getActiveSheet();

      $highestRow = $sheet->getHighestRow();
      $highestColumn = $sheet->getHighestColumn();

      $rowData = array();
      $row = 1;

      do
      {
        $columnData = array();
        $column = 'A';

        do
        {
          if ($sheet->cellExists($column.$row))
          {
            $actualValue = $sheet->getCell($column.$row)->getValue();
            $displayValue = $sheet->getCell($column.$row)->getCalculatedValue();
          }
          else
          {
            $actualValue = $displayValue = null;
          }

          $columnData[] = array(
            'columnID' => $column,
            'cellID' => $column.$row,
            'actualValue' => $actualValue,
            'displayValue' => $displayValue
          );
        }
        while ($column++ != $highestColumn);

        $rowData[] = array(
          'rowID' => $row,
          'columns' => $columnData
        );
      }
      while ($row++ != $highestRow);

      $products = array();

      foreach ($rowData as $row)
      {
        if (empty($products))
        {
          $value = trim($row['columns'][0]['actualValue']);

          if ($value === '' || preg_match('#^inde(ks|x)$#i', $value))
          {
            continue;
          }
        }

        if (count($row['columns']) < 6)
        {
          $errors[] = sprintf('Nieprawidłowy format pliku XLS. Nieprawidłowa liczba kolumn (%d < 6) w wierszu %d.', count($row['columns']), $row['rowID']);

          break;
        }

        $products[] = array(
          'index' => (string)$row['columns'][0]['actualValue'],
          'name' => (string)$row['columns'][1]['actualValue'],
          'price' => (float)$row['columns'][2]['actualValue'],
          'quantity' => (int)$row['columns'][3]['actualValue'],
          'supplier' => (string)$row['columns'][4]['actualValue'],
          'contact' => (string)$row['columns'][5]['actualValue']
        );
      }

      break;
    }
  }

  if (empty($errors))
  {
    $conn = get_conn();

    try
    {
      $conn->beginTransaction();

      exec_stmt('DELETE FROM storage_products WHERE storage=:storage', array(':storage' => $storage->id));

      $query = 'INSERT INTO storage_products (storage, `index`, name, price, quantity, supplier, contact) VALUES';

      $productCount = count($products) - 1;

      foreach ($products as $k => $product)
      {
        $query .= sprintf('(%d, %s, %s, %F, %d, %s, %s)', $storage->id,
          $conn->quote($product['index']),
          $conn->quote($product['name']),
          $product['price'],
          $product['quantity'],
          $conn->quote($product['supplier']),
          $conn->quote($product['contact'])
        ) . ($k === $productCount ? '' : ', ');
      }

      exec_stmt($query);

      unlink($file);

      /* @var $fileInfo SplFileInfo */
      foreach (new DirectoryIterator(dirname($file)) as $fileInfo)
      {
        if ($fileInfo->isFile() && ((time() - 300) > $fileInfo->getCTime()))
        {
          unlink($fileInfo->getPathname());
        }
      }

      $conn->commit();

      log_info('Zimportowano produkty do magazynu <%s>.', $storage->name);

      set_flash('Produkty zostały pomyślnie zimportowane.');

      go_to('storage/view.php?id=' . $storage->id);
    }
    catch (PDOException $x)
    {
      $conn->rollBack();

      $errors[] = $x->getMessage();
    }
  }
}

escape_var($storage->name);

$availFileExt = '*.csv;*.xml';

if (class_exists('ZipArchive', false))
{
  $availFileExt .= ';*.xls;*.xlsx';
}


?>
<? begin_slot('head') ?>
<link rel="stylesheet" href="<?= url_for_media('jquery-plugins/uploadify/2.0.3/uploadify.css') ?>">
<style>
  #fileList
  {
    margin-left: 0;
  }
  #fileList li
  {
    list-style: none;
  }
  #fileList input { margin-right: 0.5em; }
  #fileList li:last-child
  {
    margin-bottom: 0.5em;
  }
</style>
<? append_slot() ?>

<? decorate("Importowanie produktów magazynu") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Import produków magazynu &lt;<?= $storage->name ?>&gt;</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for("storage/import.php?id={$storage->id}") ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Import produktów</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label>Magazyn</label>
            <p><a href="<?= url_for("storage/view.php?id={$storage->id}") ?>"><?= $storage->name ?></a></p>
          <li>
            <label for="file">Plik</label>
            <ul id="fileList"></ul>
            <input id="file" name="file" type="file">
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Importuj">
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
  $(function()
  {
    var fileCount = 0;

    $('#file').uploadify(
    {
      'scriptAccess': 'always',
      'uploader'   : '<?= url_for_media('uploadify/uploadify.swf', true) ?>',
      'script'     : '<?= url_for('_files_/uploadify_multi.php', true) ?>',
      'checkScript': '<?= url_for('_files_/check.php', true) ?>',
      'cancelImg'  : '<?= url_for_media('jquery-plugins/uploadify/2.0.3/cancel.png') ?>',
      'auto'       : true,
      'folder'     : '<?= ENVIS_UPLOADS_DIR ?>/imported',
      'fileDesc'   : 'Magazyn (<?= $availFileExt ?>)',
      'fileExt'    : '<?= $availFileExt ?>',
      'sizeLimit'  : 6291456,
      'buttonText' : 'Wybierz',
      'scriptData' : {id: '<?= $storage->id ?>'},
      'multi'      : false,
      onComplete   : function(event, queueID, file, response, data)
      {
        $('#fileList').append(render('<li><input id="filepath-${i}" name="filepath" type="radio" checked="checked" value="${file}"><label for="filepath-${i}">${name}</label>', {i: ++fileCount, file: file.name, name: file.name}));
      }
    });
  });
</script>
<? append_slot() ?>
