$(function()
{
  $('#clear-product-nr').click(function()
  {
    $('#product-nr').val('');
  });

  function fixAutocomplete(e, ui)
  {
    $(this).data('autocomplete').menu.element.css('width', $(this).width() + 'px');
  }

  $('#product-category-ac').autocomplete({
    source: CATALOG_SEARCH_CATEGORIES_URL || '/catalog/categories/fetch.php',
    open: fixAutocomplete,
    select: function(e, ui)
    {
      $('#product-category').val(ui.item.id);
    }
  });
});
