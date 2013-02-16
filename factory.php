<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('vis/factory');

no_access_if(!has_access_to_factory($_GET['id']));

$factory = fetch_one('SELECT v.*, f.* FROM factories f LEFT JOIN vis_factories v ON v.factory=f.id WHERE f.id=?', array(1 => $_GET['id']));

not_found_if(empty($factory));

if ($factory->factory === null)
{
  exec_stmt('INSERT INTO vis_factories SET factory=?', array(1 => $_GET['id']));

  $factory->width = 640;
  $factory->height = 480;
  $factory->fg_color = 'black';
  $factory->bg_color = 'transparent';
  $factory->bg_image = 'none';
  $factory->bg_position = 'center center';
  $factory->bg_repeat = 'no-repeat';
}

$machines = fetch_all('SELECT v.*, m.* FROM machines m LEFT JOIN vis_factory_machines v ON v.machine=m.id WHERE m.factory=?', array(1 => $_GET['id']));

escape_vars($factory->bg_color, $factory->bg_image, $factory->bg_position, $factory->bg_repeat);

$bgPos = array(
  'center center' => 'środek',
  'top left' => 'lewy górny róg',
  'top right' => 'prawy górny róg',
  'bottom left' => 'lewy dolny róg',
  'bottom right' => 'prawy dolny róg'
);

$bgRepeat = array(
  'no-repeat' => 'bez powtórzeń',
  'repeat' => 'z powtórzeniami',
  'repeat-x' => 'z powtórzeniami w poziomie',
  'repeat-y' => 'z powtórzeniami w pionie'
);

if (file_exists(dirname(__FILE__) . ENVIS_UPLOADS_DIR . '/vis-factory-bg/' . $factory->bg_image))
{
  $factory->bg_image = 'url(' . url_for(ENVIS_UPLOADS_DIR . '/vis-factory-bg/' . $factory->bg_image) . ')';
}
else
{
  $factory->bg_image = 'none';
}

$machineImgPath = url_for(ENVIS_UPLOADS_DIR . '/vis-machine/');

$canEdit = is_allowed_to('vis/factory/edit');
$editMode = !empty($_GET['edit']) && $canEdit;

$showLink = !$editMode && is_allowed_to('vis/machine');

?>
<? begin_slot('head') ?>
<? if ($editMode): ?>
<link rel="stylesheet" href="<?= url_for_media('jquery-plugins/uploadify/2.0.3/uploadify.css') ?>">
<link rel="stylesheet" href="<?= url_for_media('jquery-plugins/colorpicker/2009.05.23/css/colorpicker.css') ?>">
<? endif ?>
<style>
  .container { text-align: center; }
  #vis
  {
    position: relative;
    text-align: left;
    margin: 0 auto 1em auto;
    border: 1px solid #246;
    width: <?= $factory->width ?>px;
    height: <?= $factory->height ?>px;
    color: <?= $factory->fg_color ?>;
    background-color: <?= $factory->bg_color ?>;
    background-image: <?= $factory->bg_image ?>;
    background-position: <?= $factory->bg_position ?>;
    background-repeat: <?= $factory->bg_repeat ?>;
  }
  .machine
  {
    position: absolute;
    text-align: center;
  }
  .machine h3
  {
    margin: 0;
  }
</style>
<? if ($editMode): ?>
<style>
  .action { display: none; }
  .colorpicker { z-index: 1010; }
  .setBg-image-choice, .setImg-image-choice { display: none; }
  .edit .machine img { cursor: move; }
  #conmenu
  {
    z-index: 2000;
  }
  #conmenu div
  {
    background: #FFF;
    color: #246;
    padding: 0.25em 1em;
    margin: 0;
    text-align: left;
    cursor: pointer;
    -moz-box-shadow: #246 2px 2px 5px;
    -webkit-box-shadow: #246 2px 2px 5px;
  }
  #conmenu div:hover
  {
    background: #246;
    color: #FFF;
  }
  #conmenu :first-child
  {
    -moz-border-radius: 0.5em 0.5em 0 0;
    -webkit-border-top-left-radius: 0.5em;
    -webkit-border-top-right-radius: 0.5em;
  }
  #conmenu :last-child
  {
    -moz-border-radius-bottomleft: 0.5em;
    -moz-border-radius-bottomright: 0.5em;
    -webkit-border-bottom-left-radius: 0.5em;
    -webkit-border-bottom-right-radius: 0.5em;
  }
