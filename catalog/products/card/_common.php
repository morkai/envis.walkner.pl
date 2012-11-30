<?php

include_once __DIR__ . '/../../_common.php';

/**
 * @return array
 */
function catalog_get_card_layouts()
{
  $layouts = include __DIR__ . '/layouts/definitions.php';

  foreach ($layouts as $name => $layout)
  {
    $layout->templateFile = __DIR__ . '/layouts/' . $name . '.php';
  }

  return $layouts;
}

/**
 * @param int $product
 * @return Object|null
 */
function catalog_get_card_product($product)
{
  $q = <<<SQL
SELECT
  p.*,
  i.file AS imageFile,
  c.name AS categoryName
FROM catalog_products p
LEFT JOIN catalog_product_images i ON i.id=p.image
LEFT JOIN catalog_categories c ON c.id=p.category
WHERE p.id=:id
LIMIT 1
SQL;

  $product = fetch_one($q, array(':id' => $product));

  if (empty($product))
  {
    return null;
  }

  $product->images = fetch_all('SELECT * FROM catalog_product_images WHERE product=?', array(1 => $product->id));

  if (empty($product->imageFile))
  {
    if (empty($product->images))
    {
      $product->imageFile = url_for_media('/img/350x197.gif', true);
    }
    else
    {
      $product->imageFile = url_for('_files_/products/' . $product->images[0]->file);
    }
  }
  else
  {
    $product->imageFile = url_for('_files_/products/' . $product->imageFile);
  }

  return $product;
}
