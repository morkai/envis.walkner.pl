<?php

include __DIR__ . '/_common.php';

no_access_if_not_allowed('catalog*');

if (!empty($_GET['product']))
{
  $product = fetch_one('SELECT id, category FROM catalog_products WHERE id=? LIMIT 1', array(1 => $_GET['product']));

  not_found_if(empty($product));

  $categories = catalog_fetch_categories($product->category);
}

$initiallyOpen = array();
$initiallySelect = array();

if (!empty($categories))
{
  foreach ($categories as $category)
  {
    $initiallyOpen[] = 'category-' . $category->id;
  }

  $initiallySelect[] = 'product-' . $product->id;
}

$canManageProducts = is_allowed_to('catalog/manage');

$defaultImageUrlTpl = url_for('catalog/products/images/default.php?product={$product}&id={$image}');
$deleteImageUrlTpl = url_for('catalog/products/images/delete.php?product={$product}&id={$image}');

?>

<? begin_slot('head') ?>
<link rel=stylesheet href="<?= url_for_media('jquery-plugins/lightbox/2.51/css/lightbox.css') ?>">
<link rel=stylesheet href="<?= url_for_media("uploadify/2.1.4/uploadify.css", true) ?>">
<style>
.jstree-default.jstree-focused { background: #FFF!important; }
#container
{
  display: table;
  width: 100%;
}
#container > div
{
  display: table-cell;
  vertical-align: top;
}
#container > :first-child
{
  width: 1%;
  max-width: 40%;
}
#container > :first-child > div
{
  margin-right: 1em;
}

.jstree-default a
{
  border: 1px solid transparent;
  padding: 0 2px 0 1px!important;
}
#tree {
  max-width: 400px;
  overflow: auto;
}
@media only screen and (max-width: 1280px) {
  #tree {
    max-width: 200px;
  }
}
#tree .product > .jstree-icon
{
  background: url('<?= url_for_media('fff/page.png') ?>') left top no-repeat;
}
#tree .category > .jstree-icon
{
  background: url('<?= url_for_media('fff/folder.png') ?>') left top no-repeat;
}
#tree .jstree-open > .category > .jstree-icon,
#tree .jstree-leaf > .category > .jstree-icon
{
  background-image: url('<?= url_for_media('fff/folder_open.png') ?>');
}
#vakata-contextmenu
{
  text-align: left;
}
#vakata-contextmenu ins
{
  margin-left: 2px;
}
#dialog
{
  display: none;
}
#productImages {
  margin: 1em 0 0 0;
}
#productImages:empty {
  display: none;
}
#productImages li {
  display: inline-block;
  text-align: center;
  margin: .5em 1em .5em 0;
}
#productImages .thumb {
  display: block;
  padding: .5em;
  background: #666;
}
#productImages .thumb:hover {
  background: #F50;
}
#productImages .thumb.default {
  background: #adff2f;
}
#productImages img {
  max-width: 178px;
  max-height: 100px;
}
#productImages .actions {
  margin-top: .25em;
}
#productImageFileUploader { margin-top: 1em; }
</style>
<? append_slot() ?>

