<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_POST['factory']));

no_access_if_not_allowed('factory/add');

$factory = $_POST['factory'];
$errors = array();

if (!between(1, $factory['name'], 128))
{
  $errors[] = 'Nazwa musi się składać z od 1 do 128 znaków.';
}

if (empty($errors))
{
  settype($factory['latitude'], 'float');
  settype($factory['longitude'], 'float');

  $bindings = array(1 => $factory['name'], $factory['latitude'], $factory['longitude']);

  $conn = get_conn();

  try
  {
    $conn->beginTransaction();

    exec_stmt('INSERT INTO `factories` SET `name`=?, `latitude`=?, `longitude`=?', $bindings);

    $factory['id'] = (int)get_conn()->lastInsertId();

    if (!$_SESSION['user']->isSuper())
    {
      $allowedFactories = $_SESSION['user']->getAllowedFactories();
      $allowedFactories[$factory['id']] = true;

      exec_stmt('UPDATE `users` SET allowedFactories=:factories WHERE id=:id', array(':id' => $_SESSION['user']->getId(), ':factories' => serialize($allowedFactories)));

      $_SESSION['user']->setAllowedFactories($allowedFactories);
    }

    log_info('Dodano fabrykę <%s>.', $factory['name']);

    $conn->commit();

    output_json(array('status' => true, 'factory' => $factory));
  }
  catch (PDOException $x)
  {
    $conn->rollBack();

    $errors[] = $x->getMessage();
  }
}

output_json(array('status' => false, 'errors' => $errors));

?>
