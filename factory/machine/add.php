<?php

include_once __DIR__ . '/../../_common.php';

bad_request_if(empty($_GET['factory']));

no_access_if_not(is_allowed_to('machine/add'), has_access_to_factory($_GET['factory']));

$factory = fetch_one('SELECT id, name FROM factories WHERE id=?', array(1 => $_GET['factory']));

not_found_if(empty($factory));

$referer = get_referer('factory/view.php?id=' . $_GET['factory']);
$errors = array();

if (isset($_POST['machine']))
{
  if (!between(1, $_POST['machine']['id'], 64))
  {
    $errors[] = 'ID musi się składać z od 1 do 64 znaków.';
  }

  if (!preg_match('/^[A-Za-z0-9-_:]+$/', $_POST['machine']['id']))
  {
    $errors[] = 'ID musi się składać ze znaków alfanumerycznych i -, _ lub :';
  }

  if (!between(1, $_POST['machine']['name'], 128))
  {
    $errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
  }

  if (empty($errors))
  {
    $bindings = array(1 => $_POST['machine']['name'], $_GET['factory'], $_POST['machine']['id']);

    $id = $_POST['machine']['id'];

    try
    {
      exec_stmt('INSERT INTO machines SET name=?, factory=?, id=?', $bindings);

      if (!$_SESSION['user']->isSuper())
      {
        $allowedMachines = $_SESSION['user']->getAllowedFactories();
        $allowedMachines[$id] = true;

        exec_stmt('UPDATE `users` SET allowedMachines=:machines WHERE id=:id', array(':id' => $_SESSION['user']->getId(), ':machines' => serialize($allowedMachines)));

        $_SESSION['user']->setAllowedMachines($allowedMachines);
      }

      log_info('Dodano maszynę <%s>.', $_POST['machine']['name']);

      set_flash(sprintf('Maszyna <%s> została dodana pomyślnie.', $_POST['machine']['name']));

      go_to('factory/machine/?id=' . $id);
    }
    catch (PDOException $x)
    {
      if ($x->getCode() == 26000)
      {
        not_found();
      }
      else
      {
        $errors[] = $x->getMessage();
      }
    }
  }

  $name = escape($_POST['machine']['name']);
  $id = escape($_POST['machine']['id']);
}
else
{
  $name = '';
  $id = '';
}


?>

<? decorate("Nowa maszyna w fabryce <{$factory->name}>") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Nowa maszyna</h1>
  </div>
  <div class="block-body">
    <form method="post" action="<?= url_for('factory/machine/add.php?factory=' . $factory->id) ?>">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <fieldset>
        <legend>Nowa maszyna</legend>
        <? display_errors($errors) ?>
        <ol class="form-fields">
          <li>
            <label for="machine-factory">Fabryka<span class="form-field-required" title="Wymagane">*</span></label>
            <p><?= e($factory->name) ?></p>
          <li>
            <label for="machine-name">ID<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="machine-name" name="machine[id]" type="text" maxlength="64" value="<?= $id ?>">
            <p class="form-field-help">Od 1 do 64 znaków alfanumerycznych i -, _ lub :</p>
          <li>
            <label for="machine-name">Nazwa<span class="form-field-required" title="Wymagane">*</span></label>
            <input id="machine-name" name="machine[name]" type="text" maxlength="128" value="<?= $name ?>">
            <p class="form-field-help">Od 1 do 128 znaków.</p>
          <li>
            <ol class="form-actions">
              <li><input type="submit" value="Dodaj maszynę">
              <li><a href="<?= $referer ?>">Anuluj</a>
            </ol>
        </ol>
      </fieldset>
    </form>
  </div>
</div>
