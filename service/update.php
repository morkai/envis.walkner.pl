<?php

include __DIR__ . '/_common.php';

if (empty($_REQUEST['issue']) || empty($_REQUEST['what'])) bad_request();

$target = __DIR__ . '/update/' . preg_replace('/[^a-zA-Z]/', '', $_REQUEST['what']) . '.php';

if (!file_exists($target)) bad_request();

no_access_if_not_allowed('service*');

$bindings = array(':id' => $_REQUEST['issue']);
$issue    = fetch_one('SELECT i.*, o.name AS ownerName FROM issues i LEFT JOIN users o ON o.id=i.owner WHERE i.id=:id', $bindings);

if (empty($issue)) bad_request();

define('ISSUE_ROLE_CREATOR',     1);
define('ISSUE_ROLE_DOCS_VIEWER', 2);
define('ISSUE_ROLE_ASSIGNEE',    4);
define('ISSUE_ROLE_OWNER',       8);
define('ISSUE_ROLE_ASSIGNER',    16);
define('ISSUE_ROLE_SUPER',       32);

$userId = $_SESSION['user']->getId();

$role = 0;

if (is_issue_docs_viewer($_SESSION['user'], $issue))
  $role |= ISSUE_ROLE_DOCS_VIEWER;

if ($issue->owner == $userId)
  $role |= ISSUE_ROLE_OWNER;

if (is_allowed_to('service/assigning'))
  $role |= ISSUE_ROLE_ASSIGNER;

if ($issue->creator == $userId)
  $role |= ISSUE_ROLE_CREATOR;

if (fetch_one('SELECT 1 FROM issue_assignees WHERE issue=:id AND assignee=:user', $bindings + array(':user' => $userId)))
  $role |= ISSUE_ROLE_ASSIGNEE;

if (is_allowed_to('super'))
  $role |= ISSUE_ROLE_SUPER;

if ($role === 0)
  no_access();

include $target;
