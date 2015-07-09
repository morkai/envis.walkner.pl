<?php

include_once __DIR__ . '/_common.php';

if (!empty($_POST['v']))
{
  $v = fetch_issues_grid_options($_POST['v']);

  if (empty($v['n']))
  {
    unset($v['n']);

    go_to('service/?' . http_build_query(array('v' => $v)));
  }

  $serializedView = serialize($v);

  $visibility = is_allowed_to('service/views') && isset($_POST['visibility']) && $_POST['visibility'] == 1 ? 1 : 0;

  if (isset($_POST['id']) && is_valid_view_id($_POST['id']))
  {
    $originalView = fetch_one("SELECT view, creator FROM grid_views WHERE grid='service/issues' AND view=? LIMIT 1", array(1 => $_POST['id']));

    if (empty($originalView)) not_found();

    no_access_if_not($originalView->creator == $_SESSION['user']->getId());

    if (isset($_POST['delete']))
    {
      exec_stmt("DELETE FROM grid_views WHERE grid='service/issues' AND view=? LIMIT 1", array(1 => $originalView->view));

      log_info(sprintf('Usunięto widok zgłoszeń serwisu <%s>.', $v['n']));

      set_flash(sprintf('Widok <%s> został usunięty pomyślnie!', $v['n']));

      go_to('service/filter.php');
    }

    if (isset($_POST['save']))
    {
      exec_update('grid_views',
                  array(
                    'public' => $visibility,
                    'name' => $v['n'],
                    'options' => $serializedView
                  ),
                  "grid='service/issues' AND view='{$originalView->view}'");

      log_info(sprintf('Zmodyfikowano widok zgłoszeń serwisu <%s>.', $v['n']));

      set_flash(sprintf('Widok <%s> został zmodyfikowany pomyślnie!', $v['n']));

      go_to('service/?v=' . $originalView->view);
    }

    bad_request();
  }
  else
  {
    $viewId = md5(uniqid(microtime()) . $serializedView);

    exec_insert('grid_views', array(
      'grid' => 'service/issues',
      'view' => $viewId,
      'creator' => $_SESSION['user']->getId(),
      'public' => $visibility,
      'name' => $v['n'],
      'options' => $serializedView
    ));

    go_to('service/?v=' . $viewId);
  }
}

$currentView = !empty($_GET['v']) && is_valid_view_id($_GET['v']) ? $_GET['v'] : null;

$views = fetch_array("SELECT view AS `key`, name AS `value` FROM grid_views WHERE grid='service/issues' AND creator=? ORDER BY name",
                     array(1 => $_SESSION['user']->getId()));

$filterColumns = array('0' => ' ', 'description' => 'Opis') + $columnNames;

unset($filterColumns['id'], $filterColumns['updatedAt']);

$orderDirs = array(
  1 => 'Rosnąco',
  -1 => 'Malejąco',
);

$conns = array(
  0 => 'Wszystkie warunki',
  1 => 'Dowolny z warunków',
);

$options = fetch_issues_grid_options();

$defaultView = fetch_one("SELECT d.view, v.name FROM grid_view_defaults d INNER JOIN grid_views v ON v.view=d.view WHERE d.grid='service/issues' AND d.user=? LIMIT 1",
                         array(1 => $_SESSION['user']->getId()));

if (empty($defaultView))
{
  $defaultView = new stdClass;
  $defaultView->view = '0';
  $defaultView->name = 'Standardowy widok';
}

$availableViews = fetch_array("SELECT view AS `key`, name AS `value` FROM grid_views WHERE grid='service/issues' AND (public=1 OR creator=?) AND view <> ? ORDER BY name",
                              array(1 => $_SESSION['user']->getId(), $defaultView->view));

$referer = get_referer('service/');

?>

<? begin_slot('head') ?>
<style>
  input[type="number"] { width: 5em; text-align: right; }
  .form-fields .horizontal > ol > li
  {
    float: none;
    display: inline-block;
    vertical-align: middle;
  }
  .form-fields .horizontal + li { padding-top: 0; }
  #viewFiltersTpl { display: none; }
  .multiline > li { vertical-align: top!important; }
  #view input[type="button"]
  {
    width: 2em;
  }
  input[type="date"]
  {
    width: 8em;
  }
  #viewSelection
  {
    font-size: .8em;
    margin: 0;
    vertical-align: top;
  }
  .block-body > fieldset
  {
    padding: 0;
    margin: 0;
    border: 0;
  }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-ui/1.8.11/js/jquery.ui.datepicker-pl.js') ?>"></script>
