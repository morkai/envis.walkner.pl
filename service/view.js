$(function()
{
  let maxPanelHeight = 0;
  let prevScrollY = window.scrollY;

  $('#newAssignee').autocomplete({
    source: function(request, response)
    {
      var names = [];
      var owner = $('#owner')[0];

      if (owner && $.trim(owner.innerHTML) !== '-')
      {
        names.push($.trim(owner.innerHTML));
      }

      $('.assignee').each(function(_, el) { names.push($.trim(el.innerHTML)); });

      $.getJSON('fetch_people.php', {term: request.term}, function(people)
      {
        var peoples = [];

        for (var i in people)
        {
          if ($.inArray(people[i], names) === -1)
          {
            peoples.push(people[i]);
          }
        }

        response(peoples);
      });
    }
  });

  $('#issueTabs').bind('inview', function(_, visible)
  {
    if (visible)
    {
      $(this).unbind('inview');

      fetchActivity();
    }
  }).tabs({
    cache: true,
    load(e, ui)
    {
      $(ui.panel).find(".ui-tabs-loading").remove();

      adjustViewportHeight(ui.panel);
    },
    select(e, ui)
    {
      const $newPanel = $(ui.panel).data('oldTab', $('#issueTabs .ui-state-active a').attr('href'));

      if ($newPanel.is(":empty"))
      {
        $newPanel.append('<div class="ui-tabs-loading">Ładowanie...</div>');
      }

      prevScrollY = document.scrollingElement.scrollTop;
    },
    show(e, ui)
    {
      adjustViewportHeight(ui.panel);
    }
  });

  function adjustViewportHeight(panelEl)
  {
    const panelHeight = panelEl.getBoundingClientRect().height;

    if (panelHeight === maxPanelHeight)
    {
      return;
    }

    const diff = maxPanelHeight - panelHeight;

    maxPanelHeight = Math.max(maxPanelHeight, panelHeight);

    document.body.style.marginBottom = Math.max(0, diff) + 'px';
    document.scrollingElement.scrollTop = prevScrollY;
  }

  $('#goToUpdateIssueForm').click(function()
  {
    $('#issueTabs').tabs('select', 0);
    $('#updateIssueFormComment').focus();

    return false;
  });

  function fetchActivity()
  {
    var $activity = $('#activity');

    $activity.load($activity.attr('data-href'));
  }
});