<? begin_slot('js') ?>
<script src="<?= url_for_media('jquery-plugins/hotkeys/0.8/jquery.hotkeys.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/jstree/1.0-rc1/jquery.jstree.min.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/jstree/1.0-rc1/_lib/jquery.cookie.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/simplemodal/1.3/jquery.simplemodal.min.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/lightbox/2.51/js/lightbox.js') ?>"></script>
<script src="<?= url_for_media("uploadify/2.1.4/swfobject.js", true) ?>"></script>
<script src="<?= url_for_media("uploadify/2.1.4/jquery.uploadify.min.js", true) ?>"></script>
<script>
$(function()
{

function showErrors(form, errors)
{
  var el = form.find('.form-errors');

  if (!el.length)
    el = $('<ul class=form-errors></ul>').prependTo(form);

  el.empty();

  for (var i in errors)
    el.append('<li>' + errors[i]);
}

function centerDialog()
{
  center($('#simplemodal-container'), dialog);
}

var tree           = $('#tree');
var currentProduct = 0;

function fetchProduct(id, force)
{
  if (!id || (!force && id == currentProduct))
    return;

  var a = $('#product-' + id).find('a').addClass('jstree-loading');

  currentProduct = id;

  $('#product')
    .load('<?= url_for('catalog/products/') ?>?id=' + id, function(r)
    {
      a.removeClass('jstree-loading');

      $('#productImageFile').uploadify({
        uploader: '<?= url_for_media("uploadify/2.1.4/uploadify.swf", true) ?>',
        script: '<?= url_for_media("uploadify/2.1.4/uploadify.php", true) ?>',
        cancelImg: '<?= url_for_media("uploadify/2.1.4/cancel.png", true) ?>',
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
            url: '<?= url_for("catalog/products/images/upload.php") ?>',
            data: {
              product: currentProduct,
              file: response,
              name: file.name
            },
            success: function(data)
            {
              $('#productImages').append('\
<li>\
<a class="thumb" href="<?= url_for('/_files_/products/') ?>' + data.file + '" rel="lightbox[' + data.id + ']" title="' + data.description + '" data-id="' + data.id + '">\
<img src="<?= url_for('/_files_/products/') ?>' + data.file + '" alt="">\
</a>\
');
              <? if ($canManageProducts): ?>
              var defaultImageUrl = '<?= $defaultImageUrlTpl ?>'.replace('{$product}', data.product).replace('{$image}', data.id);
              var deleteImageUrl = '<?= $deleteImageUrlTpl ?>'.replace('{$product}', data.product).replace('{$image}', data.id);

              $('#productImages li:last-child').append('\
<div class="actions">\
<a class="fff default" href="' + defaultImageUrl + '"><?= fff('Ustaw jako domyślne', 'bullet_tick') ?></a>\
<a class="fff delete" href="' + deleteImageUrl + '"><?= fff('Usuń obraz', 'bullet_cross') ?></a>\
</div>\
');
              <? endif ?>
            }
          });
        }
      });
    });
}

function handleAddCategoryAction(node)
{
  showAddCategoryDialog(node.attr('id').replace(/[^\d]/g, ''));
}

function handleAddProductAction(node)
{
  var id = $('a:eq(0)', node).attr('data-id');

  dialog.modal({onClose: restoreDialog});

  dialog.load('<?= url_for("catalog/products/add.php?category=") ?>' + id, function()
  {
    centerDialog();

    $('#addProductForm').submit(function()
    {
      var form = $(this);

      $.ajax({
        type: 'POST',
        url: this.action,
        data: form.serialize(),
        success: function(r)
        {
          if (r.success)
            handleProductAdded(r.data);
          else
            showErrors(form, r.errors);
        }
      });

      return false;
    });
  });
}

function handleEditAction(node)
{
  var el = $('a:eq(0)', node);

  (el.hasClass('product') ? showEditProductDialog : showEditCategoryDialog)(el.attr('data-id'));
}

function handleDeleteAction(node)
{
  var el = $('a:eq(0)', node);

  (el.hasClass('product') ? showDeleteProductDialog : showDeleteCategoryDialog)(el.attr('data-id'));
}

function handleCategoryAdded(category)
{
  var parent = $('#category-' + category.parent);

  if (parent.size() && tree.jstree('is_closed', parent))
    tree.jstree('open_node', parent);

  tree.jstree('refresh', parent);

  restoreDialog();
}

function handleCategoryEdited(category)
{
  tree.jstree('rename_node', $('#category-' + category.id), category.name);

  restoreDialog();
}

function handleCategoryDeleted(id)
{
  var parent = $('#category-' + id);

  if (currentProduct && $('#product-' + currentProduct, parent).size())
    restoreProduct(currentProduct);

  tree.jstree('refresh', $.jstree._reference(tree)._get_parent(parent));

  restoreDialog();
}

function handleProductAdded(product)
{
  var categoryNode = $('#category-' + product.category);

  tree.jstree('refresh', categoryNode);
  tree.jstree('open_node', categoryNode);

  fetchProduct(product.id, true);

  restoreDialog();
}

