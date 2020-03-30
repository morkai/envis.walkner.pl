<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT i.id, i.owner, i.creator, i.relatedFactory, i.relatedMachine
FROM issues i
WHERE i.id=?
LIMIT 1
SQL;

$issue = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($issue));

$files = array_map(function($file)
{
  $file->type = get_file_type_from_name($file->file);

  return $file;
}, fetch_all('SELECT f.*, u.name AS uploaderName FROM issue_files f INNER JOIN users u ON u.id=f.uploader WHERE f.issue=? ORDER BY name ASC', array(1 => $issue->id)));

$currentUser = $_SESSION['user'];

$docsViewer = is_issue_docs_viewer($currentUser, $issue);
$docsViewerSuffix = $docsViewer ? '&docs=1' : '';

$canAddFiles = $currentUser->isSuper()
               || is_issue_participant($currentUser, $issue)
               || (!$issue->owner && is_allowed_to('service/edit'));

$canManageFiles = function($file) use($currentUser, $issue)
{
  return $currentUser->isSuper()
         || $issue->owner == $currentUser->getId()
         || $file->uploader == $currentUser->getId()
         || (!$issue->owner && is_allowed_to('service/edit'));
};

?>

<div id="files">
  <table>
    <thead>
      <tr>
        <th>Nazwa
        <th>Typ
        <th>Czas wysłania
        <th>Wysyłający
        <th>Akcje
    <tbody>
      <? if (empty($files)): ?>
      <tr class="nofiles">
        <td colspan=5>Brak plików.
      <? endif ?>
      <? foreach ($files as $file): ?>
      <tr>
        <td class="name clickable"><a target="_blank" href="<?= url_for("service/files/download.php?id={$file->id}{$docsViewerSuffix}") ?>"><?= e($file->name) ?></a>
        <td><?= $file->type ?>
        <td><?= date('Y-m-d, H:i', $file->uploadedAt) ?>
        <td><a href="<?= url_for("user/view.php?id={$file->uploader}") ?>"><?= e($file->uploaderName) ?></a>
        <td class="actions">
          <? if ($canManageFiles($file)): ?>
          <ul>
            <li class="edit"><?= fff('Edytuj nazwę', 'bullet_edit', "service/files/edit.php?id={$file->id}") ?>
            <li class="delete"><?= fff('Usuń plik', 'bullet_cross', "service/files/delete.php?id={$file->id}") ?>
          </ul>
          <? endif ?>
      <? endforeach ?>
  </table>
  <div id="issueFiles" style="margin-top: 1em"></div>
</div>

<script src="<?= url_for_media("uppy/uppy.min.js", true) ?>"></script>
<script>
$(function()
{
  $('#files table').makeClickable();

  $('#files table tbody').delegate('.delete a', 'click', function(e)
  {
    if (e.button !== 0)
    {
      return true;
    }

    var me = this;

    $.ajax({
      type: 'DELETE',
      url: me.href,
      success: () =>
      {
        $(me).closest('tr').fadeOut(function()
        {
          $(this).remove();

          if ($('#files tbody')[0].childElementCount === 0)
          {
            $('#files tbody').append('<tr class=nofiles><td colspan=5>Brak plików');
          }
        });
      }
    });

    return false;
  });

  $('#files table tbody').delegate('.edit a', 'click', function(e)
  {
    if (e.button === 2)
    {
      return true;
    }

    var me = this;
    var $name = $(this).closest('tr').find('.name').first();
    var oldName = $.trim($name.text());
    var oldHtml = $name.html();
    var $input = $('<input type=text value="' + oldName + '">').keydown(function(e)
    {
      switch (e.keyCode)
      {
        case 27:
          $name.html(oldHtml);
          break;

        case 13:
          save();
          break;
      }
    }).blur(save);

    $name.empty().append($input);
    $input.focus();

    function save()
    {
      var newName = $.trim($input.val());

      $input.remove();
      $name.html(oldHtml);

      if (newName !== oldName)
      {
        $.ajax({
          type: 'POST',
          url: me.href,
          data: {name: newName},
          success: function()
          {
            $name.find('a').text(newName);
          }
        });
      }
    }

    return false;
  });

  var uppy = Uppy.Core({
    autoProceed: true,
    meta: {
      folder: '/issues'
    }
  });

  uppy.use(Uppy.FileInput, {
    target: '#issueFiles',
    pretty: false,
    replaceTargetContent: true,
    limit: 1
  });

  uppy.use(Uppy.XHRUpload, {
    endpoint: '<?= url_for_media("uppy/uppy.php", true) ?>',
    formData: true,
    fieldName: 'file'
  });

  uppy.on('file-added', file =>
  {
    $('#files .nofiles').remove();

    $('#files tbody').append(`
<tr data-id="${file.id}">
  <td class="name">${file.name}</td>
  <td>${file.type}</td>
  <td></td>
  <td></td>
  <td class="actions"></td>
</tr>
    `);
  });

  uppy.on('upload-error', (file, error, response) =>
  {
    $('#files tr[data-id="' + file.id + '"]').css('color', 'red');
  });

  uppy.on('upload-success', (file, res) =>
  {
    $.ajax({
      type: 'POST',
      url: '<?= url_for("service/files/upload.php") ?>',
      data: {
        issue: <?= $issue->id ?>,
        file: res.body.file,
        name: res.body.name
      },
      success: function(data)
      {
        $('#files tr[data-id="' + file.id + '"]').replaceWith(`
<tr data-id="${file.id}">
  <td class="name clickable"><a target="_blank" href="<?= url_for("service/files/download.php?id=") ?>${data.id}<?= $docsViewerSuffix ?>">${data.name}</td>
  <td>${file.type}</td>
  <td>${data.uploadedAt}</td>
  <td><a href="<?= url_for("user/view.php?id=") ?>${data.uploader}">${data.uploaderName}</a></td>
  <td class="actions">
    <ul>
      <li class="edit"><a href="<?= url_for("/service/files/edit.php?id=") ?>${data.id}"><img src="<?= url_for_media("fff/bullet_edit.png") ?>" alt="Edytuj nazwę" title="Edytuj nazwę"></a>
      <li class="delete"><a href="<?= url_for("/service/files/delete.php?id=") ?>${data.id}"><img src="<?= url_for_media("fff/bullet_cross.png") ?>" alt="Usuń plik" title="Usuń plik"></a>
    </ul>
  </td>
</tr>
    `);
      }
    });
  });

  uppy.on('complete', (result) =>
  {
    result.failed.forEach(f => uppy.removeFile(f.id));
    result.successful.forEach(f => uppy.removeFile(f.id));
  });
});
</script>
