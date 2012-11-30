$(function()
{
  $('#categories').makeClickable();
  $('#products').makeClickable();
  $('#issues tbody').makeClickable();
  $('#productTabs').tabs();

  var $productImages = $('#productImages');

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

  if (typeof PRODUCT_IMAGE_UPLOADER_CONFIG === 'undefined')
  {
    window.PRODUCT_IMAGE_UPLOADER_CONFIG = {};
  }

  if (typeof $.fn.uploadify === 'function')
  {
    var productImageTpl = $('#productImageTpl').html();

    $('#productImageFile').uploadify({
      uploader: PRODUCT_IMAGE_UPLOADER_CONFIG.uploader || 'uploadify/2.1.4/uploadify.swf',
      script: PRODUCT_IMAGE_UPLOADER_CONFIG.script || 'uploadify/2.1.4/uploadify.php',
      cancelImg: PRODUCT_IMAGE_UPLOADER_CONFIG.cancelImg || 'uploadify/2.1.4/cancel.png',
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
          url: PRODUCT_IMAGE_UPLOADER_CONFIG.uploadImageUrl || 'catalog/products/images/upload.php',
          data: {
            product: PRODUCT_IMAGE_UPLOADER_CONFIG.currentProduct,
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
  }
});
