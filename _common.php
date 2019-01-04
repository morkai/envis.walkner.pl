<?php

if (!isset($fromImport)
  && $_SERVER['REQUEST_SCHEME'] !== 'https'
  && strpos($_SERVER['REQUEST_URI'], '/offers/print/') === false)
{
  header('HTTP/1.1 301 Moved Permanently');
  header("Location: https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
  exit;
}

date_default_timezone_set('UTC');
ini_set('default_charset', 'utf-8');

$__start__ = microtime(true);
$__debug__ = '';

include_once __DIR__ . '/_config.php';
include_once __DIR__ . '/_lib_/User.php';
include_once __DIR__ . '/_common_url.php';

$isFromImport = isset($fromImport);
$fromConfig = true;

if (!$isFromImport)
{
  include_once __DIR__ . '/import.php';

  auth();
}

if (!(isset($bypassAuth) || $isFromImport) && !isset($_SESSION['user']))
{
  go_to('user/login.php?referer=' . (!empty($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : ''));
}

function auth()
{
  $domain = preg_match('/^localhost/', ENVIS_DOMAIN) ? '' : ENVIS_DOMAIN;

  session_name('envis');
  session_set_cookie_params(time() + 3600, ENVIS_BASE_URL, $domain, false, true);

  if (isset($_REQUEST[session_name()]))
  {
    session_id($_REQUEST[session_name()]);
  }

  session_start();
}

function debug($text)
{
  global $__debug__;

  $__debug__ .= "\n" . $text;
}

function get_privilages()
{
  static $privilages = null;

  if ($privilages === null)
  {
    $privilages = include dirname(__FILE__) . '/_privilages.php';
  }

  return $privilages;
}

function is_allowed_to($privilage)
{
  if (empty($_SESSION['user']))
  {
    return false;
  }

  return $_SESSION['user']->isAllowedTo($privilage);
}

function has_access_to_factory($factory)
{
  if (empty($_SESSION['user']))
  {
    return false;
  }

  return $_SESSION['user']->hasAccessToFactory((int)$factory);
}

function has_access_to_machine($machine)
{
  if (empty($_SESSION['user']))
  {
    return false;
  }

  return $_SESSION['user']->hasAccessToMachine($machine);
}

function get_allowed_factories($pattern)
{
  if ($_SESSION['user']->isSuper())
  {
    return '';
  }

  return sprintf($pattern, implode(', ', $_SESSION['user']->getAllowedFactoryIds()));
}

function get_allowed_machines($pattern)
{
  if ($_SESSION['user']->isSuper())
  {
    return '';
  }

  return sprintf($pattern, list_quoted($_SESSION['user']->getAllowedMachineIds()));
}

/**
 * @return PDO
 */
function get_conn()
{
  static $conn;

  if ($conn === null)
  {
    $conn = new PDO(ENVIS_PDO_DSN, ENVIS_PDO_USER, ENVIS_PDO_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES 'utf8'");
    $conn->exec("SET time_zone='+00:00'");
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
  }

  return $conn;
}

function _exec_query_with_set($query, $bindings)
{
  $set = '';

  foreach ($bindings as $field => $_)
  {
    if ($field[0] === ':')
    {
      $field = substr($field, 1);
    }

    $set .= ' `' . $field . '`=:' . $field . ',';
  }

  $stmt = prepare_stmt(sprintf($query, substr($set, 0, -1)));

  foreach ($bindings as $field => $value)
  {
    $stmt->bindValue(($field[0] === ':' ? '' : ':') . $field, $value);
  }

  $stmt->execute();

  return $stmt;
}

function exec_insert($table, $bindings)
{
  return _exec_query_with_set('INSERT INTO ' . $table . ' SET %s', $bindings);
}

function exec_update($table, $bindings, $condition)
{
  return _exec_query_with_set('UPDATE ' . $table . ' SET %s WHERE ' . $condition, $bindings);
}

/**
 * @param  string $query
 * @param  array $bindings
 * @return PDOStatement
 */
function prepare_stmt($query, array $bindings = array())
{
  $stmt = get_conn()->prepare($query);

  foreach ($bindings as $k => $v)
  {
    $stmt->bindValue($k, $v);
  }

  return $stmt;
}

/**
 * @param  string $query
 * @param  array $bindings
 * @return PDOStatement
 */
function exec_stmt($query, array $bindings = array())
{
  if ($query instanceof PDOStatement)
  {
    $stmt = $query;

    foreach ($bindings as $k => $v)
    {
      $stmt->bindValue($k, $v);
    }
  }
  else
  {
    $stmt = prepare_stmt($query, $bindings);
  }

  $stmt->execute();

  return $stmt;
}

/**
 * @param  string $query
 * @param  array $bindings
 * @return object
 */
function fetch_one($query, array $bindings = array())
{
  return exec_stmt($query, $bindings)->fetch(PDO::FETCH_OBJ);
}

/**
 * @param  string $query
 * @param  array $bindings
 * @return array<object>
 */
function fetch_all($query, array $bindings = array())
{
  return exec_stmt($query, $bindings)->fetchAll(PDO::FETCH_OBJ);
}

/**
 * @param  string $query
 * @param  array $bindings
 * @return array<string,mixed>
 */
function fetch_array($query, $bindings = array())
{
  $array = array();

  foreach (fetch_all($query, $bindings) as $row)
  {
    $array[$row->key] = escape($row->value);
  }

  return $array;
}

/**
 * @param  string $location
 */
function go_to($location)
{
  if (strpos($location, '://') === false)
  {
    $location = url_for($location, true);
  }

  if (!is_ajax())
  {
    header('Location: ' . str_replace('&amp;', '&', $location));
  }

  exit;
}

function no_content()
{
  header('HTTP/1.1 204 No Content');

  exit;
}

function internal_server_error($message = '')
{
  header('HTTP/1.1 500 Internal Server Error');

  echo $message;
  exit;
}

function bad_request($contents = '')
{
  header('HTTP/1.1 400 Bad Request');

  echo $contents;
  exit;
}

function bad_request_if($condition)
{
  if ($condition)
  {
    bad_request();
  }
}

function not_found()
{
  header('HTTP/1.1 404 Not Found');
  exit;
}

function not_found_if($condition)
{
  if ($condition)
  {
    not_found();
  }
}

function no_access()
{
  global $__start__;

  header('HTTP/1.1 403 Forbidden');

  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
  {
    exit;
  }

?>
<? decorate('Brak dostępu') ?>
<div class="block">
  <div class="block-header error">
    <h1 class="block-name">Brak dostępu</h1>
  </div>
  <div class="block-body">
    <p>Niestety, ale nie masz uprawień wymaganych do wykonania żądanej akcji.</p>
  </div>
</div>
<?php
  exit;
}

function no_access_if($cond)
{
  $conds = func_get_args();

  foreach ($conds as $cond)
  {
    if ($cond) no_access();
  }
}

function no_access_if_not($cond1)
{
  $conds = func_get_args();

  foreach ($conds as $cond)
  {
    if (!$cond) no_access();
  }
}

function no_access_if_not_allowed($privilage)
{
  no_access_if(!is_allowed_to($privilage));
}

function is_ajax()
{
  static $is = null;

  if ($is === null)
  {
    $is = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
  }

  return $is;
}

function output_json($value = array(), $callback = null)
{
  if (empty($callback))
  {
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
    {
      header('Content-Type: text/html; charset=UTF-8');
    }
    else
    {
      header('Content-Type: application/json; charset=UTF-8');
    }
  }
  else
  {
    header('Content-Type: text/javascript; charset=UTF-8');
  }

  if (empty($callback))
  {
    echo json_encode($value);
  }
  else
  {
    echo $callback, '(', json_encode($value), ');';
  }
  exit;
}

/**
 * @param  int $lowerBound
 * @param  mixed $value
 * @param  int $upperBound
 * @return bool
 */
function between($lowerBound, $value, $upperBound)
{
  if (is_string($value))
  {
    $value = strlen(trim($value));
  }
  elseif (is_array($value))
  {
    $value = count($value);
  }
  else
  {
    $value = (int)$value;
  }

  return ($value >= $lowerBound) && ($value <= $upperBound);
}

/**
 * @param  array $errors
 */
function render_errors(array $errors)
{
  $html = '';

  if (!empty($errors))
  {
    $html .= '<ul class="form-errors">';

    foreach ($errors as $error)
    {
      $html .= '<li>' . $error;
    }

    $html .= '</ul>';
  }

  return $html;
}


/**
 * @param  array $errors
 */
function display_errors(array $errors)
{
  echo render_errors($errors);
}

function is_empty($value)
{
  $value = trim($value);

  return empty($value);
}

function trim_var(&$string)
{
  $string = trim($string);
}

function e($string)
{
  return escape($string);
}

function escape($string)
{
  if (is_array($string))
  {
    escape_array($string);

    return $string;
  }

  return htmlspecialchars($string, ENT_COMPAT, 'utf-8');
}

function escape_var(&$string)
{
  $string = escape($string);
}

function escape_vars(&$string1, &$string2 = null, &$string3 = null, &$string4 = null)
{
  $string1 = escape($string1);
  $string2 = escape($string2);
  $string3 = escape($string3);
  $string4 = escape($string4);
}

function escape_array(array &$data)
{
  foreach ($data as &$value)
  {
    $value = escape($value);
  }
}

function fff($alt, $src, $href = null, $id = null, $class = null)
{
  $code = '<img' . (!$href && $id ? ' id="' . $id . '"' : '') . ' src="' . url_for_media('fff/' . $src . '.png') . '" alt="' . $alt . '" title="' . $alt . '">';

  if ($href)
  {
    $code = '<a' . ($id ? ' id="' . $id . '"' : '') . ' class="fff ' . $class . '" href="' . url_for($href) . '">' . $code . '</a>';
  }

  return $code;
}

function fff_link($label, $src, $href)
{
  return '<a class="fff" href="' . url_for($href) . '"><img src="' . url_for_media('fff/' . $src . '.png') . '" alt=""> ' . $label . '</a>';
}

function checked_if($condition)
{
  return $condition ? 'checked="checked"' : '';
}

function disabled_if($condition)
{
  return $condition ? 'disabled="disabled"' : '';
}

function render_choice($label, $id, $name, array $options, $selected = null, $multiple = false, $selectAttrs = array())
{
  $nameAttr = 'name="' . $name . '"';

  if (count($options) > 4)
  {
    if ($id !== null)
    {
      $selectAttrs['id'] = $id;
    }

    if ($multiple) $selectAttrs['multiple'] = 'multiple';

    $attrs = '';

    foreach ($selectAttrs as $k => $v) $attrs .= ' ' . $k . '="' . $v . '"';

    $code = '';

    if ($label !== null)
    {
      $code .= label($id, $label);
    }

    $code .= "<select $nameAttr $attrs>" . render_options($options, $selected) . '</select>';

    return $code;
  }

  $typeAttr = 'type="' . ($multiple ? 'checkbox' : 'radio') . '"';

  $code = '<fieldset><legend><label for="' . $id . '-1">' . $label . '</label></legend><ol class="form-fields">';
  $i = 0;

  settype($selected, 'array');

  foreach ($options as $value => $label)
  {
    ++$i;

    $idAttr = 'id="' . $id . '-' . $i . '"';
    $forAttr = 'for="' . $id . '-' . $i . '"';
    $checkedAttr = in_array($value, $selected) ? 'checked="checked"' : '';

    $code .= "<li><input $idAttr $nameAttr $typeAttr $checkedAttr " . 'value="' . e($value) . '"><label for="' . $id . '-' . $i . '">' . e($label) . '</label>';
  }

  return $code . '</ol></fieldset>';
}

function render_options(array $options, $selected = null, $level = 0)
{
  $code = '';
  $indent = str_repeat('&nbsp;', $level * 4);

  settype($selected, 'array');

  foreach ($options as $value => $label)
  {
    if (isset($label->value) && isset($label->label))
    {
      $value = $label->value;
      $label = $label->label;
    }

    $code .= '<option value="' . escape($value) . '"';

    if (in_array($value, $selected))
    {
      $code .= ' selected="selected"';
    }

    $code .= ' class="level-' . $level . '">' . $indent . escape($label) . '</option>';
  }

  return $code;
}

function render_grouped_options(array $options, $selected = null, $level = 0)
{
  $code = '';
  $indent = str_repeat('&nbsp;', $level * 4);

  foreach ($options as $label => $group)
  {
    if (!is_array($group))
    {
      return render_options($options, $selected, $level + 1);
    }

    if (is_array(reset($group)))
    {
      $code .= '<option value="0" disabled="disabled" class="group level-' . $level . '">' . $indent . $label . '</option>';
      $code .= render_grouped_options($group, $selected, $level + 1);
    }
    else
    {
      $code .= '<option value="0" disabled="disabled" class="group level-' . $level . '">' . $indent . $label . '</option>';
      $code .= render_options($group, $selected, $level + 1);
    }
  }

  return $code;
}

$GLOBALS['__slots__'] = array();
$GLOBALS['__slots__']['@defaults'] = array();

function has_slot($name)
{
  return !empty($GLOBALS['__slots__'][$name]);
}

function render_slot($name, $captureDefault = false)
{
  if ($captureDefault)
  {
    $GLOBALS['__slots__']['@defaults'][] = $name;

    ob_start();
  }
  elseif (isset($GLOBALS['__slots__'][$name]))
  {
    return $GLOBALS['__slots__'][$name];
  }
  else
  {
    return '';
  }
}

function end_render_slot()
{
  $default = ob_get_clean();

  if (!empty($GLOBALS['__slots__']['@defaults']))
  {
    $name = array_pop($GLOBALS['__slots__']['@defaults']);

    return !empty($GLOBALS['__slots__'][$name]) ? $GLOBALS['__slots__'][$name] : $default;
  }
}

function begin_slot($name)
{
  if (empty($GLOBALS['__slots__'][$name]))
    $GLOBALS['__slots__'][$name] = '';

  ob_start();
}

function replace_slot()
{
  end($GLOBALS['__slots__']);

  $GLOBALS['__slots__'][key($GLOBALS['__slots__'])] = ob_get_clean();
}

function append_slot()
{
  end($GLOBALS['__slots__']);

  $GLOBALS['__slots__'][key($GLOBALS['__slots__'])] .= ob_get_clean();
}

function reconstruct_date($match)
{
  $date = $match['year'];

  if (isset($match['month']))
  {
    $date .= '-' . $match['month'];
  }
  else
  {
    return $date . '-01-01 00:00:00';
  }

  if (isset($match['day']))
  {
    $date .= '-' . $match['day'];
  }
  else
  {
    return $date . '-01 00:00:00';
  }

  if (isset($match['hour']))
  {
    $date .= ' ' . $match['hour'];
  }
  else
  {
    return $date . ' 00:00:00';
  }

  if (isset($match['minute']))
  {
    $date .= ':' . $match['minute'];
  }
  else
  {
    return $date . ':00:00';
  }

  if (isset($match['second']))
  {
    $date .= ':' . $match['second'];
  }
  else
  {
    return $date . ':00';
  }

  return $date;
}

function get_referer($default = null)
{
  if (isset($_POST['referer']))
  {
    $referer = $_POST['referer'];
  }
  elseif (isset($_SERVER['HTTP_REFERER']))
  {
    $referer = $_SERVER['HTTP_REFERER'];
  }
  else
  {
    $referer = url_for($default);
  }

  return escape($referer);
}

function log_info($message)
{
  static $stmt;

  if ($stmt === null)
  {
    $stmt = prepare_stmt('INSERT INTO logs SET message=?, user=?, time=?, ip=?');
  }

  $args = func_get_args();

  $stmt->execute(array(
    call_user_func_array('sprintf', $args),
    isset($_SESSION['user']) ? $_SESSION['user']->getId() : null,
    gmdate('Y-m-d H:i:s'),
    $_SERVER['REMOTE_ADDR'],
  ));
}

function list_quoted($array)
{
  $result = '';

  $count = count($array);
  $i = 0;

  foreach ($array as $value)
  {
    $result .= '"' . addslashes($value) . '"';

    if ($i++ < $count) $result .= ',';
  }

  return substr($result, 0, -1);
}

function prep_js_id($id)
{
  return preg_replace('/[^A-Za-z0-9-]/', '-', $id);
}

function label($for, $text, $required = false)
{
  if (substr($text, -1) === '*')
  {
    $required = true;
    $text = substr($text, 0, -1);
  }

  $label = '<label for="' . $for . '">' . $text;

  if ($required)
  {
    $label .= '<span class="form-field-required" title="Wymagane">*</span>';
  }

  return $label . '</label>';
}

function create_email_attachment($fromPath, $filename)
{
  require_once __DIR__ . '/_lib_/swiftmailer/swift_required.php';

  return Swift_Attachment::fromPath($fromPath)->setFilename($filename);
}

function create_email($receivers, $subject, $message, $replyTo = null)
{
  require_once __DIR__ . '/_lib_/swiftmailer/swift_required.php';

  $message = Swift_Message::newInstance()
    ->setSubject($subject)
    ->setFrom(ENVIS_SMTP_FROM_EMAIL, ENVIS_SMTP_FROM_NAME)
    ->setTo($receivers)
    ->setBody($message);

  if ($replyTo === null)
  {
    $message->setReplyTo(ENVIS_SMTP_REPLY_EMAIL, ENVIS_SMTP_REPLY_NAME);
  }
  else
  {
    $message->setReplyTo($replyTo);
  }

  return $message;
}

function send_email($receivers, $subject, $message, $replyTo = null)
{
  send_email_message(create_email($receivers, $subject, $message, $replyTo));
}

function send_email_message(Swift_Message $message)
{
  static $mailer = null;

  require_once __DIR__ . '/_lib_/swiftmailer/swift_required.php';

  if ($mailer === null)
  {
    $transport = Swift_SmtpTransport::newInstance(ENVIS_SMTP_HOST, ENVIS_SMTP_PORT, ENVIS_SMTP_SECURITY)
      ->setUsername(ENVIS_SMTP_USER)
      ->setPassword(ENVIS_SMTP_PASS);

    $mailer = Swift_Mailer::newInstance($transport);
  }

  $mailer->send($message);
}

function adjust_plural_of_time($time, $_234, $other)
{
  $lastChar = substr($time, -1);
  $lastButOneChar = substr($time, -2, 1);

  return ($lastButOneChar !== '1' ) && ($lastChar == 2 || $lastChar == 3 || $lastChar == 4) ? $_234 : $other;
}

function minutes_to_text($minutes)
{
  settype($minutes, 'int');

  if ($minutes < 0)
  {
    $minutes = -$minutes;
  }

  $parts = 0;

  $minutesInHour = 60;
  $minutesInDay = 1440;
  $minutesInMonth = 43200;
  $minutesInYear = 518400;

  $years = floor($minutes / $minutesInYear);

  if ($years > 0) $minutes -= $minutesInYear * $years;

  $months = floor($minutes / $minutesInMonth);

  if ($months > 0) $minutes -= $minutesInMonth * $months;

  $days = floor($minutes / $minutesInDay);

  if ($days > 0) $minutes -= $minutesInDay * $days;

  $hours = floor($minutes / $minutesInHour);

  if ($hours > 0) $minutes -= $minutesInHour * $hours;

  $str = '';

  if ($years > 1)         $str .= ' ' . $years . ' ' . adjust_plural_of_time($years, 'lata', 'lat');
  elseif ($years == 1)    $str .= ' rok';

  if (($years > 0) && $months > 0) $str .= $days > 0 || $hours > 0 || $minutes > 0 ? ',' : ' i';

  if ($months > 1)        $str .= ' ' . $months . ' ' . adjust_plural_of_time($months, 'miesięce', 'miesięcy');
  elseif ($months == 1)   $str .= ' miesiąc';

  if (($years > 0 || $months > 0) && $days > 0) $str .= $hours > 0 || $minutes > 0 ? ',' : ' i';

  if ($days > 1)          $str .= " $days dni";
  elseif ($days == 1)     $str .= ' dzień';

  if (($years > 0 || $months > 0 || $days > 0) && $hours > 0) $str .= $minutes > 0  ? ',' : ' i';

  if ($hours > 1)         $str .= ' ' . $hours . ' ' . adjust_plural_of_time($hours, 'godziny', 'godzin');
  elseif ($hours == 1)    $str .= ' godzina';

  if (($years > 0 || $months > 0 || $days > 0 || $hours > 0) && $minutes > 0) $str .= ' i';

  if ($minutes > 1)       $str .= ' ' . $minutes . ' ' . adjust_plural_of_time($minutes, 'minuty', 'minut');
  elseif ($minutes == 1)  $str .= ' minuta';

  return $str;
}

function date_interval_to_minutes(DateInterval $interval)
{
  return (int)($interval->format('%r') . ((int)$interval->format('%a') * 60 * 24 + (int)$interval->format('%h') * 60 + (int)$interval->format('%i')));
}

function date_diff_format_do_plural($nb, $str) { return $nb > 1 ? $str . 's' : $str ; }

function date_diff_format(DateInterval $interval, $start = null, $end = null)
{
  $format = array();

  if ($interval->y !== 0)
  {
    $format[] = "%y " . date_diff_format_do_plural($interval->y, "year");
  }

  if ($interval->m !== 0)
  {
    $format[] = "%m " . date_diff_format_do_plural($interval->m, "month");
  }

  if ($interval->d !== 0)
  {
    $format[] = "%d " . date_diff_format_do_plural($interval->d, "day");
  }

  if ($interval->h !== 0)
  {
    $format[] = "%h " . date_diff_format_do_plural($interval->h, "hour");
  }

  if ($interval->i !== 0)
  {
    $format[] = "%i " . date_diff_format_do_plural($interval->i, "minute");
  }

  if ($interval->s !== 0)
  {
    if (!count($format))
    {
      return "less than a minute ago";
    }
    else
    {
      $format[] = "%s " . date_diff_format_do_plural($interval->s, "second");
    }
  }

  if (count($format) > 1)
  {
    $format = array_shift($format) . " and " . array_shift($format);
  }
  else
  {
    $format = array_pop($format);
  }

  return $interval->format($format);
}

function fetch_grid_options($grid, $view = null)
{
  $options = fetch_one('SELECT options FROM grid_views WHERE grid=? AND view=? LIMIT 1', array(1 => $grid, $view));

  if (empty($options))
  {
    return array();
  }

  return unserialize($options->options);
}

function render_grid_row($row)
{
  $html = '';

  foreach ($row as $cell)
  {
    $html .= '<td';

    if (is_array($cell))
    {
      $value = array_shift($cell);

      foreach ($cell as $k => $v)
      {
        $html .= ' ' . $k . '="' . $v . '"';
      }
    }
    else
    {
      $value = $cell;
    }

    $html .= '>' . $value;
  }

  return $html;
}

function markdown($text)
{
  static $parser = null;

  if (empty($text))
    return '';

  if (!$parser)
  {
    include __DIR__ . '/_lib_/markdown/markdown.custom.php';

    $parser = new MarkdownExtra_Parser();
  }

  return $parser->transform($text);
}

function decorate($title = '')
{
  if (!empty($title))
  {
    $title .= ' - ';
  }

  register_shutdown_function(function() use($title)
  {
    $contents = ob_get_clean();

    if (isset($_REQUEST['body']) || is_ajax())
      echo $contents;
    else
      include __DIR__ . '/_layout.php';
  });

  ob_start();
}

function set_flash($message, $type = 'success', $title = null)
{
  if (is_ajax())
  {
    return;
  }

  $_SESSION['flash'] = compact('message', 'type', 'title');
}

function render_message($message, $type = 'info', $title = null, $closable = true)
{
  $code = '<div class="message ' . $type . ' ' . ($closable ? 'closable' : '') . '">';

  if (!empty($title))
  {
    $code .= '<h5>' . $title . '</h5>';
  }

  $code .= markdown(str_replace('<', '&lt;', $message));
  $code .= '</div>';

  return $code;
}

function dash_if_empty($value)
{
  return $value === null || $value === '' ? '-' : e($value);
}

function is($method)
{
  $requestMethod = strtolower(isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']);

  return $method === $requestMethod;
}

function new_object($properties)
{
  $object = new stdClass;

  foreach ($properties as $k => $v)
  {
    $object->$k = $v;
  }

  return $object;
}

function get_file_type_from_name($name)
{
  $type = strtoupper(substr(strrchr($name, '.'), 1));

  if (preg_match('/^[A-Z0-9]{1,5}$/', $type))
  {
    return $type;
  }

  if (preg_match('/^[a-zA-Z]+:\/\//', $name))
  {
    return 'URL';
  }

  return '?';
}
