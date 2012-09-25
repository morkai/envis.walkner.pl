function setUpActions(options)
{
  options || (options = {});

  var $pages = $('#pages');

  $pages
    .on('click', '.movePageUp', handleMovePageUp)
    .on('click', '.movePageDown', handleMovePageDown)
    .on('click', '.deletePage', handleDeletePage)
    .on('click', '.addPage', handleAddPage)
    .on('click', '.importTemplate', handleImportTemplate)
    .on('click', '.exportTemplate', handleExportTemplate);

  $('#layout')
    .change(function()
    {
      var $option = $(this.options[this.selectedIndex]);

      $('#layoutDescription').text($option.attr('data-description'));
      $('#layoutImage').attr('src', $option.attr('data-image'));
    })
    .change();

  function handleMovePageUp()
  {
    var $pageContainer = $(this).closest('.pageContainer');

    if ($pageContainer.is(':first-child'))
    {
      return false;
    }

    $.ajax({
      type: 'POST',
      url: this.href,
      data: {
        page: parseInt($pageContainer.attr('data-page'))
      },
      success: function()
      {
        $pageContainer.insertBefore($pageContainer.prev());
        $.scrollTo($pageContainer, 400);

        $pages.trigger('pageMovedUp', [$pageContainer]);
      },
      error: function()
      {
        alert('Nie udało się przesunąć strony w górę :(');
      }
    });

    return false;
  }

  function handleMovePageDown()
  {
    var $pageContainer = $(this).closest('.pageContainer');

    if ($pageContainer.is(':last-child'))
    {
      return false;
    }

    $.ajax({
      type: 'POST',
      url: this.href,
      data: {
        page: parseInt($pageContainer.attr('data-page'))
      },
      success: function()
      {
        $pageContainer
          .hide()
          .insertAfter($pageContainer.next())
          .fadeIn(800);

        $.scrollTo($pageContainer, 400);

        $pages.trigger('pageMovedDown', [$pageContainer]);
      },
      error: function()
      {
        alert('Nie udało się przesunąć strony w dół :(');
      }
    });

    return false;
  }

  function handleDeletePage()
  {
    var result = window.confirm('Na pewno chcesz usunąć wybraną stronę?');

    if (!result)
    {
      return false;
    }

    var $pageContainer = $(this).closest('.pageContainer');

    $.ajax({
      type: 'POST',
      url: this.href,
      data: {
        page: parseInt($pageContainer.attr('data-page'))
      },
      success: function()
      {
        $pageContainer.fadeOut(400, function()
        {
          if (typeof CKEDITOR === 'object' && CKEDITOR !== null)
          {
            var editorName = $pageContainer.attr('data-editor');

            if (editorName in CKEDITOR.instances)
            {
              CKEDITOR.instances[editorName].destroy();
            }
          }

          $pageContainer.remove();

          if ($pages.children().length === 0)
          {
            window.location.reload();
          }

          $pages.trigger('pageDeleted');
        });
      },
      error: function()
      {
        alert('Nie udało się usunąć strony :(');
      }
    });

    return false;
  }

  function handleAddPage()
  {
    var $prevPageContainer = $(this).closest('.pageContainer');

    $('#layouts').dialog({
      modal: true,
      resizable: false,
      title: 'Nowa strona',
      width: 500,
      buttons: {
        'Wstaw stronę': function()
        {
          insertPage($(this), $prevPageContainer)
        },
        'Anuluj': function()
        {
          $(this).dialog('close');
        }
      }
    });

    $('#layout').focus();

    return false;
  }

  function insertPage($dialog, $prevPageContainer)
  {
    var layout = $('#layout').val();

    $.ajax({
      type: 'POST',
      url: options.updateUrl || '/catalog/products/card/update.php',
      data: {
        product: options.productId || 0,
        layout: layout,
        position: $prevPageContainer.index() + 2
      },
      success: function(result)
      {
        renderPage($dialog, $prevPageContainer, result.page);
      },
      error: function()
      {
        $dialog.dialog('close');

        alert('Nie udało się utworzyć nowej strony :(');
      }
    });
  }

  function renderPage($dialog, $prevPageContainer, pageId)
  {
    var $pageContainer = $('<div class="pageContainer"></div>').attr('data-page', pageId);
    var $actions = $pages.find('.actions').first().clone();
    var $addPage = $pages.find('.addPage').first().clone();

    $pageContainer.load(
      options.renderUrl || '/catalog/products/card/layouts/render.php',
      {page: pageId},
      function(response, status)
      {
        if (status === 'error')
        {
          window.location.reload();
        }
        else
        {
          $dialog.dialog('close');

          $pageContainer
            .prepend($actions)
            .append($addPage)
            .hide()
            .insertAfter($prevPageContainer)
            .fadeIn();

          $.scrollTo($pageContainer, 400);

          if (typeof CKEDITOR === 'object' && CKEDITOR !== null)
          {
            CKEDITOR.inline($pageContainer.find('.contents')[0])
          }

          $pages.trigger('pageAdded', [$pageContainer]);
        }
      }
    );
  }

  function handleImportTemplate()
  {
    var $pageContainer = $(this).closest('.pageContainer');
    var importTemplateUrl = options.importTemplateUrl || '/catalog/products/card/templates/import.php';

    var $importTemplate = $('#importTemplate').load(importTemplateUrl, function()
    {
      $importTemplate.dialog({
        title: 'Import szablonu zawartości',
        modal: true,
        resizable: false,
        width: 500,
        buttons: {
          'Importuj szablon': function()
          {
            var templateId = $('#importTemplateId').val();
            var pageId = parseInt($pageContainer.attr('data-page'));

            $.ajax({
              type: 'POST',
              url: importTemplateUrl,
              data: {
                page: pageId,
                template: templateId
              },
              success: function(contents)
              {
                var editorName = $pageContainer.attr('data-editor');
                var editor = CKEDITOR.instances[editorName];

                editor.setData(contents);

                $importTemplate.dialog('close');
                $.scrollTo($pageContainer, 400);
              },
              error: function()
              {
                $importTemplate.dialog('close');

                alert('Nie udało się zaimportować szablonu :(');
              }
            });
          },
          'Anuluj': function()
          {
            $importTemplate.dialog('close');
          }
        }
      });
    });

    return false;
  }

  function handleExportTemplate()
  {
    var $pageContainer = $(this).closest('.pageContainer');
    var exportTemplateUrl = options.exportTemplateUrl || '/catalog/products/card/templates/export.php';

    var $exportTemplate = $('#exportTemplate').load(exportTemplateUrl, function()
    {
      $exportTemplate.dialog({
        title: 'Eksport szablonu zawartości',
        modal: true,
        resizable: false,
        width: 500,
        buttons: {
          'Eksportuj szablon': function()
          {
            var $templateName = $('#exportTemplateName');
            var templateName = $templateName.val();
            var templateId = $('#exportTemplateId').val();
            var pageId = parseInt($pageContainer.attr('data-page'));

            $templateName.val('');

            $.ajax({
              type: 'POST',
              url: exportTemplateUrl,
              data: {
                page: pageId,
                templateName: templateName,
                templateId: templateId,
                contents: $pageContainer.find('.contents').html()
              },
              success: function()
              {
                $exportTemplate.dialog('close');
              },
              error: function()
              {
                $exportTemplate.dialog('close');

                alert('Nie udało się wyeksportować szablonu :(');
              }
            });
          },
          'Anuluj': function()
          {
            $exportTemplate.dialog('close');
          }
        }
      });
    });

    return false;
  }
}