</style>
<? endif ?>
<? append_slot() ?>
<? begin_slot('submenu') ?>
<ul id="submenu">
  <? if ($editMode): ?>
  <li><a href="<?= url_for('factory.php?id=' . $factory->id) ?>">Wyłącz tryb edytowania</a>
  <? elseif ($canEdit): ?>
  <li><a href="<?= url_for('factory.php?edit=1&amp;id=' . $factory->id) ?>">Włącz tryb edytowania</a>
  <? endif ?>
</ul>
<? append_slot() ?>

<? decorate('Wizualizacja fabryki') ?>

<div class="container">
  <h1><?= escape($factory->name) ?></h1>
  <div id="vis" class="<?= $editMode ? 'edit' : '' ?>">
    <? foreach ($machines as $machine): ?>
    <div class="machine" data-id="<?= $machine->id ?>" style="
      top: <?= $machine->top ?>px;
      left: <?= $machine->left ?>px;
      z-index: <?= $machine->zindex ?>;
      color: <?= $machine->fg_color ? $machine->fg_color : 'inherit' ?>;
      background-color: <?= $machine->bg_color ? $machine->bg_color : 'transparent' ?>;
    ">
      <h3><?= escape($machine->name) ?></h3>
      <? if ($showLink && has_access_to_machine($machine->id)): ?><a href="<?= url_for('machine.php?id=' . $machine->id) ?>"><? endif ?>
      <img alt="<? $machine->id ?>"
        src="<?= $machineImgPath . ($machine->image ? $machine->image : 'default.gif') ?>"
        width="<?= $machine->image_width ? $machine->image_width : 200 ?>"
        height="<?= $machine->image_height ? $machine->image_height : 200 ?>"
        style="
          max-width: <?= $machine->image_max_width ? $machine->image_max_width : 400 ?>px;
          max-height: <?= $machine->image_max_height ? $machine->image_max_height : 450 ?>px;
        ">
      <? if ($showLink && has_access_to_machine($machine->id)): ?></a><? endif ?>
    </div>
    <? endforeach ?>
  </div>
</div>
<? if ($editMode): ?>
<div id="setBg" class="block action">
  <div class="block-header">
    <h1 class="block-name">Ustawienia tła</h1>
  </div>
  <div class="block-body">
    <form id="setBg-form" method="post" action="<?= url_for('vis/factory.php') ?>">
      <input type="hidden" name="id" value="<?= $factory->id ?>">
      <fieldset>
        <legend>Ustawienia tła</legend>
        <ol class="form-fields">
          <li>
            <label for="setBg-fg">Kolor czcionki</label>
            <input id="setBg-fg" name="properties[fg_color]" type="text" value="<?= escape($factory->fg_color) ?>">
          <li>
            <label for="setBg-color">Kolor tła</label>
            <input id="setBg-color" name="properties[bg_color]" type="text" value="<?= escape($factory->bg_color) ?>">
          <li>
            <label for="setBg-position">Pozycja obrazka</label>
            <select id="setBg-position" name="properties[bg_position]">
              <?= render_options($bgPos, $factory->bg_position) ?>
            </select>
          <li>
            <label for="setBg-repeat">Powtarzanie obrazka</label>
            <select id="setBg-repeat" name="properties[bg_repeat]">
              <?= render_options($bgRepeat, $factory->bg_repeat) ?>
            </select>
          <li>
            <label for="setBg-image">Nowy obrazek</label>
            <input id="setBg-image" type="file" value="">
            <div class="setBg-image-choice">
              <input id="setBg-image-choice" name="properties[bg_image]" type="checkbox" checked="checked" value=""> <label for="setBg-image-choice"></label>
            </div>
          <li>
            <ol class="form-actions">
              <li><input id="setBg-submit" type="submit" value="Zapisz ustawienia">
              <li><a id="setBg-cancel" href="<?= url_for('factory.php?edit=1&amp;id=' . $_GET['id']) ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
