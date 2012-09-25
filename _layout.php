<?php

$_menu = array(
	''              => array('Dashboard',    ''),
	//'report'        => array('Raporty',      'report*'),
	'service'       => array('Serwis',       'service*'),
  'offers'        => array('Oferty',       'offers*'),
	'factory'       => array('Fabryki',      'factory*'),
	//'counter'       => array('Liczniki',     'counter*'),
	'documentation' => array('Dokumentacje', 'documentation*'),
	'catalog'       => array('Produkty',     'catalog*'),
	'storage'       => array('Magazyny',     'storage*'),
	'user'          => array('Użytkownicy',  'user*'),
	'help'          => array('Pomoc',        ''),
	//'info'          => array('Informacje',   ''),
);

if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['HTTP_X_REWRITE_URL']))
{
	$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
}

function get_menu_item_class($item)
{
	static $chosen = false;

	if ($chosen) return '';

	$len = strlen(ENVIS_BASE_URL) - 1;

	$uri  = explode('/', substr($_SERVER['REQUEST_URI'], $len));
	$item = explode('/', substr($item, $len));

	if (($uri[1] === $item[1]) || (count($uri) === 2))
	{
		$chosen = true;

		return 'menu-current';
	}

	return '';
}

begin_slot('flash');
if (!empty($_SESSION['flash']))
{
  echo '<div id=flashMessage>';
  echo render_message($_SESSION['flash']['message'],
                      $_SESSION['flash']['type'],
                      $_SESSION['flash']['title'],
                      true);
  echo '</div>';

  unset($_SESSION['flash']);
}
replace_slot();

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset=UTF-8>
  <title><?= e($title) ?>enVis</title>
  <link rel="stylesheet" href="<?= url_for_media('jquery-ui/1.8.23/css/smoothness/jquery-ui.css') ?>">
  <link rel="stylesheet" href="<?= url_for_media('common.css', true) ?>">
  <!--[if lt IE 9]>
  <link rel="stylesheet" href="<?= url_for_media('ie.css', true) ?>">
  <![endif]-->
  <?= render_slot('head') ?>
</head>
<body>

<ul id="menu">
<? foreach ($_menu as $url => $item): ?>
  <? if (!$item[1] || is_allowed_to($item[1])): ?>
  <li class="<?= get_menu_item_class(url_for($url)) ?>"><a href="<?= url_for($url) ?>"><?= $item[0] ?></a>
  <? endif ?>
<? endforeach ?>
</ul>

<?= render_slot('submenu', true) ?>
<ul id="submenu" class="empty"></ul>
<?= end_render_slot() ?>

<div id="bd">
<?= render_slot('flash') ?>
<?= $contents ?>
</div>

<div id=walkner>
  <p id=walkner-projects>
    <a href="http://walkner.pl/">walkner</a> |
    <? if (isset($_SESSION['user'])): ?>
    <a href="http://forum.walkner.pl/">forum</a> |
    <? endif ?>
    <a href="http://envis.walkner.pl/">envis</a> |
    <a href="http://infracheck.walkner.pl/">infracheck</a>
  <? if (isset($_SESSION['user'])): ?>
  <p id=walkner-user>
    <a href="<?= url_for('user/view.php') ?>"><?= $_SESSION['user']->getName() ?></a> |
    <a href="<?= url_for('user/logout.php') ?>">wyloguj się</a>
  <? endif ?>
  <p>&copy; Walkner elektronika przemysłowa Zbigniew Walukiewicz
</div>

<script src="<?= url_for_media('jquery/1.8.1/jquery.min.js') ?>"></script>
<script src="<?= url_for_media('jquery-ui/1.8.23/js/jquery-ui.min.js') ?>"></script>
<script src="<?= url_for_media('main.js.php', true) ?>"></script>
<?= render_slot('js') ?>