function handleProductEdited(product)
{
  if (currentProduct == product.id)
    fetchProduct(product.id, true);
  
  tree.jstree('rename_node', $('#product-' + product.id), product.name);

  restoreDialog();
}

function handleProductDeleted(id)
{
  restoreProduct(id);

  var product = $('#product-' + id);

  currentProduct = 0;

  if (product.length)
    tree.jstree('delete_node', product);
}

function showAddCategoryDialog(parent)
{
  dialog.modal({onClose: restoreDialog});

  dialog.load('<?= url_for("catalog/categories/add.php?parent=") ?>' + parent, function()
  {
    centerDialog();

    $('#addCategoryForm').submit(function()
    {
      var form = $(this);

      $.ajax({
        type: 'POST',
        url: this.action,
        data: form.serialize(),
        success: function(r)
        {
          if (r.success)
            handleCategoryAdded(r.data);
          else
            showErrors(form, r.errors);
        }
      });

      return false;
    });
  });
}

function showEditProductDialog(id)
{
  dialog.modal({onClose: restoreDialog});

  dialog.load('<?= url_for("catalog/products/edit.php?id=") ?>' + id, function()
  {
    centerDialog();

    $('#editProductForm').submit(function()
    {
      var form = $(this);

      $.ajax({
        type: 'POST',
        url: this.action,
        data: form.serialize(),
        success: function(r)
        {
          if (r.success)
            handleProductEdited(r.data);
          else
            showErrors(form, r.errors);
        }
      });

      return false;
    });
  });
}

function showEditCategoryDialog(id)
{
  dialog.modal({onClose: restoreDialog});

  dialog.load('<?= url_for("catalog/categories/edit.php?id=") ?>' + id, function()
  {
    centerDialog();

    $('#editCategoryForm').submit(function()
    {
      var form = $(this);

      $.ajax({
        type: 'POST',
        url: this.action,
        data: form.serialize(),
        success: function(r)
        {
          if (r.success)
            handleCategoryEdited(r.data);
          else
            showErrors(form, r.errors);
        }
      });

      return false;
    });
  });
}

function showDeleteProductDialog(id)
{
  dialog.modal({onClose: restoreDialog});

  dialog.load('<?= url_for("catalog/products/delete.php?id=") ?>' + id, function()
  {
    centerDialog();

    $('#deleteProductForm').submit(function()
    {
      $.ajax({
        type: 'DELETE',
        url: this.action,
        success: function(r) { handleProductDeleted(r.id); },
        complete: function(_, r) { restoreDialog(); }
      });

      return false;
    });
  });
}

function showDeleteCategoryDialog(id)
{
  dialog.modal({onClose: restoreDialog});

  dialog.load('<?= url_for("catalog/categories/delete.php?id=") ?>' + id, function()
  {
    centerDialog();
    
    $('#deleteCategoryForm').submit(function()
    {
      $.ajax({
        type: 'DELETE',
        url: this.action + '&mode=' + $(this).find('input[name="mode"]:checked').val(),
        success: function(r) { handleCategoryDeleted(r.id); },
        complete: function(_, r) { restoreDialog(); }
      });

      return false;
    });
  });
}

var dialog        = $('#dialog');
var dialogBackup  = dialog.html();
var chooseProduct = $('#product').html();

function restoreProduct(id)
{
  if (currentProduct == id)
    $('#product').html(chooseProduct);
}

function restoreDialog()
{
  dialog.html(dialogBackup);

  $.modal.close();

  return false;
}

$('.cancel').live('click', restoreDialog);

$('#deleteProductLink').live('click', function()
{
  showDeleteProductDialog(currentProduct);

  return false;
});

$('#editProductLink').live('click', function()
{
  showEditProductDialog(currentProduct);

  return false;
});

$('#addRootCategoryLink').click(function()
{
  showAddCategoryDialog(0);

  return false;
});

$('#tree a').live('dblclick', function(e)
{
  var target = $(e.target);

  if (target.hasClass('category'))
  {
    tree.jstree('toggle_node', target);
  }

  return false;
});

