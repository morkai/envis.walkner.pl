<?php

include_once __DIR__ . '/../_common.php';

function help_get_parent($parent)
{
  return empty($parent) ? 'IS NULL' : (' = ' . (int)$parent);
}

function help_fetch_page($id)
{
  $page = fetch_one('SELECT h.id, h.title, h.contents, (SELECT COUNT(*) FROM help h2 WHERE h2.parent=:id) AS children FROM help h WHERE h.id=:id', array('id' => $id));

  if (empty($page)) return null;

  $page->tags = fetch_array('SELECT tag AS `key`, 1 AS `value` FROM help_tags WHERE page=:page ORDER BY tag', array(':page' => $id));

  $page->related = array();

  if (!empty($page->tags))
  {
    $page->related = fetch_all(sprintf(
      "SELECT h.id, h.title FROM help_tags t INNER JOIN help h ON h.id=t.page WHERE h.id <> :id AND t.tag IN('%s') GROUP BY h.id ORDER BY h.title", implode("', '", array_keys($page->tags))
    ), array(':id' => $id));
  }

  return $page;
}

function help_render_page($page)
{
?>
<? if (!empty($page->contents)): ?>
<div id="page-contents"><?= $page->contents ?></div>
<? endif ?>
<? if (!empty($page->related)): ?>
<div class="section">
  <h2>Powiązane</h2>
  <ul>
    <? foreach ($page->related as $rpage): ?>
    <li><a data-id="<?= $rpage->id ?>" href="?id=<?= $rpage->id ?>"><?= escape($rpage->title) ?></a>
    <? endforeach ?>
  </ul>
</div>
<? endif ?>
<? if (!empty($page->tags)): ?>
<div class="section">
  <h2>Tagi</h2>
  <ul id="tags">
    <? foreach ($page->tags as $tag => $_): ?>
    <li><a href="<?= url_for('help/?tag=' . $tag) ?>"><?= $tag ?></a>
    <? endforeach ?>
  </ul>
</div>
<? endif ?>
<?php
}
