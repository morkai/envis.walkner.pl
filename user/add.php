<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('user/add');

$referer = get_referer('user');
$errors = array();

if (isset($_POST['user']))
{
  $usr = $_POST['user'];

  if (empty($usr['name']))
  {
    $errors[] = 'Imię i nazwisko jest wymagane.';
  }

  if (strlen($usr['password']) < 3)
  {
    $errors[] = 'Hasło musi mieć przynajmniej 3 znaki.';
  }

  if ($usr['password'] !== $usr['passwordConfirm'])
  {
    $errors[] = 'Podane hasła nie zgadzają się.';
  }

  if (!between(1, $usr['email'], 128))
  {
    $errors[] = 'Niepoprawny format adresu e-mail.';
  }

  $usr['super'] = 0;

  if (empty($usr['role']))
  {
    $usr['role'] = null;
  }
  elseif ($usr['role'] === 'super')
  {
    $usr['role'] = null;
    $usr['super'] = 1;
  }

  if (empty($errors))
  {
    $allowedFactories = $allowedMachines = array();

    if (!empty($usr['allowed']) && !$usr['super'])
    {
      foreach ($usr['allowed'] as $allowed)
      {
        $parts = explode('|', $allowed);

        if (isset($parts[1]))
        {
          $allowedFactories[(int)$parts[0]] = true;
          $allowedMachines[$parts[1]] = true;
        }
        else
        {
          $allowedMachines[$parts[0]] = true;
        }
      }
    }

    $bindings = array(
      ':email' => $usr['email'],
      ':password' => hash('sha256', $usr['password']),
      ':name' => $usr['name'],
      ':createdAt' => gmdate('Y-m-d H:i:s'),
      ':super' => $usr['super'],
      ':role' => $usr['role'],
      ':factories' => serialize($allowedFactories),
      ':machines' => serialize($allowedMachines),
    );

    try
    {
      exec_stmt('INSERT INTO users SET email=:email, `password`=:password, name=:name, createdAt=:createdAt, role=:role, super=:super, allowedFactories=:factories, allowedMachines=:machines', $bindings);

      $id = get_conn()->lastInsertId();

      log_info('Dodano użytkownika <%s>.', $usr['name']);

      set_flash(sprintf('Użytkownik <%s> został dodany pomyślnie', $usr['name']));

      go_to('user/view.php?id=' . $id);
    }
    catch (PDOException $x)
    {
      if ($x->getCode() == 23000)
      {
        $errors[] = 'Adres e-mail jest już wykorzystywany przez innego użytkownika.';
      }
      else
      {
        throw $x;
      }
    }
  }

  escape_array($usr);
}
else
{
  $usr = array(
    'email' => '',
    'name' => '',
    'allowed' => array(),
    'role' => 'user',
  );
}


$roles = array('super' => 'Super administrator');
$roles += fetch_array('SELECT id AS `key`, name AS `value` FROM roles ORDER BY name ASC');

class Factory
{
  public $id;

  public $name;

  private $machines;

  public function __construct($id, $name)
  {
    $this->id = $id;
    $this->name = $name;
    $this->machines = array();
  }

  public function addMachine(Machine $machine)
  {
    $this->machines[$machine->id] = $machine;
  }

  public function asOption($selected = array())
  {
    $value = $this->id;

    $code = '<option class="factory" value="' . $value . '"';

    if (in_array($value, $selected))
    {
      $code .= ' selected="selected"';
    }

    $code .= '>' . escape($this->name);

    foreach ($this->machines as $machine)
    {
      $code .= $machine->asOption($selected, $this->id);
    }

    return $code;
  }
}

class Machine
{
  public $id;

  public $name;

  public function __construct($id, $name)
  {
    $this->id = $id;
    $this->name = $name;
  }

  public function asOption($selected, $factory)
  {
    $value = $factory . '|' . $this->id;

    $code = '<option class="machine-' . $factory . '" value="' . $value . '"';

    if (in_array($value, $selected))
    {
      $code .= ' selected="selected"';
    }

    $code .= '>&nbsp;&nbsp;&nbsp;' . escape($this->name);

    return $code;
  }
}
$query = <<<SQL
SELECT
  e.id, e.name, e.machine, m.factory, m.name AS machineName, f.name AS factoryName
