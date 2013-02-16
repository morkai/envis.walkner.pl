<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_GET['id']));

no_access_if_not_allowed('vis/machine');

no_access_if(!has_access_to_machine($_GET['id']));

$machine = fetch_one('SELECT v.*, m.*, f.name AS factoryName FROM machines m LEFT JOIN vis_machines v ON v.machine=m.id INNER JOIN factories f ON f.id=m.factory WHERE m.id=?', array(1 => $_GET['id']));

not_found_if(empty($machine));

if ($machine->machine === null)
{
  exec_stmt('INSERT INTO vis_machines SET machine=?', array(1 => $_GET['id']));

  $machine->width = 640;
  $machine->height = 480;
  $machine->fg_color = 'black';
  $machine->bg_color = 'transparent';
  $machine->bg_image = 'none';
  $machine->bg_position = 'center center';
  $machine->bg_repeat = 'no-repeat';
}

$deviceImgPath = url_for(ENVIS_UPLOADS_DIR . '/vis-device/');

$devices = fetch_all('SELECT v.*, d.* FROM engines d LEFT JOIN vis_machine_devices v ON v.machine=d.machine AND v.device=d.id WHERE d.machine=?', array(1 => $_GET['id']));

$varStmt = prepare_stmt('
SELECT
  var.name,
  var.id,
  (SELECT val.value FROM `values` val WHERE val.variable=dv.variable AND val.machine=:machine AND val.engine=:device ORDER BY val.createdAt DESC LIMIT 1) AS value
FROM vis_machine_device_variables dv
INNER JOIN variables var ON var.id=dv.variable
WHERE dv.machine=:machine
  AND dv.device=:device
ORDER BY var.name ASC
');

foreach ($devices as $device)
{
  if ($device->device === null)
  {
    $device->top = 0;
    $device->left = 0;
    $device->zindex = 0;
    $device->image = $deviceImgPath . 'default.gif';
    $device->image_width = 200;
    $device->image_height = 200;
    $device->image_max_width = 400;
    $device->image_max_height = 450;
    $device->fg_color = 'inherit';
    $device->bg_color = 'transparent';
    $device->variables = array();
    $device->variables_fg_color = 'inherit';
    $device->variables_bg_color = 'transparent';
  }
  else
  {
    $device->image = $deviceImgPath . $device->image;
    $device->variables = fetch_all($varStmt, array(':machine' => $machine->id, ':device' => $device->id));
  }

  escape_var($device->name);
}

escape_vars($machine->bg_color, $machine->bg_image, $machine->bg_position, $machine->bg_repeat);

if (file_exists(dirname(__FILE__) . ENVIS_UPLOADS_DIR . '/vis-machine-bg/' . $machine->bg_image))
{
  $machine->bg_image = 'url(' . url_for(ENVIS_UPLOADS_DIR . '/vis-machine-bg/' . $machine->bg_image) . ')';
}
else
{
  $machine->bg_image = 'none';
}

$variables = fetch_array('SELECT id AS `key`, name AS value FROM variables ORDER BY name');

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

$canViewFactoryVis = is_allowed_to('vis/factory');
$canEdit = is_allowed_to('vis/machine/edit');
$editMode = !empty($_GET['edit']) && $canEdit;
$hasDocs = false;

$showLink = !$editMode && is_allowed_to('vis/device');

if (!$editMode && is_allowed_to('documentation*'))
{
  $rs = fetch_one('SELECT COUNT(*) AS `count` FROM documentations WHERE machine=:machine AND device IS NULL', array(':machine' => $machine->id));

  $hasDocs = $rs && ($rs->count > 0);
}

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
    width: <?= $machine->width ?>px;
    height: <?= $machine->height ?>px;
    color: <?= $machine->fg_color ?>;
    background-color: <?= $machine->bg_color ?>;
    background-image: <?= $machine->bg_image ?>;
    background-position: <?= $machine->bg_position ?>;
    background-repeat: <?= $machine->bg_repeat ?>;
  }
  .device
  {
    position: absolute;
  }
  .device h3
  {
    margin: 0;
  }
  .device img, .device a
  {
    margin: 0;
    text-align: left;
  }
  .device table
  {
    border: 0;
    width: auto;
    margin: 0 auto;
  }
  .device td
  {
    background: none;
    border: 0!important;
    padding: 0.25em;
    text-align: left;
    color: inherit;
  }
  .device tr:hover td
  {
    color: inherit;
  }