<div id="setImg" class="block action">
  <div class="block-header">
    <h1 class="block-name">Ustawienia obrazka</h1>
  </div>
  <div class="block-body">
    <form id="setImg-form" method="post" action="<?= url_for('vis/machine.php') ?>">
      <input id="setImg-id" type="hidden" name="id" value="">
      <fieldset>
        <legend>Ustawienia obrazka</legend>
        <ol class="form-fields">
          <li>
            <label for="setImg-fg">Kolor czcionki</label>
            <input id="setImg-fg" name="properties[fg_color]" type="text" value="<?= escape($factory->fg_color) ?>">
          <li>
            <label for="setImg-color">Kolor tła</label>
            <input id="setImg-color" name="properties[bg_color]" type="text" value="<?= escape($factory->bg_color) ?>">
          <li>
            <label for="setImg-image">Nowy obrazek</label>
            <input id="setImg-image" type="file" value="">
            <div class="setImg-image-choice">
              <input id="setImg-image-choice" name="properties[image]" type="checkbox" checked="checked" value=""> <label for="setImg-image-choice"></label>
            </div>
          <li>
            <ol class="form-actions">
              <li><input id="setImg-submit" type="submit" value="Zapisz ustawienia">
              <li><a id="setImg-cancel" href="<?= url_for('factory.php?edit=1&amp;id=' . $_GET['id']) ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-plugins/uploadify/2.0.3/swfobject.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/uploadify/2.0.3/jquery.uploadify.min.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/simplemodal/1.3/jquery.simplemodal.min.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/colorpicker/2009.05.23/js/colorpicker.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/conmenu/1.0.1/jquery.conmenu.js') ?>"></script>
