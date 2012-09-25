function setUpCkeditor(options)
{
  options || (options = {});

  CKEDITOR.on('instanceCreated', function(e)
  {
    var editor = e.editor;
    var $page = $(editor.element.$).closest('.page');

    $page.closest('.pageContainer').attr('data-editor', editor.name);

    editor.on('configLoaded', function()
    {
      editor.config.entities = false;
      editor.config.language = 'pl';
      editor.config.extraPlugins = 'tableresize';
      editor.config.removePlugins = 'forms,iframe,smiley,templates,pagebreak,flash,about';
    });

    editor.on('focus', function()
    {
      $page.addClass('editing');
    });

    editor.on('blur', function()
    {
      $page.removeClass('editing');

      $.ajax({
        type: 'POST',
        url: options.updateUrl || '/catalog/products/card/update.php',
        data: {
          page: parseInt($page.attr('data-page')),
          product: options.productId || 0,
          layout: $page.attr('data-layout'),
          contents: editor.getData()
        },
        success: function(result)
        {
          $page.attr('data-page', result.page);
        },
        error: function()
        {
          alert('Nie udało się zapisać zmian :( Spróbuj ponownie!');
        }
      });
    });
  });
}
