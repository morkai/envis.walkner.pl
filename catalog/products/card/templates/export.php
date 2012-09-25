<?php

include __DIR__ . '/../_common.php';

no_access_if_not_allowed('catalog/manage');

if (!empty($_POST))
{
  bad_request_if(empty($_POST['templateId']) && empty($_POST['templateName']));

  $templateId = empty($_POST['templateId']) || !is_numeric($_POST['templateId']) ? 0 : (int)$_POST['templateId'];
  $templateName = empty($_POST['templateName']) ? '' : trim((string)$_POST['templateName']);
  $contents = empty($_POST['contents']) ? '' : trim((string)$_POST['contents']);

  if (empty($templateName))
  {
    exec_update('catalog_card_templates', array('contents' => $contents), "id={$templateId}");
  }
  else
  {
    exec_insert('catalog_card_templates', array(
      'name' => $templateName,
      'contents' => $contents
    ));
  }

  if (is_ajax())
  {
    no_content();
  }
  else
  {
    go_to(get_referer());
  }
}

$templates = fetch_array('SELECT id AS `key`, name AS value FROM catalog_card_templates ORDER BY name');

?>
<p>Wybierz szablon, który chcesz nadpisać lub wpisz nazwę dla nowego szablonu.</p>
<p>
  <label for="exportTemplateId">Szablon do nadpisania:</label>
  <select id="exportTemplateId">
    <?= render_options($templates) ?>
  </select>
</p>
<p>
  <label for="exportTemplateName">Nazwa nowego szablonu:</label>
  <input id="exportTemplateName" type="text" value="">
</p>
