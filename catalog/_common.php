<?php

include_once __DIR__ . '/../_common.php';

function catalog_fetch_categories($categoryId)
{
  $categories = array();

  $stmt = prepare_stmt('SELECT * FROM catalog_categories WHERE id=:id LIMIT 1');

  while ($categoryId)
  {
    exec_stmt($stmt, array(':id' => $categoryId));

    $category = $stmt->fetch(PDO::FETCH_OBJ);

    if (empty($category))
    {
      break;
    }

    $categories[] = $category;
    $categoryId = $category->parent;
  }

  return array_reverse($categories);
}
