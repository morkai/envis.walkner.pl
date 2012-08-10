<?php

$bypassAuth = true;

include './_common.php';

if (!empty($_GET['id']))
{
	$page = help_fetch_page($_GET['id']);

	if (empty($page)) unset($page);
}
elseif (!empty($_GET['tag']))
{
	$pages = fetch_all('SELECT h.id, h.title FROM help_tags t INNER JOIN help h ON h.id=t.page WHERE t.tag=:tag ORDER BY h.title', array(':tag' => $_GET['tag']));

	if (empty($pages)) unset($pages);
}

$canManage = is_allowed_to('help*');

?>
<? begin_slot('head') ?>
<link rel="stylesheet" href="<?= url_for_media('jquery-plugins/jstree/0.9.8/source/tree_component.css') ?>">
<style>
#page-contents table { border: 0!important; border-color: #FFF!important; }
#page-contents table thead tr th { border: 0!important; border-color: #FFF!important; }
#page-contents table td { border-left: 1px solid #CCC; padding-left: 0.25em; padding-right: 0.25em; }
#page-contents table td:last-child { border-right: 1px solid #CCC; }
#toc { overflow-x: auto; padding-left: 0; }
#toc + div { clear: left; height: 1px; font-size: 0.1em; line-height: 0.1em; }
.tree-context { text-align: left; }
.section { margin-top: 1em; }
.section:first-child { margin-top: 0; }
.section h2 { margin-bottom: 0; }
.section ul { margin-bottom: 0; }
#tags { margin-left: 0; margin-top: 0.5em; }
#tags li { list-style: none; display: inline; }
#tags li a { background: #06C; color: #FFF; text-decoration: none; padding: 0.25em; }
#tags li a:hover { background: #F60; }
#msg { display: none; }
<? if (!isset($page)): ?>
#block-page .block-options { display: none; }
<? endif ?>
<? if ($canManage): ?>
#block-editor { display: none; }
.cke_skin_kama table, .cke_skin_kama tr, .cke_skin_kama td, .cke_skin_kama th {border: 0!important;}
#cke_editor { border: 0; padding: 0; }
<? endif ?>
</style>
<? append_slot() ?>

<? decorate("Pomoc") ?>

<div id="msg" class="message closable"></div>

<div class="yui-gd">
	<div class="yui-u first">
		<div class="block">
			<div class="block-header">
				<h1 class="block-name">Spis treści</h1>
				<? if ($canManage): ?>
				<ul class="block-options">
					<li id="page-add"><?= fff('Dodaj nową stronę', 'page_add', 'help/do_create.php') ?>
				</ul>
				<? endif ?>
			</div>
			<div class="block-body" id="toc"></div>
		</div>
	</div>
	<div class="yui-u">
		<div class="block" id="block-page">
			<div class="block-header">
				<h1 class="block-name" id="page-title">
					<? if (isset($page)): ?><?= escape($page->title) ?>
					<? elseif (isset($pages)): ?>Pomoc &lt;<?= escape($_GET['tag']) ?>&gt;
					<? else: ?>Pomoc
					<? endif ?>
				</h1>
				<? if ($canManage): ?>
				<ul class="block-options">
					<li id="page-edit"><?= fff('Edytuj', 'page_edit', 'help/do_edit.php') ?>
				</ul>
				<? endif ?>
			</div>
			<div class="block-body" id="page">
					<? if (isset($page)): ?><? help_render_page($page) ?>
					<? elseif (isset($pages)): ?><ol><? foreach ($pages as $page): ?><li><a href="?id=<?= $page->id ?>"><?= escape($page->title) ?></a><? endforeach ?></ol>
					<? else: ?><p>Wybierz temat pomocy ze spisu treści.</p>
					<? endif ?>
			</div>
		</div>
		<? if ($canManage): ?>
		<div class="block" id="block-editor">
			<div class="block-header">
				<h1 class="block-name">Edycja &lt;<span class="page-title"></span>&gt;</h1>
			</div>
			<div class="block-body">
				<form id="pageForm" method="post" action="<?= url_for('help/do_edit.php') ?>">
					<fieldset>
						<legend>Edycja strony</legend>
						<ol class="form-fields">
							<li>
								<label for="pageForm-title">Tytuł<span class="form-field-required">*</span></label>
								<input id="pageForm-title" type="text" name="page[title]" value="">
							<li>
								<label for="editor">Zawartość<span class="form-field-required">*</span></label>
								<textarea id="editor" name="page[contents]"></textarea>
							<li>
								<label for="pageForm-tags">Tagi</label>
								<input id="pageForm-tags" type="text" name="page[tags]" value="">
							<li>
								<ol class="form-actions">
									<li><input id="pageForm-save" type="submit" value="Zapisz">
									<li><a id="pageForm-cancel" href="<?= (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '?') ?>">Anuluj</a>
								</ol>
						</ol>
					</fieldset>
				</form>
			</div>
		</div>
		<? endif ?>
	</div>
