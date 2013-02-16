<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('service/declare');

$referer = get_referer("service/");
$errors = array();

$templateOptions = fetch_array('SELECT id AS `key`, name AS `value` FROM declaration_templates ORDER BY name');

?>

<? begin_slot('head') ?>
<style>
  #templateEdit,
  #templateDelete
  {
    display: none;
  }
  #cke_templateCode
  {
    border: 0;
    padding: 0;
  }
  #cke_templateCode td
  {
    border-top: none;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script src="<?= url_for_media('ckeditor/3.6.2/ckeditor.js') ?>"></script>
<script>
$(function()
{
  CKEDITOR.replace('templateCode', {
    toolbar: [
      { name: 'document',    items : [ 'Source' ] },
      { name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
      { name: 'editing',     items : [ 'Find','Replace','-','SelectAll' ] },
      '/',
      { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
      { name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
      { name: 'links',       items : [ 'Link','Unlink','Anchor' ] },
      { name: 'insert',      items : [ 'Image','Table','HorizontalRule','SpecialChar','PageBreak' ] },
      '/',
      { name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] },
      { name: 'colors',      items : [ 'TextColor','BGColor' ] },
      { name: 'tools',       items : [ 'Maximize', 'ShowBlocks','-','About' ] }
    ]
  });

  $('#templateId').change(function()
  {
    var me = this;

    if (me.value == 0)
    {
      me.form.reset();

      CKEDITOR.instances.templateCode.setData('');

      $('#templateAdd').fadeIn();
      $('#templateEdit').hide();
      $('#templateDelete').hide();
    }
    else
    {
      me.disabled = true;

      $.ajax({
        url: '<?= url_for('service/declarations/fetch.php') ?>',
        data: {id: me.value},
        success: function(template)
        {
          $('#templateName').val(template.name);
          $('#templatePattern').val(template.pattern);

          CKEDITOR.instances.templateCode.setData(template.code);
        },
        complete: function()
        {
          me.disabled = false;

        $('#templateAdd').hide();
        $('#templateEdit').fadeIn();
        $('#templateDelete').fadeIn();
        }
      });
    }
  });
});
</script>
<? append_slot() ?>

<? decorate("Szablony deklaracji zgodności") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Szablony deklaracji zgodności</h1>
  </div>
  <div class="block-body">
    <form id="issue" class="form" method="post" action="<?= url_for("service/declarations/act.php") ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Szablony deklaracji zgodności</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <?= label('templateId', 'Szablon') ?>
            <select id="templateId" name="template[id]" autofocus>
              <option value=0 selected>Nowy szablon...
              <option value=0>
              <?= render_options($templateOptions) ?>
            </select>
          <li>
            <?= label('templateName', 'Nazwa*') ?>
            <input id="templateName" name="template[name]" type="text" maxlength="200" value="">
          <li>
            <?= label('templatePattern', 'Domyślny, jeżeli w temacie zgłoszenia występuje') ?>
            <input id="templatePattern" name="template[pattern]" type="text" maxlength="200" value="">
          <li>
            <?= label('templateCode', 'Kod') ?>
            <textarea id=templateCode name=template[code]></textarea>
          <li>
            <ol class="form-actions">
              <li><input id=templateAdd type="submit" name=add value="Dodaj szablon">
              <li><input id=templateEdit type="submit" name=edit value="Edytuj szablon">
              <li><input id=templateDelete type="submit" name=delete value="Usuń szablon">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
