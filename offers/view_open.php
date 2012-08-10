<?php

$templates = fetch_offer_templates();

?>

<? begin_slot('head') ?>
<style>
  .number { text-align: right; }
  #items { margin-top: 1em; }
  #items th { border-bottom: 1px solid #246; }
  #items > tbody > tr:first-child > td { border-top-width: 1px; }
  #items td, #items th { font-size: 1em; padding: .5em .25em }
  #items tbody tr:last-child td { border-bottom: 0; }
  .item-position { width: 1%; cursor: n-resize; }
  #newItem .item-position { cursor: default; }
  .item-description textarea { height: 3.5em; }
  .item-quantity { width: 6%; }
  .item-unit { width: 5%; }
  .item-currency { width: 5%; }
  .item-price { width: 10%; }
  .item-per { width: 5%; }
  .item-vat { width: 5%; }
  .item-actions { width: 5%; }
  .item-actions input { width: 100%; }
  .ui-sortable-helper { opacity: .8; }
  .templates { width: 100%; }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script>
  $(function()
  {
    var newItemDefaults = {},
        newItemTpl      = $('#newItemTpl').html(),
        $items          = $('#items'),
        dirty           = false;

    $(window).unload(function(e)
    {
      if (dirty)
      {
        if (confirm('Opuszczasz stronę oferty bez zapisania dokonanych w niej zmian.\nCzy chcesz zapisać zmiany?'))
        {
          $.ajax({
            url: '<?= url_for("offers/edit.php?id={$offer->id}") ?>',
            data: $('#offer').serialize(),
            type: 'POST',
            async: false
          });
        }
      }
    });

    $items.find('tbody').sortable({
      handle: '.item-position',
      items: '> .item',
      cancel: false,
      update: recount
    });

    $('input, textarea').change(function() { dirty = true; });

    $('#newItem').find('input, textarea').each(function()
    {
      newItemDefaults[this.id] = this.value;
    });

    $('#addNewItem').click(function()
    {
      dirty = true;

      var fields = ['description',
                    'quantity',
                    'unit',
                    'currency',
                    'price',
                    'per',
                    'vat'],
          count  = $items.find('.item').length,
          html   = newItemTpl.replace(/\{i\}/g, count)
                             .replace(/\{position\}/g, count + 1);

      fields.forEach(function(field)
      {
        html = html.replace('{' + field + '}',
                            document.getElementById('item-' + field).value);
      });

      $(html).hide().insertBefore($('#newItem')).fadeIn();

      for (var id in newItemDefaults)
      {
        document.getElementById(id).value = newItemDefaults[id];
      }

      $('#item-description').focus();
    });

    $('.removeItem').live('click', function()
    {
      $(this).closest('tr').fadeOut(function()
      {
        $(this).remove();
        recount();
      });
    });

    $('.item-quantity input').live('blur', function()
    {
      var value = parseFloat($.trim(this.value));

      this.value = isNaN(value) || value <= 0 ? 1 : value;
    });

    $('.item-per input').live('blur', function()
    {
      var value = parseInt($.trim(this.value));

      this.value = isNaN(value) || value < 1 ? 1 : value;
    });

    $('.item-currency input').live('blur', function()
    {
      this.value = $.trim(this.value).replace(/[^a-z]/ig, '')
                                     .toUpperCase()
                                     .substr(0, 3);
    });

    $('.item-price input').live('blur', function()
    {
      var value = parseFloat($.trim(this.value).replace(',', '.')
                                               .replace(/[^0-9\.]/g, ''));

      this.value = isNaN(value) || value < 0
                 ? '0.00' : (Math.floor(value * 100) / 100).toFixed(2);
    });

    $('.item-vat input').live('blur', function()
    {
      var value = parseInt($.trim(this.value));

      if (isNaN(value) || value < 0)
      {
        value = 0;
      }
      else if (value > 100)
      {
        value = 100;
      }

      this.value = value;
    });

    $('#templatesClient').change(function()
    {
      var tpl = JSON.parse($(this[this.selectedIndex]).attr('data-template'));

      $('#offerClient').val(tpl.clientName);
      $('#offerClientContact').val(tpl.clientContact);
    });

    $('#templatesIntro').change(function()
    {
      var tpl = JSON.parse($(this[this.selectedIndex]).attr('data-template'));

      $('#offerIntro').val(tpl.intro);
    });

    $('#templatesOutro').change(function()
    {
      var tpl = JSON.parse($(this[this.selectedIndex]).attr('data-template'));

      $('#offerOutro').val(tpl.outro);
    });

    $('#offer').submit(function() { dirty = false; });

    function recount()
    {
      var pos = 1;

      $items.find('.item .item-position').each(function()
      {
        this.childNodes[0].nodeValue = pos + '.';
        this.childNodes[1].value = pos;

        ++pos;
      });
    }
  });
</script>
<? append_slot() ?>

<? decorate("Oferty") ?>

