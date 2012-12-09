<?php

include_once __DIR__ . '/../_common.php';

$term = empty($_GET['term']) ? '' : (string)$_GET['term'];

bad_request_if(empty($term));

$categories = fetch_all('SELECT id, name FROM catalog_categories WHERE name LIKE :term ORDER BY parent, name', array(
  ':term' => "%{$term}%"
));

$results = array();

foreach ($categories as $category)
{
  $value = catalog_render_category_path($category->id, false);
  $value = str_replace('&gt;', '>', $value);

  $results[] = array(
    'id' => $category->id,
    'value' => $value
  );
}

output_json($results);
