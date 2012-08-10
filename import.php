<?php

ignore_user_abort(true);
set_time_limit(0);
error_reporting(E_ALL);

function write($msg)
{
	$args = func_get_args();
	
	$msg = '[' . date('H:i:s') . '] ' . implode('', $args) . PHP_EOL;
	
	if (isset($_GET['debug']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'debug'))
	{
		echo $msg;
	}
}

if (!isset($fromConfig))
{
	$fromImport = true;

	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_common.php';
}

$dir = dirname(__FILE__) . ENVIS_UPLOADS_DIR . DIRECTORY_SEPARATOR . 'monitoring';

/* @var $fileInfo SplFileInfo */
foreach (new DirectoryIterator($dir) as $fileInfo)
{
	$file = $fileInfo->getFilename();
	$path = $fileInfo->getPathname();

	if (!preg_match('#^[a-z0-9_-]+_[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}\.txt$#i', $file, $matches))
	{
		if ($fileInfo->isFile())
    {
      rename($path, dirname($dir) . '/imported/' . $file . '.zlaNazwa');
      #@unlink($path);
    }
		
		continue;
	}

	$fp = fopen($path, 'r');

	if (flock($fp, LOCK_EX))
	{
		write('Parsing ', $file);

		$conn = get_conn();

		$time    = '';
		$machine = '';
		$data    = array();

		while (!feof($fp))
		{
			$line = trim(fgets($fp));
			
			if (preg_match('/[0-9]{2}:[0-9]{2}:[0-9]{2}/', $line))
			{
				$time = strtotime($line);

				continue;
			}
			elseif (preg_match('/^Machine name: (.*?)$/', $line, $match))
			{
				$machine = $match[1];

				continue;
			}
			elseif (preg_match('/^(?P<device>.+):(?P<var>.*?)\) (?P<val>.*?)$/', $line, $match))
			{
				if (!is_numeric($match['val']))
				{
					$match['val'] = 0;

          continue;
				}

				$data[] = array(
					'time'    => $time,
					'machine' => $machine,
					'device'  => $match['device'],
					'var'     => $match['var'],
					'val'     => (float)$match['val']
				);
			}
		}
		
		fclose($fp);

		if (empty($data))
		{
			rename($path, dirname($dir) . '/imported/' . $file . '.pusty');
      #@unlink($path);

			write('No data.');

			continue;
		}

		$query = 'INSERT INTO `values` (`machine`, `engine`, `variable`, `value`, `createdAt`) VALUES';

		foreach ($data as $entry)
		{
			$query .= sprintf('("%s","%s","%s",%s,"%s"),', $entry['machine'], $entry['device'], $entry['var'], $entry['val'], gmdate('Y-m-d H:i:s', $entry['time']));
		}

		try
		{
			$conn->beginTransaction();

			$conn->exec(substr($query, 0, -1));

			$conn->commit();

			rename($path, dirname($dir) . '/imported/' . $file . '.ok');
			#@unlink($path);
			
			write('Done');
		}
		catch (PDOException $x)
		{
			$conn->rollBack();
      
      rename($path, dirname($dir) . '/imported/' . $file . '.zlyConfig');
      #@unlink($path);

			write('Error: ', $x->getMessage());
		}
	}
	else
	{
		write('Skipped ', $file);

		continue;
	}
}