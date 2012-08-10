<?php

function move_catalog_subcategories($category)
{
  exec_stmt('UPDATE catalog_categories SET parent=? WHERE parent=?',
            array(1 => $category->parent, $category->id));
}

include '../../_common.php';

if (empty($_REQUEST['id'])) bad_request();

no_access_if_not_allowed('catalog/manage');

$category = fetch_one('SELECT id, parent, name FROM catalog_categories WHERE id=?', array(1 => $_REQUEST['id']));

if (empty($category)) not_found();

if (is('delete'))
{
  $db = get_conn();

  $mode = empty($_REQUEST['mode']) ? 0 : (int)$_REQUEST['mode'];

  try
  {
    $db->beginTransaction();

    if ($mode === 0)
    {
      if ($category->parent)
      {
        move_catalog_subcategories($category);

        exec_stmt('UPDATE catalog_products SET category=? WHERE category=?',
                  array(1 => $category->parent, $category->id));
      }
      else
      {
        move_catalog_subcategories($category);
      }
    }

    exec_stmt('DELETE FROM catalog_categories WHERE id=?', array(1 => $category->id));

    $db->commit();
  }
  catch (PDOException $x)
  {
    $db->rollBack();

    throw $x;
  }

  log_info("Usunięto kategorię <{$category->name}> z katalogu.");

  if (is_ajax())
    output_json(array('success' => true, 'id' => $category->id));

  set_flash("Kategoria <{$category->name}> została usunięta.");

  go_to('catalog');
}

$referer = get_referer('catalog');

$modes = array(
  0 => 'Przenieś podkategorie i produkty do nadrzędnej kategorii',
  1 => 'Usuń wszystkie podkategorie i produkty'
);

if (!$category->parent)
  $modes[0] = 'Przenieś podkategorie, a produkty usuń';

?>

<? decorate('Usuwanie kategorii z katalogu') ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Usuwanie kategorii</h1>
  </div>
  <div class="block-body">
    <form id="deleteCategoryForm" method=post action="<?= url_for("catalog/categories/delete.php?id={$category->id}") ?>">
      <input type="hidden" name="_method" value="DELETE">
      <p>Jesteś pewien że chcesz usunąć kategorię &lt;<?= e($category->name) ?>>?</p>
      <ol class="form-fields">
        <li class="form-choice">
          <?= render_choice('Tryb', 'deleteCategoryMode', 'mode', $modes, 0) ?>
        <li>
          <ol class="form-actions">
            <li><input type=submit value="Usuń kategorię">
            <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
          </ol>
      </ol>
    </form>
  </div>
</div>