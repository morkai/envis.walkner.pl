<?php

include '../_common.php';

if (empty($_REQUEST['id'])) bad_request();

$storage = fetch_one('SELECT id, name, owner FROM storages WHERE id=:id', array(':id' => $_REQUEST['id']));

if (empty($storage)) not_found();

no_access_if_not($_SESSION['user']->isSuper() || ($storage->owner == $_SESSION['user']->getId()));

$referer = get_referer('storage/view.php?id=' . $storage->id);

if (!empty($_REQUEST['format']))
{
	$format = in_array($_REQUEST['format'], array('csv', 'xml', 'xls', 'xlsx')) ? $_REQUEST['format'] : 'csv';

	if (empty($_REQUEST['file']))
	{
		$file = $storage->name . '.' . $format;
	}
	elseif (preg_match('#\.' . $format . '$#i', $_REQUEST['file']))
	{
		$file = $_REQUEST['file'];
	}
	else
	{
		$file = $_REQUEST['file'] . '.' . $format;
	}

	$products = fetch_all('SELECT `index`, name, price, quantity, supplier, contact FROM storage_products WHERE storage=:storage ORDER BY name', array(':storage' => $storage->id));

	header('Content-Disposition: attachment; filename="' . $file . '"');
	header('Cache-Control: max-age=0');

	switch ($format)
	{
		case 'csv':
		{
			header('Content-Type: text/csv');

			$result = 'Indeks,Nazwa,Cena,Ilosc,Dostawca,Kontakt';

			foreach ($products as $k => $product)
			{
				$product->supplier = str_replace(array("\t", "\r", "\n"), array('\t', '\r', '\n'), addslashes($product->supplier));
				$product->contact  = str_replace(array("\t", "\r", "\n"), array('\t', '\r', '\n'), addslashes($product->contact));

				$result .= "\n" . sprintf('"%s","%s",%.2F,%d,"%s","%s"', $product->index, addslashes($product->name), $product->price, $product->quantity, $product->supplier, $product->contact);
			}

			echo $result;

			break;
		}

		case 'xml':
		{
			header('Content-Type: text/xml');

			$xml = new SimpleXMLElement('<storage></storage>');

			foreach ($products as $product)
			{
				$node = $xml->addChild('product');

				foreach ($product as $k => $v)
				{
					$node->addChild($k, $v);
				}
			}

			echo $xml->asXML();

			break;
		}

		case 'xls':
		case 'xlsx':
		{
			if ($format === 'xls')
			{
				$type   = 'application/vnd.ms-excel';
				$writer = 'Excel5';
			}
			else
			{
				$type   = 'application/vnd.openXMLformats-officedocument.spreadsheetml.sheet';
				$writer = 'Excel2007';
			}

			header('Content-Type: ' . $type);

			include '../_lib_/PHPExcel.php';
			include '../_lib_/PHPExcel/IOFactory.php';

			$excel = new PHPExcel();
			$excel->getProperties()
				->setCreator($_SESSION['user']->getName())
				->setLastModifiedBy($_SESSION['user']->getName())
				->setTitle($title = sprintf('Magazyn %s', $storage->name))
				->setSubject($title);

			$sheet = $excel->setActiveSheetIndex(0);
			
			$sheet
				->setCellValue('A1', 'Indeks')
				->setCellValue('B1', 'Nazwa')
				->setCellValue('C1', 'Cena')
				->setCellValue('D1', 'Ilosc')
				->setCellValue('E1', 'Dostawca')
				->setCellValue('F1', 'Kontakt');

			$sheet->getStyle('A1:F1')->getFont()->setSize(16);

			foreach (range('A', 'D') as $column)
			{
				$sheet->getColumnDimension($column)->setAutoSize(true);
			}

			$sheet->getColumnDimension('E')->setWidth(30);
			$sheet->getColumnDimension('F')->setWidth(30);

			foreach ($products as $k => $product)
			{
				$k += 3;

				$sheet
					->setCellValue('A' . $k, $product->index)
					->setCellValue('B' . $k, $product->name)
					->setCellValue('C' . $k, $product->price)
					->setCellValue('D' . $k, $product->quantity)
					->setCellValue('E' . $k, $product->supplier)
					->setCellValue('F' . $k, $product->contact);
			}

			$writer = PHPExcel_IOFactory::createWriter($excel, $writer);
			$writer->save('php://output');

			break;
		}
	}

	exit;
}

escape_var($storage->name);

$xlsAvailable = class_exists('ZipArchive', false) ? '' : 'disabled';

?>

<? decorate("Eksport produktów magazynu") ?>

<div class="block">
	<div class="block-header">
		<h1 class="block-name">Eksport produków magazynu &lt;<?= $storage->name ?>&gt;</h1>
	</div>
	<div class="block-body">
		<form method="post" action="<?= url_for('storage/export.php') ?>">
			<input type="hidden" name="id" value="<?= $storage->id ?>">
			<fieldset>
				<legend>Eksport produktów</legend>
				<ol class="form-fields">
					<li>
						<label>Magazyn</label>
						<p><a href="<?= url_for("storage/view.php?id={$storage->id}") ?>"><?= $storage->name ?></a></p>
					<li>
						<label for="file">Nazwa pliku</label>
						<input id="file" name="file" type="text" value="<?= $storage->name ?>">
					<li>
						<label for="format">Format</label>
						<select id="format" name="format">
							<option value="csv" selected>CSV
							<option value="xml">XML
							<option value="xls" <?= $xlsAvailable ?>>XLS
							<option value="xlsx" <?= $xlsAvailable ?>>XLSX
						</select>
					<li>
						<ol class="form-actions">
							<li><input type="submit" value="Eksportuj">
							<li><a href="<?= $referer ?>">Anuluj</a>
						</ol>
				</ol>
			</fieldset>
		</form>
	</div>
</div>