<script>
  $(document).ready(function()
  {
    var vis = $('#vis');

    $('#setBg .form-actions a').click(function()
    {
      $.modal.close();

      return false;
    });
    $('#setBg-fg').ColorPicker({
      onSubmit: function(hsb, hex)
      {
        $('#setBg-fg').val('#' + hex).ColorPickerHide();
      }
    });
    $('#setBg-color').ColorPicker({
      onSubmit: function(hsb, hex)
      {
        $('#setBg-color').val('#' + hex).ColorPickerHide();
      }
    });
    $('#setBg-image').uploadify({
      'scriptAccess': 'always',
      'uploader'   : '<?= url_for_media('uploadify/uploadify.swf', true) ?>',
      'script'     : 'http://<?= ENVIS_DOMAIN . url_for('_files_/uploadify.php') ?>',
      'checkScript': 'http://<?= ENVIS_DOMAIN . url_for('_files_/check.php') ?>',
      'cancelImg'  : '<?= url_for_media('jquery-plugins/uploadify/2.0.3/cancel.png') ?>',
      'auto'       : true,
      'folder'     : '<?= ENVIS_UPLOADS_DIR ?>/vis-factory-bg',
      'fileDesc'   : 'Obrazek (png, jpg, gif)',
      'fileExt'    : '*.png;*.jpg;*.jpeg;*.gif',
      'sizeLimit'  : 3145728,
      'buttonText' : 'Wybierz',
      'scriptData' : {id: '<?= (int)$factory->id ?>'},
      onSelect     : function()
      {
        $('#setBg-submit').attr('disabled', 'disabled');
        $('#setBg-cancel').hide();
      },
      onError      : function(e, q, file, error)
      {
        $('#setBg-submit').removeAttr('disabled');
        $('#setBg-cancel').show();
      },
      onComplete: function(event, queueID, file, response, data)
      {
        $('#setBg-image-choice').val('<?= $factory->id ?>_new' + file.type);
        $('label[for="setBg-image-choice"]').text(file.name);
        $('.setBg-image-choice').show();
        $('#setBg-submit').removeAttr('disabled');
        $('#setBg-cancel').show();
      }
    });
    $('#setBg-form').submit(function()
    {
      $('#setBg-submit').attr('disabled', 'disabled');
      $('#setBg-cancel').hide();

      $.post('<?= url_for('vis/factory.php') ?>', $(this).serialize(), function(image)
      {
        vis
          .css('color', $('#setBg-fg').val())
          .css('background-color', $('#setBg-color').val())
          .css('background-position', $('#setBg-position').val())
          .css('background-repeat', $('#setBg-repeat').val());

        if (image.length > 4) vis.css('background-image', image);

        $('.setBg-image-choice').hide();

        $('#setBg-submit').removeAttr('disabled');
        $('#setBg-cancel').show();

        $.modal.close();
      }, 'text');

      return false;
    });

    vis.resizable({
      minWidth: 200,
      minHeight: 200,
      stop: function(e, ui)
      {
        var data = 'id=<?= (int)$_GET['id'] ?>&properties[width]=' + ui.size.width + '&properties[height]=' + ui.size.height;
        
        $.post('<?= url_for('vis/factory.php') ?>', data);
      }
    });

    $('.machine').draggable({
      containment: 'parent',
      handle: 'img',
      stack: '.machine',
      stop: function(e, ui)
      {
        var zIndex = [];

        $('.machine').each(function(i, el)
        {
          zIndex.push('&zindex[' + $(el).attr('data-id') + ']=' + $(el).css('z-index'));
        });
        
        var data = 'id=' + $(this).attr('data-id') + '&properties[top]=' + ui.position.top + '&properties[left]=' + ui.position.left + zIndex;

        $.post('<?= url_for('vis/factory_machine.php') ?>', data);
      }
    });
    $('.machine img').each(function(i, el)
    {
      el = $(el);

      el.resizable({
        aspectRatio: true,
        containment: '#vis',
        handles: 'se',
        autoHide: true,
        maxWidth: parseInt(el.css('max-width')),
        maxHeight: parseInt(el.css('max-height')),
        stop: function(e, ui)
        {
          var data = 'id=' + $(this).parent().attr('data-id') + '&properties[image_width]=' + ui.size.width + '&properties[image_height]=' + ui.size.height;
          
          $.post('<?= url_for('vis/factory_machine.php') ?>', data);
        }
      })
    });

    var machine;

    $.conmenu({
      selector: vis,
      choices: [{
        label: 'Ustawienia t\u0142a',
        action: function(el)
        {
          modal('#setBg', {persist: true});
        }
      }]
    });

    $.conmenu({
      selector: '#vis .machine',
      choices: [
        {
          label: 'Ustawienia obrazka',
          action: function(el)
          {
            machine = $(el);

            $('#setImg-id').val(machine.attr('data-id'));
            $('#setImg-fg').val(machine.css('color'));
            $('#setImg-color').val(machine.css('background-color'));
            
            modal('#setImg', {persist: true});
          }
        }
      ]
    });

    $('#setImg .form-actions a').click(function()
    {
      $.modal.close();

      return false;
    });
    $('#setImg-fg').ColorPicker({
      onSubmit: function(hsb, hex)
      {
        $('#setImg-fg').val('#' + hex).ColorPickerHide();
      }
    });
    $('#setImg-color').ColorPicker({
      onSubmit: function(hsb, hex)
      {
        $('#setImg-color').val('#' + hex).ColorPickerHide();
      }
    });
    $('#setImg-image').uploadify({
      'scriptAccess': 'always',
      'uploader'   : '<?= url_for_media('uploadify/uploadify.swf', true) ?>',
      'script'     : 'http://<?= ENVIS_DOMAIN . url_for('_files_/uploadify.php') ?>',
      'checkScript': 'http://<?= ENVIS_DOMAIN . url_for('_files_/check.php') ?>',
      'cancelImg'  : '<?= url_for_media('jquery-plugins/uploadify/2.0.3/cancel.png') ?>',
      'auto'       : true,
      'folder'     : '<?= ENVIS_UPLOADS_DIR ?>/vis-machine',
      'fileDesc'   : 'Obrazek (png, jpg, gif)',
      'fileExt'    : '*.png;*.jpg;*.jpeg;*.gif',
      'sizeLimit'  : 3145728,
      'buttonText' : 'Wybierz',
      onSelect     : function()
      {
        $('#setImg-image').uploadifySettings('scriptData', {'id': machine.attr('data-id')});
        
        $('#setImg-submit').attr('disabled', 'disabled');
        $('#setImg-cancel').hide();
      },
      onError: function(e, q, file, error)
      {
        $('#setImg-submit').removeAttr('disabled');
        $('#setImg-cancel').show();
      },
      onComplete: function(event, queueID, file, response, data)
      {
        $('#setImg-image-choice').val(machine.attr('data-id') + '_new' + file.type);
        $('label[for="setImg-image-choice"]').text(file.name);
        $('.setImg-image-choice').show();
        $('#setImg-submit').removeAttr('disabled');
        $('#setImg-cancel').show();
      }
    });
    $('#setImg-form').submit(function()
    {
      $('#setImg-submit').attr('disabled', 'disabled');
      $('#setImg-cancel').hide();

      $.post('<?= url_for('vis/factory_machine.php') ?>', $(this).serialize(), function(props)
      {
        machine
          .css('color', $('#setImg-fg').val())
          .css('background-color', $('#setImg-color').val());
        
        if (props && props.src)
        {
          $('img', machine)
            .resizable('option', 'maxWidth', props.width)
            .resizable('option', 'maxHeight', props.height)
            .css('max-width', props.width + 'px')
            .css('max-height', props.height + 'px')
            .css('width', props.width + 'px')
            .css('height', props.height + 'px')
            .attr('src', props.src)
            .attr('width', props.width)
            .attr('height', props.height)
          .parent()
            .css('width', props.width + 'px')
            .css('height', props.height + 'px');
        }

        $('.setImg-image-choice').hide();

        $('#setImg-submit').removeAttr('disabled');
        $('#setImg-cancel').show();

        $.modal.close();
      }, 'json');

      return false;
    });
  });
</script>
<? append_slot() ?>
<? endif ?>
