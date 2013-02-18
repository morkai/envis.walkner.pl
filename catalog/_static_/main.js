$(function()
{
  $('#categories').makeClickable();
  $('#products').makeClickable();
  $('#issues tbody').makeClickable();
  $('#files tbody').makeClickable();

  $('#productTabs').tabs({
    select: function(e, ui)
    {
      window.location.hash = ui.panel.id;
    }
  });

  var $productImages = $('#productImages');
  var $productFiles = $('#productFiles');

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

  if (typeof $.fn.uploadify === 'function')
  {
    var productImageTpl = $('#productImageTpl').html();

    $('#productImageFile').uploadify({
      uploader: PRODUCT_FILE_UPLOADER_CONFIG.uploader || 'uploadify/2.1.4/uploadify.swf',
      script: PRODUCT_FILE_UPLOADER_CONFIG.script || 'uploadify/2.1.4/uploadify.php',
      cancelImg: PRODUCT_FILE_UPLOADER_CONFIG.cancelImg || 'uploadify/2.1.4/cancel.png',
      folder: '/products',
      auto: true,
      multi: true,
      buttonText: 'Dodaj obrazy',
      fileDesc: 'Obraz (png, jpg, gif)',
      fileExt: '*.png;*.jpg;*.jpeg;*.gif',
      onComplete: function(e, id, file, response, data)
      {
        $.ajax({
          type: 'POST',
          url: PRODUCT_FILE_UPLOADER_CONFIG.uploadImageUrl || 'catalog/products/images/upload.php',
          data: {
            product: PRODUCT_FILE_UPLOADER_CONFIG.currentProduct,
            file: response,
            name: file.name
          },
          success: function(data)
          {
            var $productImage = $(render(productImageTpl, data)).hide();

            $productImage.appendTo($productImages).fadeIn();
          }
        });
      }
    });

    var productFileTpl = $('#productFileTpl').html();

    $('#productFile').uploadify({
      uploader: PRODUCT_FILE_UPLOADER_CONFIG.uploader || 'uploadify/2.1.4/uploadify.swf',
      script: PRODUCT_FILE_UPLOADER_CONFIG.script || 'uploadify/2.1.4/uploadify.php',
      cancelImg: PRODUCT_FILE_UPLOADER_CONFIG.cancelImg || 'uploadify/2.1.4/cancel.png',
      folder: '/products',
      auto: true,
      multi: true,
      buttonText: 'Dodaj lokalne pliki',
      onComplete: function(e, id, file, response)
      {
        uploadFile(file.name, response);
      }
    });

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

  function uploadFile(name, file)
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

        var $productFile = $(render(productFileTpl, data)).hide();

        $productFile.appendTo($productFiles).fadeIn();
      }
    });
  }
});
