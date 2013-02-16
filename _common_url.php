<?php

include_once __DIR__ . '/_config.php';

function url_for($href, $abs = false)
{
  $url = '';

  if ($abs)
  {
    $url = 'http';

    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] !== 'off'))
    {
      $url .= 's';
    }

    $url .= '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ENVIS_DOMAIN);
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