</div>
<? begin_slot('js') ?>
<? if ($canManage): ?>
<script src="<?= url_for_media('ckeditor/3.0/ckeditor.js') ?>"></script>
<? endif ?>
<script src="<?= url_for_media('jquery-plugins/jstree/0.9.8/_lib/css.js') ?>"></script>
<script src="<?= url_for_media('jquery-plugins/jstree/0.9.8/source/tree_component.min.js') ?>"></script>
<script>
	$(function()
	{
		var msgTo;

		<? if ($canManage): ?>
		function toggleLock(status)
		{
			if (status)
			{
				toc.lock(true);

				$('#page-add').hide();
			}
			else
			{
				toc.lock(false);

				$('#page-add').show();
			}
		}
		$('#page-add a').click(function()
		{
			current = 0;
			
			toc.create(false, -1);
			
			return false;
		});
		$('#page-edit a').click(function()
		{
			editPage();

			return false;
		});
		<? endif ?>

		function msg(text, error)
		{
			if (msgTo)
      {
        clearTimeout(msgTo);
      }

      var $msg = $('#msg');

      $msg.removeClass('error').removeClass('success').addClass(error ? 'error' : 'success');
      $msg.html('<p>' + text + '</p>');
      $msg.fadeIn(500);

			msgTo = setTimeout(function() { $msg.fadeOut(500); }, 6000);
		}

		function prepareId(id)
		{
			return parseInt(id.replace(/[^0-9]/g, ''));
		}

		function fixIcon(item, icon)
		{
      if (item && item.length)
      {
			  $('a:first', item).css('background-image', 'url(<?= url_for_media('fff/') ?>' + icon + '.png)');
      }
		}

		function getTitle(item)
		{
			return $.trim($('a:first', item).text());
		}

		var noServerSide = false;
		var current = <?= (isset($page) ? $page->id : 0) ?>;
		var currentTitle = null;
		var creating = 0;

		$('#toc').tree({
			data: {
				type: 'json',
				method: 'GET',
				async: true,
				url: '<?= url_for('help/fetch_toc.php') ?>'
			},
			lang: {
				new_node: 'Pusta strona',
				loading: 'Ładowanie...'
			},
			ui: {
				dots: false
				<? if (!$canManage): ?>
				, context: null
				<? endif ?>
			},
			rules: {
			<? if ($canManage): ?>
				draggable: 'all'
			<? else: ?>
				renameable: 'none',
				creatable: 'none',
				deletable: 'none'
			<? endif ?>
			},
			callback: {
				onchange: function(item, tree)
				{
					var id = prepareId(item.id);

					if (current == id) return false;

					var rb = {title: $('#page-title').text(), contents: $('#page').html()};

					$('#page-title').text(getTitle(item)).attr('data-id', id);
					$('#page').html('<p>Ładowanie...</p>');

					<? if ($canManage): ?>
					$('#block-page .block-options').hide();
					<? endif ?>

					startWaiting();

					$.ajax({
						type: 'GET',
						url: '<?= url_for('help/fetch_contents.php') ?>?id=' + id,
						dataType: 'html',
						success: function(contents)
						{
							$('#page').html(contents);

							current = id;
						},
						error: function()
						{
							msg('Nie udało się załadować wybranej strony.', true);

							$('#page-title').text(rb.title);
							$('#page').html(rb.contents);

							toc.select_branch($('#help_' + current));
						},
						complete: function()
						{
							rb = null;

							<? if ($canManage): ?>
							$('#block-page .block-options').show();
							<? endif ?>

							stopWaiting();
						}
					});

					return true;
				}
				<? if ($canManage): ?>
				, onrgtclk: function(item, tree)
				{
					current = prepareId(item.id);
				},
				beforecreate: function(item, ref, type, tree)
				{
					if (noServerSide) { return; }

					var result = false;
					creating = 0;

					$.ajax({
						async: false,
						type: 'post',
						url: '<?= url_for('help/do_create.php') ?>',
						data: {parent: current},
						dataType: 'json',
						success: function(data)
						{
							if (data && data.id)
							{
								msg('Nowa strona pomocy została stworzona.');

								result   = true;
								creating = 1;

								item.id = 'page_' + data.id;

								fixIcon($('#page_' + current), 'book');
								fixIcon($(item), 'page');
							}
							else
							{
								msg('Nowa strona pomocy nie została stworzona.</p><p>' + data.error, true);
							}
						},
						error: function()
						{
							msg('Nowa strona pomocy nie została stworzona.', true);
						}
					});

					return result;
				},
				beforerename: function(item, lang, tree)
				{
					currentTitle = getTitle(item);

					return true;
				},
				onrename: function(item, lang, tree, rb)
				{
					var id    = prepareId(item.id);
					var title = getTitle(item);

					if (title == currentTitle) return;

					if (noServerSide) { return; }

					startWaiting();

					$.ajax({
						type: 'POST',
						url: '<?= url_for('help/do_rename.php') ?>',
						data: {id: id, title: title},
						dataType: 'json',
						success: function()
						{
							if (current == id)
							{
								$('#page-title').text(title);
							}

							msg('Tytuł został zmieniony.');

							toc.refresh(item);
						},
						error: function()
						{
							$.tree_rollback(rb);

							msg('Tytuł nie został zmieniony.', true);
						},
						complete: function()
						{
							creating = 0;

							stopWaiting();
						}
					});
				},
				ondelete: function(item, tree, rb)
				{
					if (noServerSide) { return; }

					startWaiting();

					$.ajax({
						type: 'POST',
						url: '<?= url_for('help/do_delete.php') ?>',
						data: {id: prepareId(item.id) },
						dataType: 'json',
						success: function()
						{
							msg('Strona &lt;' + getTitle(item) + '&gt; została usunięta pomyślnie.');
						},
						error: function()
						{
							msg('Strona &lt;' + getTitle(item) + '&gt; nie została usunięta.', true);

							$.tree_rollback(rb);
						},
						complete: stopWaiting
					})
				},
				beforemove: function(item, ref, type, tree)
				{
					switch (type)
					{
						case 'before':
						{
							var next = toc.next(item, true);

							return !next || (next.attr('id') != ref.id);
						}

						case 'after':
						{
							var prev = toc.prev(item, true);

							return !prev || (prev.attr('id') != ref.id);
						}
					}

					return true;
				},
				onmove: function(item, ref, type, tree, rb)
				{
					startWaiting();

					var itemTitle = getTitle(item);
					var refTitle  = getTitle(ref);

					$.ajax({
						type: 'POST',
						url: '<?= url_for('help/do_move.php') ?>',
						data: {id: prepareId(item.id), ref: prepareId(ref.id), type: type},
						dataType: 'json',
						success: function(result)
						{
							switch (result)
							{
								case 'item_not_found':
								{
									msg('Nie można przenieść strony &lt;' + itemTitle + '&gt;, ponieważ została ona usunięta.', true);

									$.tree_rollback(rb);
									noServerSide = true;
									toc.remove(item);
									noServerSide = false;

									break;
								}

								case 'ref_not_found':
								{
									msg('Nie można przenieść strony &lt;' + itemTitle + '&gt;, ponieważ strona &lt;' + refTitle + '&gt; nie istnieje.', true);

									$.tree_rollback(rb);
									noServerSide = true;
									toc.remove(ref);
									noServerSide = false;

									break;
								}

								default:
								{
									msg('Strona &lt;' + itemTitle + '&gt; została przeniesiona pomyślnie.');
									
									break;
								}
							}
						},
						error: function(data)
						{
							msg('Strona &lt;' + itemTitle + '&gt; nie została przeniesiona.', true);

							$.tree_rollback(rb);
						},
						complete: stopWaiting
					});
				}
				<? endif ?>
			}
		});

		<? if ($canManage): ?>
		var toc  = $.tree_reference('toc');
		var menu = toc.settings.ui.context;

		menu[0].label = 'Nowa podstrona';
		menu[2].label = 'Zmień tytuł strony';
		menu[3].label = 'Usuń stronę';

		var editor;

		CKEDITOR.config.language = 'pl';
		CKEDITOR.config.toolbar  =
		[
			['Source','-','NewPage'],
			['Cut','Copy','Paste','PasteText','PasteFromWord'],
			['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
			'/',
			['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
			['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
			['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
			['Link','Unlink','Anchor'],
			['Image','Table','HorizontalRule','SpecialChar'],
			'/',
			['Styles','Format','Font','FontSize'],
			['TextColor','BGColor'],
			['Maximize', 'ShowBlocks','-','About']
		];

		$('#pageForm').submit(savePage);
		$('#pageForm-cancel').click(cancelPageEdit);

		function editPage()
		{
			toggleLock(true);

			if ($('#page-contents').size() == 0)
			{
				$('#page').prepend('<div id="page-contents"></div>');
			}

			$('#editor').val($('#page-contents').html());

			editor = CKEDITOR.replace('editor');

			var title = $.trim($('#page-title').text());
			var tags = [];
			$('#tags a').each(function(i) { tags.push(this.innerHTML); });

			$('.page-title').text(title);
			$('#pageForm-title').val(title);
			$('#pageForm-tags').val(tags.join(', '));
			
			$('#block-page').hide();
			$('#block-editor').show();
		}
		function savePage()
		{
			var submit = $('#pageForm .form-actions input[type="submit"]').attr('disabled', 'disabled');
			var title = $('#pageForm-title').val();

			$.ajax({
				type: 'POST',
				url: '<?= url_for('help/do_edit.php') ?>',
				dataType: 'html',
				data: {id: current, title: title, tags: $('#pageForm-tags').val(), contents: editor.getData()},
				success: function(html)
				{
					$('#page-title').text($('#pageForm-title').val());
					$('#help_' + current + ' a:first').text($('#page-title').text());
					$('#page').html(html);

					msg('Strona &lt;' + title + '&gt; została zmieniona pomyślnie.');

					cancelPageEdit();
				},
				error: function(xhr)
				{
					msg('Strona nie została zmieniona.</p><p>' + xhr.responseText, true);
				},
				complete: function()
				{
					submit.removeAttr('disabled');
				}
			});

			return false;
		}
		function cancelPageEdit(success)
		{
			$('#block-editor').hide();
			$('#block-page').show();

			editor.destroy();
			editor = null;

			$('.page-title').text('');
			$('#pageForm-title').val('');
			$('#pageForm-tags').val('');

			toggleLock(false);

			return false;
		}
		<? endif ?>
	});
</script>
<? append_slot() ?>