FROM engines e
INNER JOIN machines m ON m.id=e.machine
INNER JOIN factories f ON f.id=m.factory
ORDER BY factoryName, machineName, e.name
SQL;

$query = <<<SQL
SELECT m.id AS machine, m.name AS machineName, m.factory, f.name AS factoryName
FROM machines m
INNER JOIN factories f
  ON f.id=m.factory
ORDER BY f.name ASC, m.name ASC
SQL;

$factories = array();

$factory = null;
$machine = null;

foreach (fetch_all($query) as $row)
{
  if (!isset($factories[$row->factory]))
  {
    $factory = new Factory($row->factory, $row->factoryName);
    $machine = null;

    $factories[$row->factory] = $factory;
  }

  if (!$machine || ($machine->id != $row->machine))
  {
    $machine = new Machine($row->machine, $row->machineName);

    $factory->addMachine($machine);

    if (isset($allowedMachines[$machine->id]))
    {
      $usr['allowed'][$factory->id . '|' . $machine->id] = $machine->name;
    }
  }
}

unset($factory, $machine);

?>
<? begin_slot('head') ?>
<style>
  #user-allowed { min-height: 22em; }
  #user-allowed .factory { font-weight: bold; }
</style>
<? append_slot() ?>

<? decorate("Nowy użytkownik") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowy użytkownik</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('user/add.php') ?>" autocomplete="off">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Nowy użytkownik</legend>
        <? display_errors($errors) ?>
        <div class="yui-gd">
          <div class="yui-u first">
            <ol class="form-fields">
              <li class="form-choice">
                <?= render_choice('Rola', 'user-role', 'user[role]', $roles, $usr['role']) ?>
              <li>
                <label for="user-name">Imię i nazwisko<span class="form-field-required" title="Wymagane">*</span></label>
                <input id="user-name" name="user[name]" type="text" maxlength="128" value="<?= $usr['name'] ?>">
              <li>
                <label for="user-email">E-mail<span class="form-field-required" title="Wymagane">*</span></label>
                <input id="user-email" name="user[email]" type="text" maxlength="128" value="<?= $usr['email'] ?>">
                <p class="form-field-help">Do 128 znaków.</p>
                <p class="form-field-help">Musi być unikalny.</p>
              <li>
                <label for="user-password">Hasło<span class="form-field-required" title="Wymagane">*</span></label>
                <input id="user-password" name="user[password]" type="password" maxlength="256" value="">
                <p class="form-field-help">Przynajmniej 3 znaki.</p>
              <li>
                <label for="user-passwordConfirm">Potwierdzenie hasła<span class="form-field-required" title="Wymagane">*</span></label>
                <input id="user-passwordConfirm" name="user[passwordConfirm]" type="password" maxlength="256" value="">
              <li>
                <ol class="form-actions">
                  <li><input type="submit" value="Dodaj użytkownika">
                  <li><a href="<?= $referer ?>">Anuluj</a>
                </ol>
            </ol>
          </div>
          <div class="yui-u">
            <ol class="form-fields">
              <li>
                <label for="user-allowed">Dostępne fabryki i maszyny</label>
                <select id="user-allowed" name="user[allowed][]" multiple="true">
                <? foreach ($factories as $factory): ?>
                  <?= $factory->asOption($usr['allowed']) ?>
                <? endforeach ?>
                </select>
            </ol>
          </div>
        </div>
      </fieldset>
    </form>
  </div>
</div>

<? begin_slot('js') ?>
<script>
$(function()
{
  $('select[name="user[role]"]').change(function()
  {
    if (this.value === 'super')
    {
      $('#user-allowed')
        .attr('disabled', 'disabled')
        .attr('title', 'Super administrator ma dostęp do wszystkich fabryk.');
    }
    else
    {
      $('#user-allowed').removeAttr('disabled').removeAttr('title');
    }
  }).change();

  $('#user-allowed option').mousedown(function(e)
  {
    if ($('#user-allowed').attr('disabled'))
    {
      return false;
    }

    $('#user-allowed').focus();

    var pos = this.value.indexOf('|');

    if (pos == -1)
    {
      var selected = this.selected = !this.selected;

      $(this).nextAll('.machine-' + this.value).each(function(i, el)
      {
        el.selected = selected;
      });
    }
    else
    {
      this.selected = !this.selected;
    }

    return false;
  });
});
</script>
<? append_slot() ?>
