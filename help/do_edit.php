<?php

include_once __DIR__ . '/_common.php';

bad_request_if(empty($_POST['id']));

no_access_if_not_allowed('help*');

$errors = array();

$id = (int)$_POST['id'];

if (empty($_POST['title']) || (trim($_POST['title']) === ''))
{
  $errors[] = 'Tytuł jest wymagany.';
}
else
{
  $title = $_POST['title'];
}

if (empty($_POST['contents']) || (($_POST['contents'] = trim($_POST['contents'])) === '') || preg_match('#^<p>\s*</p>$#s', $_POST['contents']))
{
  $contents = '';
}
else
{
  $contents = $_POST['contents'];
}

if (empty($_POST['tags']) || !count($tags = explode(',', trim($_POST['tags']))))
{
  $tags = array();
}
else
{
  $tags = array_map('trim', $tags);
}

$parent = empty($_POST['parent']) ? null : (int)$_POST['parent'];

$conn = get_conn();

if (empty($errors))
{
  try
  {
    $conn->beginTransaction();

    exec_stmt('DELETE FROM help_tags WHERE page=:page', array(':page' => $id));

    if (!empty($tags))
    {
      $query = 'INSERT INTO help_tags (page, tag) VALUES';

      foreach ($tags as $tag)
      {
        $query .= '(' . $id . ', ' . $conn->quote($tag, PDO::PARAM_STR) . '),';
      }

      $conn->exec(substr($query, 0, -1));
    }

    exec_stmt('UPDATE help SET title=:title, contents=:contents WHERE id=:id', array(':title' => $title, ':contents' => $contents, ':id' => $id));

    $conn->commit();

    help_render_page(help_fetch_page($id));
    exit;
  }
  catch (PDOException $x)
  {
    $conn->rollBack();

    $errors[] = $x->getMessage();
  }
}

header('HTTP/1.1 400 Bad Request');

echo implode('<br>', $errors);