<script>
$(function()
{
  $('.removeViewOrder', $('#viewOrders')).live('click', function()
  {
    $(this.parentNode).fadeOut(function(){ $(this).remove(); });

    $('#addViewOrder').focus();

    return false;
  });

  $('#addViewOrder').click(function()
  {
    $('#viewOrderTpl').clone()
                      .removeAttr('id')
                      .find('#viewOrder')
                      .removeAttr('id')
                      .end()
                      .find('.removeViewOrder')
                      .removeAttr('disabled')
                      .end()
                      .hide()
                      .insertBefore(this.parentNode)
                      .fadeIn()
                      .find('select')
                      .first()
                      .focus();

    return false;
  });

  var viewFilters = $('#viewFilters');
  var viewFilterCount = -1;

  var addViewFilter = $('#addViewFilter');

  $('.removeViewFilter', viewFilters).live('click', function()
  {
    $(this.parentNode).closest('li.horizontal').fadeOut(function() { $(this).remove(); });

    addViewFilter.focus();

    return false;
  });

  var viewFilterTpl = $('#viewFiltersTpl').detach().removeAttr('id');

  addViewFilter.click(function()
  {
    ++viewFilterCount;

    viewFilterTpl.clone()
                 .attr('name', viewFilterTpl.find('.viewFilterColumn').attr('name').replace('[]', '[' + viewFilterCount + ']'))
                 .hide()
                 .insertBefore(this.parentNode)
                 .fadeIn()
                 .find('.viewFilterColumn')
                 .focus();

    return false;
  });

  $('.viewFilterColumn', viewFilters).live('change', function(_, cf)
  {
    var column = $(this.parentNode);

    column.nextAll().remove();

    var date = false;
    var multiline = true;
    var multiple = true;
    var html = '';
    var setInfo = function($info) { $info.val(cf.info); };
    var setValue = function($value) { $value.val(cf.value); };

    switch (this.value)
    {
      case 'subject':
      case 'description':
      case 'owner':
      case 'creator':
      case 'orderNumber':
      case 'orderInvoice':
        multiline = false;
        multiple = false;

        html += '<li><select class="info" name="v[f][i][' + viewFilterCount + ']"><option value="equals">równa się<option value="notEquals">nie równa się<option value="contains">zawiera<option value="starts">zaczyna się od<option value="ends">kończy się na</select>';
        html += '<li><input class="value" name="v[f][v][' + viewFilterCount + ']" type="text" value="">';
        break;

      case 'status':
        multiline = true;

        html += '<li><select class="value" name="v[f][v][' + viewFilterCount + '][]" multiple><?= render_options($statuses) ?></select>';
        break;

      case 'kind':
        multiline = true;

        html += '<li><select class="value" name="v[f][v][' + viewFilterCount + '][]" multiple><?= render_options($kinds) ?></select>';
        break;

      case 'type':
        multiline = true;

        html += '<li><select class="value" name="v[f][v][' + viewFilterCount + '][]" multiple><?= render_options($types) ?></select>';
        break;

      case 'priority':
        multiline = true;

        html += '<li><select class="value" name="v[f][v][' + viewFilterCount + '][]" multiple><?= render_options($priorities) ?></select>';
        break;

      case 'percent':
        html += '<li><select class="info" name="v[f][i][' + viewFilterCount + ']"><option value="lt">mniejszy niż<option value="gt">większy niż<option value="eq">równy<option value="ne">nie równy</select>';
        html += '<li><input class="value" name="v[f][v][' + viewFilterCount + ']" type="number" min="0" max="100" value="100">';
        break;

      case 'createdAt':
      case 'expectedFinishAt':
      case 'orderDate':
      case 'orderInvoiceDate':
        multiline = false;
        date = true;

        html += '<li><select class="info" name="v[f][i][' + viewFilterCount + ']" onchange="onFilterDateChanged(this)"><option value="from">od<option value="to">do<option value="between">pomiędzy</select>';
        html += '<li><input class="value" name="v[f][v][' + viewFilterCount + '][]" type="text" onfocus="onFilterDateFocused(this)" placeholder="YYYY-MM-DD">';

        if (cf && cf.info === 'between')
        {
          setInfo = function($info) { $info.val(cf.info).change(); };
          setValue = function($value)
          {
            $value[0].value = cf.value[0];
            $value[1].value = cf.value[1];
          };
        }
        break;
    }

    var parent = column.parent().removeClass('multiline');

    if (multiline)
    {
      parent.addClass('multiline');
    }

    parent.append(html);

    if (cf)
    {
      setInfo($('.info[name="v[f][i][' + viewFilterCount + ']"]', parent));
      setValue($('.value[name="v[f][v][' + viewFilterCount + ']' + (multiple ? '[]' : '') + '"]', parent));
    }
  });

  $('#viewSelection').change(function()
  {
    window.location.href = '<?= url_for('service/filter.php') ?>' + (this.value != 0 ? '?v=' + this.value : '');
  });

  <? if (!empty($options['f']['c'])): ?>
  var f = <?= json_encode($options['f']) ?>;

  for (var i in f.c)
  {
    var cf = {
      column: 'c' in f ? f.c[i] : [],
      info: 'i' in f ? f.i[i] : [],
      value: 'v' in f ? f.v[i] : []
    };

    var li = addViewFilter.click().parent().prev();

    li.find('.viewFilterColumn').val(cf.column).trigger('change', cf);
  }
  <? endif ?>
});

