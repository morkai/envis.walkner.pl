<?php

define('ENVIS_PDO_DSN', 'mysql:host=localhost;dbname=envis');
define('ENVIS_PDO_USER', 'root');
define('ENVIS_PDO_PASS', '');

define('ENVIS_SMTP_HOST', 'smtp.the.net');
define('ENVIS_SMTP_PORT', 587);
define('ENVIS_SMTP_SECURITY', 'tls');
define('ENVIS_SMTP_AUTH_MODE', 'login');
define('ENVIS_SMTP_USER', 'someone@the.net');
define('ENVIS_SMTP_PASS', 'nohax');
define('ENVIS_SMTP_FROM_EMAIL', 'someone@the.net');
define('ENVIS_SMTP_FROM_NAME', 'Some One');
define('ENVIS_SMTP_REPLY_EMAIL', 'someone@the.net');
define('ENVIS_SMTP_REPLY_NAME', 'Some One');

define('ENVIS_SUB_EMAIL', null);
define('ENVIS_SUB_NAME', null);

define('ENVIS_DOMAIN', 'localhost');
define('ENVIS_BASE_URL', '/');
define('ENVIS_MEDIA_URL', 'http://cdn.localhost/');
define('ENVIS_UPLOADS_DIR', '/_files_');
define('ENVIS_UPLOADS_PATH', __DIR__ . ENVIS_UPLOADS_DIR);

define('ENVIS_GOOGLE_KEY', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

define('ENVIS_COUNTER_VARIABLE', 'counter');

date_default_timezone_set('Europe/Warsaw');
