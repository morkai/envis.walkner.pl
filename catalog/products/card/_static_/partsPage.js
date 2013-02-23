function setUpPartsPage(options)
{
  var $pages = $('#pages');

  $pages.on('pageAdded', function(e, $pageContainer)
  {
    var $page = $pageContainer.find('.page');

    if ($page.attr('data-layout') !== 'partsPage')
    {
      return;
    }

    makeResizable($page.find('.partsCanvas'));
  });

  $pages.on('dragstart', '.partsImage', function()
  {
    return false;
  });

  $pages.on('contextmenu', '.partsImage', function()
  {
    return false;
  });

  $pages.on('mouseup', '.partsImage', function(e)
  {
    if (e.button !== 2)
    {
      return;
    }

    $(this).parent().find('.partsImageFile').click();
  });

  $pages.on('change', '.partsImageFile', function(e)
  {
    var files = e.target.files;

    if (files.length === 0)
    {
      return;
    }

    var $image = $(this).parent().find('.partsImage');
    var reader = new FileReader();

    reader.onload = function(e)
    {
      var img = new Image();

      img.onload = function()
      {
        $image.attr('src', this.src);

        var $page = $image.closest('.page');
        var $container = $page.find('.partsContainer');
        var maxWidth = $container.outerWidth();
        var maxHeight = $container.outerHeight();
        var width = this.width;
        var height = this.height;
        var ratio = 1;

        if (width > maxWidth)
        {
          ratio = width / maxWidth;
          width = maxWidth;
          height /= ratio;
        }

        if (height > maxHeight)
        {
          ratio = height / maxHeight;
          height = maxHeight;
          width /= ratio;
        }

        width = Math.floor(width);
        height = Math.floor(height);

        $container.find('.partsCanvas').css({
          width: width,
          height: height
        });

        savePage($page, {image: 1, size: 1});
      };

      img.src = e.target.result;
    };

    reader.readAsDataURL(files[0]);
  });

  $pages.on('dblclick', '.partsMarker', function()
  {
    var $marker = $(this);
    var $page = $marker.closest('.page');

    $marker.fadeOut(function()
    {
      $marker.remove();

      savePage($page, {markers: 1});
    });

    return false;
  });

  $pages.on('dblclick', '.partsImage', function(e)
  {
    var $canvas = $(this).parent();
    var $marker = $('<span class="partsMarker"></span>');

    var markerNumbers = [];

    $canvas.find('.partsMarker').each(function()
    {
      markerNumbers.push(parseInt(this.innerHTML, 10));
    });

    markerNumbers.sort();

    var markerNumber = 1;

    while (markerNumbers.indexOf(markerNumber) !== -1)
    {
      ++markerNumber;
    }

    $marker.text(markerNumber);

    $canvas.append($marker);

    $marker.css({
      top: e.offsetY - ($marker.outerHeight() / 2),
      left: e.offsetX - ($marker.outerWidth() / 2)
    });
    $marker.hide();
    $marker.fadeIn();

    makeDraggable($marker);

    savePage($canvas.closest('.page'), {markers: 1});
  });

  function makeResizable($canvas)
  {
    $canvas.resizable({
      containment: 'parent',
      stop: function()
      {
        savePage($(this).closest('.page'), {size: 1});
      }
    });
  }

  function makeDraggable($marker)
  {
    $marker.draggable({
      containment: 'parent',
      stop: function()
      {
        savePage($(this).closest('.page'), {markers: 1});
      }
    });
  }

  function serialize($page, what)
  {
    var $canvas = $page.find('.partsCanvas');
    var $image = $canvas.find('.partsImage');

    var data = {};

    if (!what || what.image)
    {
      data.src = $image.attr('src');
    }

    if (!what || what.size)
    {
      data.width = $canvas.outerWidth();
      data.height = $canvas.outerHeight();
    }

    if (!what || what.markers)
    {
      data.markers = [];

      $canvas.find('.partsMarker').each(function()
      {
        var $marker = $(this);
        var pos = $marker.position();

        data.markers.push({
          nr: parseInt($marker.text(), 10),
          top: pos.top,
          left: pos.left
        });
      });
    }

    return {
      product: options.productId,
      page: $page.attr('data-page'),
      data: JSON.stringify(data)
    };
  }

  function savePage($page, what)
  {
    var data = serialize($page, what);

    $.ajax({
      type: 'POST',
      url: 'update.php',
      data: data,
      error: function()
      {
        alert('Nie udało się zapisać zmian :( Odśwież stronę!')
      }
    });
  }

  makeResizable($pages.find('.partsCanvas'));
  makeDraggable($pages.find('.partsMarker'));
}
