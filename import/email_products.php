<?php

$productId = (int)preg_replace('/^[^0-9]+/', '', $subject);
$gallery = preg_match('/(g|gal|galeria|gallery)\s*[0-9]+/i', $subject) === 1;

$product = fetch_one('SELECT id, name FROM catalog_products WHERE id=? LIMIT 1', array(1 => $productId));

if (empty($product))
{
  throw new Exception("Produkt nie istnieje: {$productId}");
}

if (empty($attachments))
{
  throw new Exception("Brak plików do zaimportowania do produktu {$product->id}: {$product->name}");
}

exec_update('catalog_products', array('updatedAt' => time()), "id={$product->id}");

$files = array();
$images = array();

$imageTypes = array(
  'image/gif',
  'image/jpeg',
  'image/png'
);

foreach ($attachments as $attachment)
{
  if ($gallery && in_array($attachment['type'], $imageTypes))
  {
    $images[] = $attachment;
  }
  else
  {
    $files[] = $attachment;
  }
}

$filesDir = ENVIS_UPLOADS_PATH . '/products/';
$replyText = "Dodano";

if (!empty($files))
{
  $replyText .= " " . count($files) . " plików";

  $sql = <<<SQL
INSERT INTO catalog_product_files
SET product=:product,
    uploader=:uploader,
    uploadedAt=:uploadedAt,
    file=:file,
    name=:name
SQL;

  $stmt = prepare_stmt($sql);

  foreach ($files as $attachment)
  {
    $file = md5($attachment['name'] . time() . $from) . '.' . $attachment['ext'];

    if (file_put_contents($filesDir . $file, $attachment['data']) === false)
    {
      continue;
    }

    $name = preg_replace('/\.' . preg_quote($attachment['ext']) . '$/i', '', $attachment['name']);

    exec_stmt($stmt, array(
      ':product' => $product->id,
      ':uploader' => $user->id,
      ':uploadedAt' => time(),
      ':file' => $file,
      ':name' => $name
    ));
  }
}

if (!empty($images))
{
  if (!empty($files))
  {
    $replyText .= " i";
  }

  $replyText .= " " . count($images) . " obrazów";

  $sql = <<<SQL
INSERT INTO catalog_product_images
SET product=:product,
    file=:file,
    description=:description
SQL;

  $stmt = prepare_stmt($sql);

  foreach ($images as $attachment)
  {
    $file = md5($attachment['name'] . time() . $from) . '.' . $attachment['ext'];

    if (file_put_contents($filesDir . $file, $attachment['data']) === false)
    {
      continue;
    }

    WideImage::loadFromFile($filesDir . $file)
      ->resize(1920, 1080, 'inside', 'down')
      ->saveToFile($filesDir . $file);

    $name = preg_replace('/\.' . preg_quote($attachment['ext']) . '$/i', '', $attachment['name']);

    exec_stmt($stmt, array(
      ':product' => $product->id,
      ':file' => $file,
      ':description' => $name
    ));
  }
}

$productUrl = url_for('/catalog/?product=' . $product->id, true);
$replyText .= " do produktu {$product->id}: {$product->name}\r\n${productUrl}";