</style>
<? if ($editMode): ?>
<style>
  .action { display: none; }
  .colorpicker { z-index: 1010; }
  .setBg-image-choice, .setImg-image-choice { display: none; }
  .edit .device img { cursor: move; }
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
  <? if ($hasDocs): ?><li><a href="<?= url_for('documentation/view_machine.php?id=' . $machine->id) ?>">Dokumentacja</a><? endif ?>
  <? if ($editMode): ?>
  <li><a href="<?= url_for('machine.php?id=' . $machine->id) ?>">Wyłącz tryb edytowania</a>
  <? elseif ($canEdit): ?>
  <li><a href="<?= url_for('machine.php?edit=1&amp;id=' . $machine->id) ?>">Włącz tryb edytowania</a>
  <? endif ?>
</ul>
<? append_slot() ?>

<? decorate('Wizualizacja maszyny') ?>

<div class="container">
  <h1><? if ($canViewFactoryVis): ?><a href="<?= url_for('factory.php?id=' . $machine->factory) ?>"><? endif ?><?= escape($machine->factoryName) ?><? if ($canViewFactoryVis): ?></a><? endif ?> &gt; <?= escape($machine->name) ?></h1>
  <div id="vis" class="<?= $editMode ? 'edit' : '' ?>">
    <? foreach ($devices as $device): ?>
    <div class="device" data-id="<?= $device->id ?>" style="top: <?= $device->top ?>px; left: <?= $device->left ?>px; z-index: <?= $device->zindex ?>; color: <?= $device->fg_color ?>; background-color: <?= $device->bg_color ?>;">
      <h3><?= $device->name ?></h3>
      <? if ($showLink): ?><a href="<?= url_for("factory/machine/engine?machine={$machine->id}&amp;id={$device->id}") ?>"><? endif ?>
      <img alt="<? $device->id ?>" src="<?= $device->image ?>" width="<?= $device->image_width ?>" height="<?= $device->image_height ?>" style="max-width: <?= $device->image_max_width ?>px; max-height: <?= $device->image_max_height ?>px;">
      <? if ($showLink): ?></a><? endif ?>
      <table id="vars-<?= prep_js_id($device->id) ?>" class="vars" style="color: <?= $device->variables_fg_color ?>; background-color: <?= $device->variables_bg_color ?>">
        <? foreach ($device->variables as $variable): ?>
        <tr data-id="<?= $variable->id ?>">
          <td><?= $variable->name ?>
          <td><?= ($variable->value === null ? '-' : round($variable->value, 2)) ?>
        <? endforeach ?>
      </table>
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
    <form id="setBg-form" method="post" action="<?= url_for('vis/machine.php') ?>">
      <input type="hidden" name="id" value="<?= $machine->id ?>">
      <fieldset>
        <legend>Ustawienia tła</legend>
        <ol class="form-fields">
          <li>
            <label for="setBg-fg">Kolor czcionki</label>
            <input id="setBg-fg" name="properties[fg_color]" type="text" value="<?= escape($machine->fg_color) ?>">
          <li>
            <label for="setBg-color">Kolor tła</label>
            <input id="setBg-color" name="properties[bg_color]" type="text" value="<?= escape($machine->bg_color) ?>">
          <li>
            <label for="setBg-position">Pozycja obrazka</label>
            <select id="setBg-position" name="properties[bg_position]">
              <?= render_options($bgPos, $machine->bg_position) ?>
            </select>
          <li>
            <label for="setBg-repeat">Powtarzanie obrazka</label>
            <select id="setBg-repeat" name="properties[bg_repeat]">
              <?= render_options($bgRepeat, $machine->bg_repeat) ?>
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
              <li><a id="setBg-cancel" href="<?= url_for('machine.php?edit=1&amp;id=' . $machine->id) ?>">Anuluj</a>
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
    <form id="setImg-form" method="post" action="<?= url_for('vis/machine_device.php') ?>">
      <input id="setImg-machine" type="hidden" name="machine" value="<?= $machine->id ?>">
      <input id="setImg-id" type="hidden" name="id" value="">
      <fieldset>
        <legend>Ustawienia obrazka</legend>
        <ol class="form-fields">
          <li>
            <label for="setImg-fg">Kolor czcionki</label>
            <input id="setImg-fg" name="properties[fg_color]" type="text" value="inherit">
          <li>
            <label for="setImg-color">Kolor tła</label>
            <input id="setImg-color" name="properties[bg_color]" type="text" value="transparent">
          <li>
            <label for="setImg-image">Nowy obrazek</label>
            <input id="setImg-image" type="file" value="">
            <div class="setImg-image-choice">
              <input id="setImg-image-choice" name="properties[image]" type="checkbox" checked="checked" value=""> <label for="setImg-image-choice"></label>
            </div>
          <li>
            <ol class="form-actions">
              <li><input id="setImg-submit" type="submit" value="Zapisz ustawienia">
              <li><a id="setImg-cancel" href="<?= url_for('machine.php?edit=1&amp;id=' . $machine->id) ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
