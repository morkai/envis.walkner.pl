$(function()
{
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
    load: function (e, ui)
    {
      $(ui.panel).find(".ui-tabs-loading").remove();
    },
    select: function (e, ui)
    {
      var $panel = $(ui.panel);

      if ($panel.is(":empty"))
      {
        $panel.append('<div class="ui-tabs-loading">Ładowanie...</div>');
      }
    }
  });

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
