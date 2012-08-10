<?php

$bypassAuth = true;

include './_common.php';

if (empty($_GET['device']) || empty($_GET['machine'])) bad_request();

$device = fetch_one(
	'SELECT name FROM engines WHERE id=:id AND machine=:machine',
	array(':id' => $_GET['device'], ':machine' => $_GET['machine'])
);

if (empty($device)) not_found();

$device->id      = $_GET['device'];
$device->machine = $_GET['machine'];

escape_var($device->name);

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= $device->name ?></title>
		<style>
			body
			{
				margin: 0;
				font-family: "Consolas", monospaced;
				color: #FFF;
				background: #399EF4;
				overflow: hidden;
			}
			h1
			{
				margin: 0 auto;
				text-align: center;
				font-size: 60px;
				text-shadow: 2px 2px 2px #066BD1;
				background: #288DF3;
				color: #FFF;
				box-shadow: 0px 2px 2px #066BD1;
				-webkit-box-shadow: 0px 2px 2px #066BD1;
				-moz-box-shadow: 0px 2px 2px #066BD1;
			}
			p
			{
				position: absolute;
				margin: 0 auto;
				font-family: "Consolas";
				font-size: 400px;
				text-shadow: 4px 4px 4px #066BD1;
			}
		</style>
	</head>
	<body>
		<h1><?= $device->name ?></h1>
		<p id="counter">0</p>
		<script src="<?= url_for_media('jquery/1.5.2/jquery.min.js') ?>"></script>
		<script src="<?= url_for_media('main.js.php', $local=true) ?>"></script>
		<script>
			$(function()
			{
        var counter  = $('#counter');
        var oldValue = parseInt(counter.html());
        
        function refresh()
        {
          $.ajax({
            url: '<?= url_for('counter/refresh.php') ?>',
            data: {
              machine: '<?= $device->machine ?>',
              device: '<?= $device->id ?>',
              'var': '<?= get_counter_var() ?>'
            },
            cache: false,
            dataType: 'json',
            success: function(value)
            {
              var newValue = parseInt(value);
              
              if (newValue !== oldValue)
              {
                center(counter.html(newValue));
                
                oldValue = newValue;
              }
            },
            complete: function()
            {
              setTimeout(refresh, 1000);
            }
          });
        }
        
        $(window).resize(function() { center(counter); });
        
        center(counter);
        refresh();
			});
		</script>
	</body>
</html>