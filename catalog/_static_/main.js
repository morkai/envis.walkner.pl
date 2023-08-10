$(function()
{
  $('#categories').makeClickable();
  $('#products').makeClickable();
  $('#issues tbody').makeClickable();
  $('#files tbody').makeClickable();

  $('#productTabs').tabs({
    select: function(e, ui)
    {
      //window.location.hash = ui.panel.id;
    }
  });

  var $productImages = $('#productImages');
  var $productFiles = $('#productFiles');
  var productFileTpl = $('#productFileTpl').html();

  $('#catalog.collapsed .block-header').click(function(e)
  {
    var target = e.target.tagName;

    if (target === 'UL' || target === 'LI')
    {
      $('#catalog.collapsed').removeClass('collapsed');

      return false;
    }
  });

  $productImages.on('click', '.actions .delete', function()
  {
    var parent = $(this).closest('li');

    $.ajax({
      type: 'DELETE',
      url: this.href,
      success: function()
      {
        parent.fadeOut(function() { parent.remove(); });
      }
    });

    return false;
  });

  $productImages.on('click', '.actions .default', function()
  {
    var parent = $(this).closest('li');

    $.ajax({
      type: 'POST',
      url: this.href,
      success: function()
      {
        $productImages.find('a.thumb.default').removeClass('default');
        parent.find('a.thumb').addClass('default');
      }
    });

    return false;
  });

  $productImages.on('click', '.actions .rotate', function()
  {
    var $parent = $(this).closest('li');
    var $img = $parent.find('.thumb img');

    if (!$img.attr('data-src'))
    {
      $img.attr('data-src', $img.attr('src'));
    }

    $.ajax({
      type: 'POST',
      url: this.href,
      success: function()
      {
        $img.attr('src', $img.attr('data-src') + '&' + Math.random());
      }
    });

    return false;
  });

  $productFiles.on('click', '.actions .delete', function()
  {
    var parent = $(this).closest('tr');

    $.ajax({
      type: 'DELETE',
      url: this.href,
      success: function()
      {
        parent.fadeOut(function()
        {
          parent.remove();
        });
      }
    });

    return false;
  });

  $productFiles.on('click', '.actions .edit', function(e)
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

  if (typeof PRODUCT_FILE_UPLOADER_CONFIG === 'undefined')
  {
    window.PRODUCT_FILE_UPLOADER_CONFIG = {};
  }

  if (window.Uppy)
  {
    setUpImageUploader();
    setUpFileUploader();

    var $productFileUrlForm = $('#productFileUrlForm');

    $('#productFileUrl').click(function()
    {
      $(this).attr('disabled', true);

      $productFileUrlForm.fadeIn().find('input').first().focus();
    });

    $productFileUrlForm.submit(function()
    {
      var name = $productFileUrlForm.find('#productFileUrlName').val().trim();
      var file = $productFileUrlForm.find('#productFileUrlFile').val().trim();

      if (name.length && file.indexOf('://') !== -1)
      {
        uploadFile(name, file);
      }

      this.reset();
      $productFileUrlForm.find('input').first().focus();

      return false;
    });
  }

  function setUpImageUploader()
  {
    var productImageTpl = $('#productImageTpl').html();

    var uppy = Uppy.Core({
      autoProceed: true,
      meta: {
        folder: '/products'
      },
      restrictions: {
        allowedFileTypes: ['image/*']
      }
    });

    uppy.use(Uppy.FileInput, {
      target: '#productImageFile',
      pretty: false,
      replaceTargetContent: true,
      limit: 1
    });

    uppy.use(Uppy.XHRUpload, {
      endpoint: PRODUCT_FILE_UPLOADER_CONFIG.script,
      formData: true,
      fieldName: 'file'
    });

    uppy.on('file-added', (file) =>
    {
      var r = new FileReader();

      r.onload = e =>
      {
        var $productImage = $(render(productImageTpl, {
          id: '',
          description: '',
          file: ''
        }));

        $productImage.hide().attr('data-id', file.id);
        $productImage.find('.actions').remove();
        $productImage
          .find('.thumb')
          .attr('rel', null)
          .prop('href', 'javascript:void(0)')
          .find('img')
          .prop('src', e.target.result);

        $productImage.appendTo($productImages).fadeIn();
      };

      r.readAsDataURL(file.data);
    });

    uppy.on('upload-error', (file) =>
    {
      $productImages.find('li[data-id="' + file.id + '"]').remove();
    });

    uppy.on('upload-success', (file, res) =>
    {
      $.ajax({
        type: 'POST',
        url: PRODUCT_FILE_UPLOADER_CONFIG.uploadImageUrl || 'catalog/products/images/upload.php',
        data: {
          product: PRODUCT_FILE_UPLOADER_CONFIG.currentProduct,
          file: res.body.file,
          name: res.body.name
        },
        success: function(data)
        {
          var $old = $productImages.find('li[data-id="' + file.id + '"]');
          var $new = $(render(productImageTpl, data));

          if ($old.length)
          {
            $old.replaceWith($new);
          }
          else
          {
            $new.hide().appendTo($productImages).fadeIn();
          }
        }
      });
    });

    uppy.on('complete', (result) =>
    {
      result.failed.forEach(f => uppy.removeFile(f.id));
      result.successful.forEach(f => uppy.removeFile(f.id));
    });
  }

  function setUpFileUploader()
  {
    var uppy = Uppy.Core({
      autoProceed: true,
      meta: {
        folder: '/products'
      },
      restrictions: {
        allowedFileTypes: [
          'image/*',
          '.rar', '.zip', '.7z',
          '.txt', '.md', '.docx', '.doc', '.xlsx', '.xls',
          '.pcb', '.prj', '.stl', '.3mf'
        ]
      }
    });

    uppy.use(Uppy.FileInput, {
      target: '#productFile',
      pretty: false,
      replaceTargetContent: true,
      limit: 1
    });

    uppy.use(Uppy.XHRUpload, {
      endpoint: PRODUCT_FILE_UPLOADER_CONFIG.script,
      formData: true,
      fieldName: 'file'
    });

    uppy.on('file-added', (file) =>
    {
      $productFiles.find('.nofiles').remove();

      var $productFile = $(render(productFileTpl, {
        id: 0,
        name: file.name,
        type: file.type,
        uploadedAt: ''
      })).hide();

      $productFile.attr('data-id', file.id);

      $productFile
        .find('.name')
        .removeClass('clickable')
        .find('a')
        .replaceWith(file.name);

      $productFile.find('.actions').html('');

      $productFile.appendTo($productFiles).fadeIn();
    });

    uppy.on('upload-error', (file) =>
    {
      $productFiles.find('tr[data-id="' + file.id + '"]').css('color', 'red');
    });

    uppy.on('upload-success', (file, res) =>
    {
      uploadFile(res.body.name, res.body.file, file.id);
    });

    uppy.on('complete', (result) =>
    {
      result.failed.forEach(f => uppy.removeFile(f.id));
      result.successful.forEach(f => uppy.removeFile(f.id));
    });
  }

  function uploadFile(name, file, id)
  {
    $.ajax({
      type: 'POST',
      url: PRODUCT_FILE_UPLOADER_CONFIG.uploadFileUrl || 'catalog/products/files/upload.php',
      data: {
        product: PRODUCT_FILE_UPLOADER_CONFIG.currentProduct,
        file: file,
        name: name
      },
      success: function(data)
      {
        $productFiles.find('.nofiles').remove();

        var $old = $productFiles.find('tr[data-id="' + id + '"]');
        var $new = $(render(productFileTpl, data));

        if ($old.length)
        {
          $old.replaceWith($new);
        }
        else
        {
          $new.hide().appendTo($productFiles).fadeIn();
        }
      }
    });
  }
});
