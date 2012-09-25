<?php

include __DIR__ . '/../_common.php';

no_access_if_not_allowed('catalog/manage');

if (!empty($_POST))
{
  bad_request_if(empty($_POST['page']) || empty($_POST['template']));

  $db = get_conn();

  try
  {
    $db->beginTransaction();

    $template = fetch_one('SELECT id, contents FROM catalog_card_templates WHERE id=? LIMIT 1', array(1 => $_POST['template']));

    not_found_if(empty($template));

    $page = fetch_one('SELECT id FROM catalog_card_pages WHERE id=? LIMIT 1', array(1 => $_POST['page']));

    not_found_if(empty($page));

    exec_update('catalog_card_pages', array('contents' => $template->contents), "id={$page->id}");

    $db->commit();
  }
  catch (PDOException $x)
  {
    $db->rollBack();

    internal_server_error($x->getMessage());
  }

  echo $template->contents;
  exit;
}

$templates = fetch_array('SELECT id AS `key`, name AS value FROM catalog_card_templates ORDER BY name');

?>
<p>Wybierz szablon zawartości do zaimportowania.</p>
<p>Zaimportowanie wybranego szablonu nadpisze aktualną zawartość na danej stronie!</p>
<p>
  <label for="importTemplateId">Szablon do zaimportowania:</label>
  <select id="importTemplateId">
    <?= render_options($templates) ?>
  </select>
</p>