<form id="offer" method="post" action="<?= url_for("offers/edit.php?id={$offer->id}") ?>">
  <div class="block">
    <div class="block-header">
      <input id="offerTitle" name="offer[title]" class="value" type="text" value="<?= e($offer->title) ?>">
    </div>
    <div class="block-body">
      <ol class="form-fields">
        <li>
          <?= label('offerNumber', 'Numer dokumentu') ?>
          <p><?= e($offer->number) ?></p>
        <li>
          <div class="yui-g">
            <div class="yui-u first">
              <?= label('offerSupplier', 'Dostawca') ?>
              <textarea id="offerSupplier" name="offer[supplier]" class="resizable"><?= e($offer->supplier) ?></textarea>
            </div>
            <div class="yui-u">
              <?= label('offerSupplierContact', 'Kontakt po stronie dostawcy') ?>
              <textarea id="offerSupplierContact" name="offer[supplierContact]" class="resizable"><?= e($offer->supplierContact) ?></textarea>
            </div>
          </div>
        <li>
          <div class="yui-g">
            <div class="yui-u first">
              <?= label('offerClient', 'Klient') ?>
            </div>
            <div class="yui-u">
              <?= label('offerClientContact', 'Kontakt po stronie klienta') ?>
            </div>
          </div>
          <? render_offer_templates($templates, 'client') ?>
          <div class="yui-g">
            <div class="yui-u first">
              <textarea id="offerClient" name="offer[client]" class="resizable"><?= e($offer->client) ?></textarea>
            </div>
            <div class="yui-u">
              <textarea id="offerClientContact" name="offer[clientContact]" class="resizable"><?= e($offer->clientContact) ?></textarea>
            </div>
          </div>
        <li>
          <?= label('offerIntro', 'Uzgodnienia wstępne') ?>
          <? render_offer_templates($templates, 'intro') ?>
          <textarea id="offerIntro" name="offer[intro]" class="markdown resizable"><?= e($offer->intro) ?></textarea>
        <li>
          <?= label('offerAddItem', 'Przedmioty') ?>
          <table id="items">
            <thead>
              <tr>
                <th>Lp.
                <th><label for="item-description">Opis</label>
                <th><label for="item-quantity">Ilość</label>
                <th><label for="item-unit">Jednostka</label>
                <th><label for="item-currency">Waluta</label>
                <th><label for="item-price">Cena</label>
                <th><label for="item-per">Za</label>
                <th><label for="item-vat">VAT</label>
                <th>Akcje
            <tbody>
              <? foreach ($offer->items as $i => $item): ?>
              <tr class="item">
                <td class="item-position">
                  <?= $item->position ?>.
                  <input name="item[<?= $i ?>][position]" type="hidden" value="<?= $item->position ?>">
                <td class="item-description"><textarea name="item[<?= $i ?>][description]"><?= e($item->description) ?></textarea>
                <td class="item-quantity" style="width: 5%"><input name="item[<?= $i ?>][quantity]" type="text" value="<?= (float)$item->quantity ?>" class="number" maxlength="10">
                <td class="item-unit" style="width: 5%"><input name="item[<?= $i ?>][unit]" type="text" value="<?= e($item->unit) ?>" maxlength="10">
                <td class="item-currency" style="width: 5%"><input name="item[<?= $i ?>][currency]" type="text" value="<?= $item->currency ?>" maxlength="3">
                <td class="item-price" style="width: 10%"><input name="item[<?= $i ?>][price]" type="text" value="<?= $item->price ?>" class="number">
                <td class="item-per" style="width: 5%"><input name="item[<?= $i ?>][per]" type="text" value="<?= $item->per ?>" class="number">
                <td class="item-vat" style="width: 5%"><input name="item[<?= $i ?>][vat]" type="text" value="<?= $item->vat ?>" class="number" maxlength="2">
                <td class="item-actions" style="width: 5%"><input class="removeItem" type="button" value=" x " title="Usuń przedmiot">
              <? endforeach ?>
              <tr id="newItem">
                <td class="item-position">
                <td class="item-description"><textarea id="item-description"></textarea>
                <td class="item-quantity"><input id="item-quantity" type="text" value="1" class="number" maxlength="10">
                <td class="item-unit"><input id="item-unit" type="text" value="szt." maxlength="10">
                <td class="item-currency"><input id="item-currency" type="text" value="PLN" maxlength="3">
                <td class="item-price"><input id="item-price" type="text" value="0.00" class="number">
                <td class="item-per"><input id="item-per" type="text" value="1" class="number">
                <td class="item-vat"><input id="item-vat" type="text" value="23" class="number" maxlength="2">
                <td class="item-actions"><input id="addNewItem" type="button" value=" + " title="Dodaj przedmiot">
          </table>
        <li>
          <?= label('offerOutro', 'Uzgodnienia końcowe') ?>
          <? render_offer_templates($templates, 'outro') ?>
          <textarea id="offerOutro" name="offer[outro]" class="markdown resizable"><?= e($offer->outro) ?></textarea>
        <li>
					<input type="submit" value="Aktualizuj ofertę">
      </ol>
    </div>
  </div>
</form>
<script id="newItemTpl" type="text/html">
  <tr class="item">
    <td class="item-position">
      {position}.
      <input name="item[{i}][position]" type="hidden" value="{position}">
    <td class="item-description"><textarea name="item[{i}][description]">{description}</textarea>
    <td class="item-quantity"><input name="item[{i}][quantity]" type="text" value="{quantity}" class="number" maxlength="10">
    <td class="item-unit"><input name="item[{i}][unit]" type="text" value="{unit}" maxlength="10">
    <td class="item-currency"><input name="item[{i}][currency]" type="text" value="{currency}" maxlength="3">
    <td class="item-price"><input name="item[{i}][price]" type="text" value="{price}" class="number">
    <td class="item-per"><input name="item[{i}][per]" type="text" value="{per}" class="number">
    <td class="item-vat"><input name="item[{i}][vat]" type="text" value="{vat}" class="number" maxlength="2">
    <td class="item-actions"><input class="removeItem" type="button" value=" x " title="Usuń przedmiot">
</script>
