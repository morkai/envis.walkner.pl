<?php

if (file_exists(__DIR__ . '/_config.prod.php'))
{
  include_once __DIR__ . '/_config.prod.php';
}
else
{
  include_once __DIR__ . '/_config.php';
}

function url_for($href, $abs = false)
{
  $url = '';

  if ($abs)
  {
    $url = (empty($_SERVER['REQUEST_SCHEME']) ? ENVIS_URL_SCHEME : $_SERVER['REQUEST_SCHEME'])
      . '://' . ENVIS_DOMAIN;
  }

  return $url . '/' . ltrim((strpos($href, ENVIS_BASE_URL) === 0 ? '' : ENVIS_BASE_URL) . ltrim($href, '/'), '/');
}

function url_for_media($href, $local = false)
{
  if ($local)
  {
    return url_for('_static_/' . ltrim($href, '/'));
  }

  return ENVIS_MEDIA_URL . ltrim($href, '/');
}
