<?php

$issueId = (int)preg_replace('/^[^0-9]+/', '', $subject);

$issue = fetch_one('SELECT id, subject, creator, owner FROM issues WHERE id=? LIMIT 1', array(1 => $issueId));

if (empty($issue))
{
  throw new Exception("Zgłoszenie nie istnieje: {$issueId}");
}

if (empty($body) && empty($attachments))
{
  throw new Exception("Brak danych do zaimportowania do zgłoszenia {$issue->id}: {$issue->subject}");
}

exec_update('issues', array('updatedAt' => time()), "id={$issue->id}");

if (!empty($body))
{
  exec_insert('issue_history', array(
    'issue' => $issue->id,
    'system' => 0,
    'createdAt' => time(),
    'createdBy' => $user->id,
    'comment' => $body
  ));

  if (empty($attachment))
  {
    $replyText = "Zaktualizowano zgłoszenie {$issue->id}: {$issue->subject}";
  }
}

if (!empty($attachments))
{
  $sql = <<<SQL
INSERT INTO issue_files
SET issue=:issue,
    uploader=:uploader,
    uploadedAt=:uploadedAt,
    file=:file,
    name=:name
SQL;

  $stmt = prepare_stmt($sql);
  $filesDir = ENVIS_UPLOADS_PATH . '/issues/';
  $total = 0;

  foreach ($attachments as $attachment)
  {
    $file = md5($attachment['name'] . time() . $from) . '.' . $attachment['ext'];

    if (file_put_contents($filesDir . $file, $attachment['data']) === false)
    {
      continue;
    }

    $name = preg_replace('/\.' . preg_quote($attachment['ext']) . '$/i', '', $attachment['name']);

    exec_stmt($stmt, array(
      ':issue' => $issue->id,
      ':uploader' => $user->id,
      ':uploadedAt' => time(),
      ':file' => $file,
      ':name' => $name
    ));

    ++$total;
  }

  $replyText = "Dodano {$total} plików do zgłoszenia {$issue->id}: {$issue->subject}";
}
