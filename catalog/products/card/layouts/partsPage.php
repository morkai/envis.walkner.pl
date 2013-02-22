<?php

$data = (object)(empty($page->contents) ? array() : json_decode($page->contents));

if (empty($data->src))
{
  $maxWidth = 717;
  $maxHeight = 900 - 40;

  $data->src = $product->imageFile;

  if (empty($data->width) || empty($data->height))
  {
    list ($width, $height) = getimagesize($_SERVER['DOCUMENT_ROOT'] . $product->imageFile);
  }
  else
  {
    $width = $data->width;
    $height = $data->height;
  }

  if ($width > $maxWidth)
  {
    $ratio = $width / $maxWidth;
    $width = $maxWidth;
    $height /= $ratio;
  }

  if ($height > $maxHeight)
  {
    $ratio = $height / $maxHeight;
    $height = $maxHeight;
    $width /= $ratio;
  }

  $data->width = floor($width);
  $data->height = floor($height);
}

if (empty($data->markers))
{
  $data->markers = array();
}

?>
<div class="page <?= $canManageProducts ? 'editable' : '' ?>" data-page="<?= $page->id ?>" data-layout="partsPage">
  <? include __DIR__ . '/_hd.php' ?>
  <div class="contentsContainer">
    <div class="contents">
      <h1>Rozmieszczenie części</h1>
      <div class="partsContainer">
        <div class="partsCanvas" style="width: <?= $data->width ?>px; height: <?= $data->height ?>px">
          <img class="partsImage" src="<?= $data->src ?>" alt="Kliknij tutaj, aby wybrać obraz!">
          <input class="partsImageFile" type="file" accept="image/*">
          <? foreach ($data->markers as $marker): ?>
          <span class="partsMarker" style="top: <?= $marker->top ?>px; left: <?= $marker->left ?>px;"><?= $marker->nr ?></span>
          <? endforeach ?>
        </div>
      </div>
    </div>
  </div>
  <? include __DIR__ . '/_ft.php' ?>
</div>
