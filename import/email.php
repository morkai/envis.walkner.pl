#! /usr/bin/php-5.3
<?php

define('ENVIS_IMPORT_EMAIL_FALLBACK', 'import@envis.walkner.pl');
define('ENVIS_IMPORT_EMAIL_SERVICE', 'service@envis.walkner.pl');
define('ENVIS_IMPORT_EMAIL_PRODUCTS', 'products@envis.walkner.pl');

$fd = fopen('php://stdin', 'r');
$email = '';

while (!feof($fd))
{
  $email .= fread($fd, 1024);
}

fclose($fd);

$fromImport = true;

include_once __DIR__ . '/../_lib_/mime-mail-parser/MimeMailParser.php';
require_once __DIR__ . '/../_lib_/swiftmailer/swift_required.php';
include_once __DIR__ . '/../_common.php';

if (!function_exists('mailparse_msg_create'))
{
  dl('mailparse-php-5.3.so');
}

function extract_email($email)
{
  $pos = strpos($email, '<');

  return trim($pos === false ? $email : substr($email, $pos + 1, strpos($email, '>') - $pos - 1));
}

$err = false;
$db = get_conn();
$fromEmail = '';
$toEmail = '';
$subject = '';
$replyText = '';

try
{
  $db->beginTransaction();

  $parser = new MimeMailParser();
  $parser->setText($email);

  $from = iconv_mime_decode($parser->getHeader('from'), 0, 'UTF-8');
  $fromEmail = extract_email($from);
  $to = iconv_mime_decode($parser->getHeader('to'), 0, 'UTF-8');
  $toEmail = extract_email($to);
  $subject = trim(iconv_mime_decode($parser->getHeader('subject'), 0, 'UTF-8'));
  $body = trim($parser->getMessageBody('text'));
  $attachments = array_map(function($attachment)
  {
    $name = iconv_mime_decode($attachment->getFilename(), 0, 'UTF-8');
    $type = $attachment->getContentType();
    $data = $attachment->getContent();
    $size = mb_strlen($data);
    $ext = $attachment->getFileExtension();

    return array(
      'name' => $name,
      'type' => $type,
      'size' => $size,
      'data' => $data,
      'ext' => $ext
    );
  }, $parser->getAttachments());

  $user = fetch_one('SELECT id, name, email FROM users WHERE email=? LIMIT 1', array(1 => $fromEmail));

  if (!$user)
  {
    throw new Exception("Niedozwolony nadawca: {$fromEmail}");
  }

  switch ($toEmail)
  {
    case ENVIS_IMPORT_EMAIL_SERVICE:
      include_once __DIR__ . '/email_service.php';
      break;

    case ENVIS_IMPORT_EMAIL_PRODUCTS:
      include_once __DIR__ . '/email_products.php';
      break;

    case ENVIS_IMPORT_EMAIL_FALLBACK:
      include_once __DIR__ . '/email_fallback.php';
      break;

    default:
      throw new Exception("Nierozpoznany nadawca: {$toEmail}");
      break;
  }

  $db->commit();
}
catch (Exception $x)
{
  $err = true;

  $db->rollBack();

  $replyText = "Nie udało się zaimportować plików.\r\n{$x->getMessage()}";
}

if (!empty($replyText) && !empty($fromEmail) && !empty($toEmail))
{
  $mailer = Swift_Mailer::newInstance(Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'tls')
    ->setUsername(ENVIS_SMTP_USER)
    ->setPassword(ENVIS_SMTP_PASS));

  $mailer->send(Swift_Message::newInstance()
    ->setSubject("Re: {$subject}")
    ->setFrom(ENVIS_SMTP_FROM_EMAIL, ENVIS_SMTP_FROM_NAME)
    ->setTo($fromEmail)
    ->setBody($replyText)
    ->setReplyTo($toEmail));
}

if ($err)
{
  file_put_contents(ENVIS_UPLOADS_PATH . '/import.txt', $replyText);
}
