<?php

$bypassAuth = true;

include_once __DIR__ . '/_common.php';

$parent = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$parent = empty($parent) ? 'IS NULL' : '= ' . (int)$parent;

$query = <<<SQL
SELECT
  h.id,
  h.position,
  h.title,
  (SELECT COUNT(*) FROM help h2 WHERE h2.parent=h.id) AS children
FROM help h
WHERE h.parent $parent
ORDER BY position, title
SQL;

$pages = fetch_all($query);

$result = array();

foreach ($pages as $page)
{
  $data = array('title' => $page->title, 'attributes' => array('data-position' => $page->position));
  $state = null;

  if ($page->children > 0)
  {
    $state = 'closed';
    $data['icon'] = url_for_media('fff/book.png');
  }
  else
  {
    $data['icon'] = url_for_media('fff/page.png');
  }

  $result[] = array(
    'attributes' => array('id' => 'help_' . $page->id),
    'data' => $data,
    'state' => $state,
  );
}

output_json($result);
