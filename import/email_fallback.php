<?php

$total = count($attachments);

if ($total === 0)
{
  throw new Exception("Brak załączników do zaimportowania.");
}

exec_insert('emails', array(
  'createdAt' => time(),
  'from' => $from,
  'to' => $to,
  'subject' => $subject,
  'body' => $body
));

$emailId = $db->lastInsertId();

$sql = <<<SQL
INSERT INTO email_attachments
SET email=:email,
    name=:name,
    type=:type,
    size=:size,
    data=:data
SQL;

$stmt = prepare_stmt($sql);

foreach ($attachments as $attachment)
{
  exec_stmt($stmt, array(
    ':email' => $emailId,
    ':name' => $attachment['name'],
    ':type' => $attachment['type'],
    ':size' => $attachment['size'],
    ':data' => $attachment['data']
  ));
}

$replyText = "Zaimportowano {$total} plik(ów).";