function onFilterDateChanged(el)
{
  if (el.value == 'between')
  {
    $(el.parentNode).next().after('<li>a<li><input class="value" name="' + el.name.replace('[i]', '[v]') + '[]" type="text" onfocus="onFilterDateFocused(this)" placeholder="YYYY-MM-DD">');
  }
  else
  {
    $(el.parentNode).next().nextAll().remove();
  }
}

function onFilterDateFocused(el)
{
  if (!$.browser.opera)
  {
    $(el).datepicker({dateFormat: 'yy-mm-dd'}).datepicker('show');
  }

  el.onfocus = null;
}

</script>
<? append_slot() ?>

<? decorate("Zarządzanie widokami zgłoszeń") ?>

<div class="yui-g">
  <div class="yui-u first">
<form class="form" method="post" action="<?= url_for('service/filter.php') ?>">
      <div class="block" id="view">
        <div class="block-header">
          <h1 class="block-name">
            Widok
            <select id="viewSelection" name="id">
              <option value="0">Nowy
              <?= render_options($views, $currentView) ?>
            </select>
          </h1>
        </div>
        <div class="block-body">
          <fieldset>
              <legend>Filtrowanie wyników</legend>
              <ol class="form-fields">
                <li>
                  <?= render_choice('Widoczne kolumny*', 'viewColumns', 'v[c][]', $columnNames, $options['c'], true, array('size' => '5')) ?>
                <li>
                  <fieldset id="viewFiltersContainer">
                    <legend>
                      <select name="v[f][j]">
                        <?= render_options($conns, $options['f']['j']) ?>
                      </select>
                    </legend>
                    <ol class="form-fields" id="viewFilters">
                      <li class="horizontal" id="viewFiltersTpl">
                        <ol>
                          <li><input type="button" value="x" class="removeViewFilter">
                          <li>
                            <select class="viewFilterColumn" name="v[f][c][]">
                              <?= render_options($filterColumns) ?>
                            </select>
                        </ol>
                      <li>
                        <input type="button" id="addViewFilter" value="+" title="Dodaj następne pole do filtrowania">
                    </ol>
                  </fieldset>
                <li>
                  <fieldset>
                    <legend><?= label('viewOrder', 'Sortowanie*') ?></legend>
                    <ol class="form-fields" id="viewOrders">
                      <? foreach ($options['o']['f'] as $k => $field): ?>
                      <li<?= !$k ? ' id="viewOrderTpl"' : '' ?>>
                        <input type="button" class="removeViewOrder" value="x" <?= !$k ? 'disabled' : '' ?>>
                        <select <?= !$k ? 'id="viewOrder"' : '' ?> name="v[o][f][]">
                          <?= render_options($columnNames, $field) ?>
                        </select>
                        <select name="v[o][d][]">
                          <?= render_options($orderDirs, $options['o']['d'][$k]) ?>
                        </select>
                      <? endforeach ?>
                      <li>
                        <input type="button" id="addViewOrder" value="+" title="Dodaj następne pole do sortowania">
                    </ol>
                  </fieldset>
                <li>
                  <?= label('viewPerPage', 'Rekordów na jedną stronę*') ?>
                  <input id="viewPerPage" name="v[p]" class="number" type="number" min="5" max="50" title="Od 5 do 50 rekordów na stronę" value="<?= $options['p'] ?>">
                <li>
                  <label for="viewName">Zapisz jako</label>
                  <input id="viewName" name="v[n]" type="text" value="<?= $options['n'] ?>" maxlength="60">
                <? if (is_allowed_to('service/views')): ?>
                <li>
                  <input id="viewVisibility" name="visibility" type="checkbox" value="1">
                  <label for="viewVisibility">Publiczny widok</label>
                <? endif ?>
                <li>
                  <ol class="form-actions">
                    <li><input type="submit" name="save" value="Zastosuj">
                    <? if ($currentView): ?>
                    <li><input type="submit" name="delete" value="Usuń">
                    <? endif ?>
                  </ol>
              </ol>
            </fieldset>
        </div>
      </div>
    </form>
  </div>
  <div class="yui-u">
    <div class="block">
      <div class="block-header">
        <h1 class="block-name">
          Domyślny widok
        </h1>
      </div>
      <div class="block-body">
        <form method="post" action="<?= url_for('service/set_default_view.php') ?>">
          <p>Aktualnie Twój domyślny widok to <strong><?= $defaultView->name ?></strong>.</p>
          <ol class="form-fields">
            <li>
              <label for="newDefaultView">Nowy domyślny widok</label>
              <select id="newDefaultView" name="view">
                <? if ($defaultView->view): ?>
                <option value="0">Standardowy widok
                <? endif ?>
                <?= render_options($availableViews) ?>
              </select>
            <li>
              <ol class="form-actions">
                <li><input type="submit" value="Zastosuj">
              </ol>
          </ol>
        </form>
      </div>
    </div>
  </div>
</div>
