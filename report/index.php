<?php

include_once __DIR__ . '/../_common.php';

no_access_if_not_allowed('report*');

?>

<? decorate("Raporty") ?>

<div class="block">
  <div class="block-header">
    <h1 class="block-name">Raporty</h1>
  </div>
  <div class="block-body">
    <ul>
      <? if (is_allowed_to('report/avg_values')): ?>
      <li>
        <a href="<?= url_for('report/avg_values.php') ?>">Średnie wartości zmiennych</a>
        <p>Wykres średnich wartości wybranej zmiennej dla wybranych urządzeń z pewnego okresu czasu.</p>
      <? endif ?>
      <? if (is_allowed_to('report/values')): ?>
      <li>
        <a href="<?= url_for('report/values.php') ?>">Wartości zmiennych</a>
        <p>Wykres wszystkich wartości wybranej zmiennej dla wybranych urządzeń z pewnego okresu czasu.</p>
      <? endif ?>
    </ul>
  </div>
</div>