<div id="setVar" class="block action">
  <div class="block-header">
    <h1 class="block-name">Ustawienia zmiennych</h1>
  </div>
  <div class="block-body">
    <form id="setVar-form" method="post" action="<?= url_for('vis/machine_device.php') ?>">
      <input id="setVar-machine" type="hidden" name="machine" value="<?= $machine->id ?>">
      <input id="setVar-id" type="hidden" name="id" value="">
      <input type="hidden" name="variables[_]" value="0">
      <fieldset>
        <legend>Ustawienia zmiennych</legend>
        <ol class="form-fields">
          <li>
            <label for="setVar-fg">Kolor czcionki</label>
            <input id="setVar-fg" name="properties[variables_fg_color]" type="text" value="inherit">
          <li>
            <label for="setVar-color">Kolor tła</label>
            <input id="setVar-color" name="properties[variables_bg_color]" type="text" value="transparent">
          <li class="form-choice">
            <?= render_choice('Zmienne', 'setVar-vars', 'variables[]', $variables, null, true) ?>
          <li>
            <ol class="form-actions">
              <li><input id="setVar-submit" type="submit" value="Zapisz ustawienia">
              <li><a id="setVar-cancel" href="<?= url_for('machine.php?edit=1&amp;id=' . $machine->id) ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-plugins/uploadify/2.0.3/swfobject.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/uploadify/2.0.3/jquery.uploadify.min.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/simplemodal/jquery.simplemodal.min.js') ?>"></script>
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
      'folder'     : '<?= ENVIS_UPLOADS_DIR ?>/vis-machine-bg',
      'fileDesc'   : 'Obrazek (png, jpg, gif)',
      'fileExt'    : '*.png;*.jpg;*.jpeg;*.gif',
      'sizeLimit'  : 3145728,
      'buttonText' : 'Wybierz',
      'scriptData' : {id: '<?= (int)$machine->id ?>'},
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
        $('#setBg-image-choice').val('<?= $machine->id ?>_new' + file.type);
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

      $.post('<?= url_for('vis/machine.php') ?>', $(this).serialize(), function(image)
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
        var data = 'id=<?= $machine->id ?>&properties[width]=' + ui.size.width + '&properties[height]=' + ui.size.height;

        $.post('<?= url_for('vis/machine.php') ?>', data);
      }
    });

    $('.device').draggable({
      containment: 'parent',
      handle: 'img',
      stack: '.device',
      stop: function(e, ui)
      {
        var zIndex = [];

        $('.device').each(function(i, el)
        {
          zIndex.push('&zindex[' + $(el).attr('data-id') + ']=' + $(el).css('z-index'));
        });

        var data = 'machine=<?= $machine->id ?>&id=' + $(this).attr('data-id') + '&properties[top]=' + ui.position.top + '&properties[left]=' + ui.position.left + zIndex;

        $.post('<?= url_for('vis/machine_device.php') ?>', data);
      }
    });
    $('.device img').each(function(i, el)
    {
      $(el).resizable({
        aspectRatio: true,
        containment: '#vis',
        handles: 'se',
        autoHide: true,
        maxWidth: parseInt($(el).css('max-width')),
        maxHeight: parseInt($(el).css('max-height')),
        stop: function(e, ui)
        {
          var data = 'machine=<?= $machine->id ?>&id=' + $(this).parent().attr('data-id') + '&properties[image_width]=' + ui.size.width + '&properties[image_height]=' + ui.size.height;

          $.post('<?= url_for('vis/machine_device.php') ?>', data);
        }
      })
    });

    var device;

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
      selector: '#vis .device',
      choices: [
        {
          label: 'Ustawienia obrazka',
          action: function(el)
          {
            device = $(el);

            $('#setImg-id').val(device.attr('data-id'));
            $('#setImg-fg').val(device.css('color'));
            $('#setImg-color').val(device.css('background-color'));

            modal('#setImg', {persist: true});
          }
        },
        {
          label: 'Ustawienia zmiennych',
          action: function(el)
          {
            device = $(el);

            var vars = $('table', device);

            $('#setVar-id').val(device.attr('data-id'));
            $('#setVar-fg').val(vars.css('color'));
            $('#setVar-color').val(vars.css('background-color'));

            var variables = [];

            $('tr', vars).each(function(i, v)
            {
              variables.push($(v).attr('data-id'));
            });

            $('#setVar-form input[name="variables[]"]').each(function(i, v)
            {
              v.checked = $.inArray(v.value, variables) !== -1;
            });
            $('#setVar-form select[name="variables[]"] option').each(function(i, v)
            {
              v.selected = $.inArray(v.value, variables) !== -1;
            });

            modal('#setVar', {persist: true});
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
      'folder'     : '<?= ENVIS_UPLOADS_DIR ?>/vis-device',
      'fileDesc'   : 'Obrazek (png, jpg, gif)',
      'fileExt'    : '*.png;*.jpg;*.jpeg;*.gif',
      'sizeLimit'  : 3145728,
      'buttonText' : 'Wybierz',
      onSelect     : function()
      {
        $('#setImg-image').uploadifySettings('scriptData', {'id': device.attr('data-id')});

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
        $('#setImg-image-choice').val(device.attr('data-id') + '_new' + file.type);
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

      $.post('<?= url_for('vis/machine_device.php') ?>', $(this).serialize(), function(props)
      {
        device
          .css('color', $('#setImg-fg').val())
          .css('background-color', $('#setImg-color').val());

        if (props && props.src)
        {
          $('img', device)
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

    $('#setVar .form-actions a').click(function()
    {
      $.modal.close();

      return false;
    });
    $('#setVar-fg').ColorPicker({
      onSubmit: function(hsb, hex)
      {
        $('#setVar-fg').val('#' + hex).ColorPickerHide();
      }
    });
    $('#setVar-color').ColorPicker({
      onSubmit: function(hsb, hex)
      {
        $('#setVar-color').val('#' + hex).ColorPickerHide();
      }
    });
    $('#setVar-form').submit(function()
    {
      $('#setVar-submit').attr('disabled', 'disabled');
      $('#setVar-cancel').hide();

      $.post('<?= url_for('vis/machine_device.php') ?>', $(this).serialize(), function(props)
      {
        var vars = $('table', device);

        vars
          .css('color', $('#setVar-fg').val())
          .css('background-color', $('#setVar-color').val());

        vars.empty();

        for (var i in props)
        {
          vars.append('<tr data-id="' + props[i].id + '"><td>' + props[i].name + '<td>' + (Math.round(props[i].value * 100) / 100));
        }

        $('#setVar-submit').removeAttr('disabled');
        $('#setVar-cancel').show();

        $.modal.close();
      }, 'json');

      return false;
    });
  });
</script>
<? append_slot() ?>
<? else: ?>
<? begin_slot('js') ?>
<script>
  $(document).ready(function()
  {
    var updateInterval = 5000;
    var updaterHandle = null;
    var lastUpdateTime = '<?= time() ?>';

    function doUpdateValues(result)
    {
      lastUpdateTime = result.lastUpdateTime;

      $('.vars').empty();

      for (var device in result.data)
      {
        var vars = $('#vars-' + device);

        for (var i in result.data[device])
        {
          vars.append('<tr data-id="' + result.data[device][i].id + '"><td>' + result.data[device][i].name + '<td>' + (result.data[device][i].value === null ? '-' : (Math.round(result.data[device][i].value * 100) / 100)));
        }
      }

      clearTimeout(updaterHandle);

      updaterHandle = setTimeout(updateValues, updateInterval);
    }

    function updateValues()
    {
      var data = {machine: '<?= $machine->id ?>', since: lastUpdateTime};

      $.get('<?= url_for('vis/machine_device_values.php') ?>', data, doUpdateValues, 'json');
    }

    updaterHandle = setTimeout(updateValues, updateInterval);
  });
</script>
<? append_slot() ?>
<? endif ?>
