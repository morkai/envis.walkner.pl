<?php

include_once __DIR__ . '/_common.php';

bad_request_if(!array_key_exists('view', $_POST));

no_access_if_not_allowed('service*');

if ($_POST['view'] === '0')
{
  exec_stmt("DELETE FROM grid_view_defaults WHERE grid='service/issues' AND user=? LIMIT 1",
            array(1 => $_SESSION['user']->getId()));

  set_flash('Zresetowano domyślny widok zgłoszeń na standardowy.');

  go_to('service/');
}

bad_request_if(!is_valid_view_id($_POST['view']));

$view = fetch_one("SELECT name, creator, public FROM grid_views WHERE grid='service/issues' AND view=? LIMIT 1",
                  array(1 => $_POST['view']));

not_found_if(empty($view));

no_access_if(!$view->public && $_SESSION['user']->getId() != $view->creator);

exec_stmt("REPLACE INTO grid_view_defaults SET grid='service/issues', user=?, view=?",
          array(1 => $_SESSION['user']->getId(), $_POST['view']));

set_flash(sprintf('Ustawiono domyślny widok zgłoszeń na <%s>.', $view->name));

go_to('service/');