tree.bind('reopen.jstree', function(e, data)
{
  <? if (empty($product)): ?>
  setTimeout(function()
  {
    fetchProduct(tree.find('a.product.jstree-clicked').attr('data-id'));
  }, 500);
  <? else: ?>
  fetchProduct(<?= $product->id ?>);
  <? endif ?>
});

tree.bind('contextmenu.jstree', function(e, data)
{
  var items = $.jstree._reference(this)._get_settings().contextmenu.items;

  var isProduct = $(e.target).hasClass('product');

  items.addCategory._disabled = isProduct;
  items.addProduct._disabled  = isProduct;
});

tree.bind('select_node.jstree', function(e, data)
{
  var node = $(data.args[0]);

  if (node.hasClass('product'))
    fetchProduct(node.attr('data-id'));
});

$.jstree.defaults.contextmenu.items = {};

tree.jstree(
{
  plugins: ['themes', 'json_data', 'ui', 'hotkeys', 'contextmenu', 'cookies'],

  core:
  {
    animation: 250,
    initially_open: <?= json_encode($initiallyOpen) ?>
  },

  themes:
  {
    theme: 'default',
    dots: false
  },

  ui:
  {
    select_limit: 1,
    initially_select: <?= json_encode($initiallySelect) ?>
  },

  contextmenu:
  {
    items:
    {
      addCategory:
      {
        label: 'Dodaj podkategorię',
        icon: '<?= url_for_media('fff/folder_add.png') ?>',
        action: handleAddCategoryAction
      },
      addProduct:
      {
        label: 'Dodaj produkt',
        icon: '<?= url_for_media('fff/page_add.png') ?>',
        action: handleAddProductAction,
        _class: 'addProduct'
      },
      edit:
      {
        separator_before: true,
        label: 'Edytuj',
        icon: '<?= url_for_media('fff/bullet_edit.png') ?>',
        action: handleEditAction
      },
      'delete':
      {
        label: 'Usuń',
        icon: '<?= url_for_media('fff/cross.png') ?>',
        action: handleDeleteAction
      }
    }
  },

  hotkeys:
  {
    'return': function(e)
    {
      if(this.data.ui.hovered)
      {
        var node = this.data.ui.hovered.children("a:eq(0)");
        
        node.click();
      }

      return false;
    }
  },

  json_data:
  {
    progressive_render: true,
    ajax:
    {
      url: '<?= url_for('catalog/categories/fetch.php') ?>',
      data: function(n)
      {
        return {id: n && n.attr ? n.attr('id') : 0};
      }
    }
  },

  cookies: {
    to_open: 'concat',
    to_select: '<?= empty($initiallySelect) ? 'default' : 'override' ?>'
  }
});

$('body').on('click', '#productImages .delete', function(e)
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

$('body').on('click', '#productImages .default', function(e)
{
  var parent = $(this).closest('li');

  $.ajax({
    type: 'POST',
    url: this.href,
    success: function()
    {
      $('#productImages').find('a.thumb.default').removeClass('default');
      parent.find('a.thumb').addClass('default');
    }
  });

  return false;
});

});
</script>
<? append_slot() ?>

<? decorate('Produkty') ?>

<div id=container>
  <div>
    <div class="block">
      <ul class="block-header">
        <li><h1 class="block-name">Katalog produktów</h1>
        <? if ($canManageProducts): ?>
        <li><?=  fff('Dodaj główną kategorię', 'folder_add', 'catalog/categories/add.php', 'addRootCategoryLink') ?>
        <? endif ?>
      </ul>
      <div class="block-body" id="tree"></div>
    </div>
  </div>
  <div id=product>
    <div class="block">
      <ul class="block-header">
        <li><h1 class="block-name">Karta produktu</h1>
      </ul>
      <div class="block-body">
        <p>Wybierz produkt z katalogu.
      </div>
    </div>
  </div>
</div>

<div id="dialog">
  <div class="block">
    <div class="block-body">
      <p>Trwa ładowanie, proszę czekać.</p>
    </div>
  </div>
</div>
