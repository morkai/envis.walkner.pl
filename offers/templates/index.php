<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('offers/templates');

$templates = fetch_offer_templates();

?>

<? begin_slot('head') ?>
<style>
  .tabs .block-header {
    padding: 0;
  }
  .tabs .block-header li {
    margin: 0;
    display: inline-block;
    width: auto;
  }
  .tabs .block-header h1 {
    margin: 0;
  }
  .tabs .block-header a {
    display: block;
    text-decoration: none;
	  border-right: 1px solid #BBB;
    padding: .3em .5em;
  }
  .tabs .block-header a:hover {
    background: #F60;
    color: #FFF;
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FF9944', endColorstr='#FF5500');
    background: -o-gradient(linear, left top, left bottom, from(#F94), to(#F50));
    background: -webkit-gradient(linear, left top, left bottom, from(#F94), to(#F50));
    background: -moz-linear-gradient(top,  #F94,  #F50);
    text-shadow: #000 0 1px 0;
  }
  .tabs .block-name.tab-active a {
    color: #FFF;
    background: #246;
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#6688AA', endColorstr='#224466');
    background: -o-gradient(linear, left top, left bottom, from(#68A), to(#246));
    background: -webkit-gradient(linear, left top, left bottom, from(#68A), to(#246));
    background: -moz-linear-gradient(top,  #68A,  #246);
    text-shadow: #000 0 1px 0;
  }
  .tabs .block-header li:first-child a {
    border-radius: .3em 0 0 0;
  }
  .tabs .block-body {
    display: none;
  }
  .tabs .block-body.tab-active {
    display: block;
    border-radius: 0 0 .5em .5em;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
  $(function() {
    $('.deleteTemplate').hide();

    $('.tabs li a').click(function() {
      $('.block-body.tab-active, .block-name.tab-active').removeClass('tab-active');
      $(this).parent().addClass('tab-active');
      $(this.href.substring(this.href.indexOf('#'))).addClass('tab-active').find('textarea, input').first().focus();
      
      return false;
    });

    $('.templates').change(function() {
      var $fields = $(this).closest('.form-fields'),
          $option = $(this[this.selectedIndex]),
          tpl     = JSON.parse($option.attr('data-template')),
          id      = this.value;

      tpl.name = $option.text();

      for (var field in tpl) {
        $('[name="template[' + field + ']"]', $fields).val(tpl[field]);
      }

      $fields.find('.deleteTemplate')[id == 0 ? 'hide' : 'show']();
    });
  });
</script>
<? append_slot() ?>

<? decorate("Szablony ofert") ?>

<div class="block tabs">
  <ul class="block-header aside">
    <li><h1 class="block-name tab-active"><a href="#clients">Klienci</a></h1>
    <li><h1 class="block-name"><a href="#intros">Uzgodnienia wstępne</a></h1>
    <li><h1 class="block-name"><a href="#outros">Uzgodnienia końcowe</a></h1>
  </ul>
  <div id="clients" class="block-body tab-active">
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