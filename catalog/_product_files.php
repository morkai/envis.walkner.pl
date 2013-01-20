
<? begin_slot('js') ?>
<script src="<?= url_for_media("uploadify/2.1.4/swfobject.js", true) ?>"></script>
<script src="<?= url_for_media("uploadify/2.1.4/jquery.uploadify.min.js", true) ?>"></script>
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
      success: function()
      {
        $(me).closest('tr').fadeOut(function() { $(this).remove(); });
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

  $('#issueFile').uploadify({
    uploader: '<?= url_for_media("uploadify/2.1.4/uploadify.swf", true) ?>',
    script: '<?= url_for_media("uploadify/2.1.4/uploadify.php", true) ?>',
    cancelImg: '<?= url_for_media("uploadify/2.1.4/cancel.png", true) ?>',
    folder: '/issues',
    auto: true,
    multi: true,
    buttonText: 'Dodaj pliki',
    onComplete: function(e, id, file, response, data)
    {
      $.ajax({
        type: 'POST',
        url: '<?= url_for("service/files/upload.php") ?>',
        data: {
          issue: <?= $issue->id ?>,
          file: response,
          name: file.name
        },
        success: function(data)
        {
          $('#files .nofiles').remove();

          $('#files tbody').append('<tr>'
            + '<td class="name clickable"><a href="<?= url_for("service/files/download.php?id=") ?>' + data.id + '<?= $docsViewerSuffix ?>">' + data.name + '</a>'
            + '<td>' + file.type.toUpperCase().substr(1)
            + '<td>' + data.uploadedAt
            + '<td><a href="<?= url_for("user/view.php?id=") ?>' + data.uploader + '">' + data.uploaderName + '</a>'
            + '<td class="actions">'
            + '<ul>'
            + '<li class="edit"><a href="<?= url_for("/service/files/edit.php?id=") ?>' + data.id + '"><img src="<?= url_for_media("fff/bullet_edit.png") ?>" alt="Edytuj nazwę" title="Edytuj nazwę"></a>'
            + '<li class="delete"><a href="<?= url_for("/service/files/delete.php?id=") ?>' + data.id + '"><img src="<?= url_for_media("fff/bullet_cross.png") ?>" alt="Usuń plik" title="Usuń plik"></a>'
            + '</ul>'
          );
        }
      });
    }
  });
});
</script>
<? append_slot() ?>
