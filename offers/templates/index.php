<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('offers/templates');

$templates = fetch_offer_templates();

?>

<? begin_slot('head') ?>
<style>
.ui-tabs .ui-tabs-panel {
  padding: 1em;
}
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('.deleteTemplate').hide();

  $('#offerTemplatesTabs').tabs();

  $('.templates').change(function()
  {
    var $fields = $(this).closest('.form-fields');
    var $option = $(this[this.selectedIndex]);
    var tpl = JSON.parse($option.attr('data-template'));
    var id  = this.value;

    tpl.name = $option.text();

    for (var field in tpl)
    {
      $('[name="template[' + field + ']"]', $fields).val(tpl[field]);
    }

    $fields.find('.deleteTemplate')[id == 0 ? 'hide' : 'show']();
  });
});
</script>
<? append_slot() ?>

<? decorate("Szablony ofert") ?>

<div id="offerTemplatesTabs">
  <ul>
    <li><a href="#clients">Klienci</a>
    <li><a href="#intros">Uzgodnienia wstępne</a>
    <li><a href="#outros">Uzgodnienia końcowe</a>
  </ul>
  <div id="clients" class="block-body">
    <form method="post" action="save.php?type=client">
      <ol class="form-fields">
        <li><? render_offer_templates($templates, 'client') ?>
        <li>
          <?= label('templateClientName', 'Nazwa*') ?>
          <input id="templateClientName" name="template[name]" type="text">
        </li>
        <li>
          <?= label('templateClientName', 'Klient') ?>
          <textarea id="templateClientName" name="template[clientName]"></textarea>
        </li>
        <li>
          <?= label('templateClientContact', 'Osoba kontaktowa') ?>
          <textarea id="templateClientContact" name="template[clientContact]"></textarea>
        </li>
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Zapisz szablon"></li>
            <li class="deleteTemplate"><input type="submit" name="template[delete]" value="Usuń szablon"></li>
          </ol>
        </li>
      </ol>
    </form>
  </div>
  <div id="intros" class="block-body">
    <form method="post" action="save.php?type=intro">
      <ol class="form-fields">
        <li><? render_offer_templates($templates, 'intro') ?>
        <li>
          <?= label('templateIntroName', 'Nazwa*') ?>
          <input id="templateIntroName" name="template[name]" type="text">
        </li>
        <li>
          <?= label('templateIntro', 'Tekst uzgodnień wstępnych') ?>
          <textarea id="templateIntro" class="markdown resizable" name="template[intro]"></textarea>
        </li>
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Zapisz szablon"></li>
            <li class="deleteTemplate"><input type="submit" name="template[delete]" value="Usuń szablon"></li>
          </ol>
        </li>
      </ol>
    </form>
  </div>
  <div id="outros" class="block-body">
    <form method="post" action="save.php?type=outro">
      <ol class="form-fields">
        <li><? render_offer_templates($templates, 'outro') ?>
        <li>
          <?= label('templateOutroName', 'Nazwa*') ?>
          <input id="templateOutroName" name="template[name]" type="text">
        </li>
        <li>
          <?= label('templateOutro', 'Tekst uzgodnień końcowych') ?>
          <textarea id="templateOutro" class="markdown resizable" name="template[outro]"></textarea>
        </li>
        <li>
          <ol class="form-actions">
            <li><input type="submit" value="Zapisz szablon"></li>
            <li class="deleteTemplate"><input type="submit" name="template[delete]" value="Usuń szablon"></li>
          </ol>
        </li>
      </ol>
    </form>
  </div>
</div>
