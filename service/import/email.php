#! /usr/bin/php-5.3
<?php

$err = __DIR__ . '/../../_files_/error.txt';

$fd = fopen('php://stdin', 'r');
$email = '';

while (!feof($fd))
{
  $email .= fread($fd, 1024);
}

fclose($fd);

$fromImport = true;

include_once __DIR__ . '/../../_lib_/mime-mail-parser/MimeMailParser.php';
include_once __DIR__ . '/../../_common.php';

if (!function_exists('mailparse_msg_create'))
{
  dl('mailparse-php-5.3.so');
}

function extract_email($email)
{
  $pos = strpos($email, '<');

  return trim($pos === false ? $email : substr($email, $pos + 1, strpos($email, '>') - $pos - 1));
}

try
{
  $parser = new MimeMailParser();
  $parser->setText($email);

  $from = iconv_mime_decode($parser->getHeader('from'), 0, 'UTF-8');
  $to = iconv_mime_decode($parser->getHeader('to'), 0, 'UTF-8');
  $subject = trim(iconv_mime_decode($parser->getHeader('subject'), 0, 'UTF-8'));
  $body = trim($parser->getMessageBody('text'));
  $attachments = array_map(function($attachment)
  {
    $name = iconv_mime_decode($attachment->getFilename(), 0, 'UTF-8');
    $type = $attachment->getContentType();
    $data = $attachment->getContent();
    $size = mb_strlen($data);
    $ext  = $attachment->getFileExtension();

    return array(
      'name' => $name,
      'type' => $type,
      'size' => $size,
      'data' => $data,
      'ext'  => $ext
    );
  }, $parser->getAttachments());

  $db = get_conn();
  $db->beginTransaction();

  $fromEmail = extract_email($from);

  $user = fetch_one('SELECT id, name, email FROM users WHERE email=? LIMIT 1', array(1 => $fromEmail));

  if (!$user)
  {
    throw new Exception('Niedozwolony nadawca: ' . $fromEmail);
  }

  $toEmail = extract_email($to);

  if ($to === 'service@envis.walkner.pl')
  {
    if (is_numeric($subject))
    {
      $issue = fetch_one('SELECT id, creator, owner FROM issues WHERE id=? LIMIT 1', array(1 => (int)$subject));

      if ($issue)
      {
        if (!empty($body))
        {
          exec_insert('issue_history', array(
            'issue' => $issue->id,
            'system' => 0,
            'createdAt' => time(),
            'createdBy' => $user->id,
            'comment' => $body
          ));
        }

        if (!empty($attachments))
        {
          $stmt = prepare_stmt('INSERT INTO issue_files SET issue=:issue, uploader=:uploader, uploadedAt=:uploadedAt, file=:file, name=:name');
          $filesDir = __DIR__ . '/../../_files_/issues/';

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
          }
        }

        $db->commit();

        exit(0);
      }
    }
  }

  exec_insert('emails', array(
    'createdAt' => time(),
    'from' => $from,
    'to' => $to,
    'subject' => $subject,
    'body' => $body
  ));

  $emailId = $db->lastInsertId();

  if (!empty($attachments))
  {
    $stmt = prepare_stmt('INSERT INTO email_attachments SET `email`=:email, `name`=:name, `type`=:type, `size`=:size, `data`=:data');

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
  }

  $db->commit();
}
catch (Exception $x)
{
  $db->rollBack();

  $err = fopen('php://stderr', 'w');
  fwrite($err, $x->getMessage());
  fclose($err);
  exit(1);
}
