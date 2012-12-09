<input id="product-category" name="product[category]" type="hidden" value="<?= $product['category'] ?>">
<fieldset>
  <legend>Nowy produkt</legend>
  <ol class="form-fields">
    <li>
      <?= label('product-category-ac', 'Kategoria*') ?>
      <input id="product-category-ac" type="text" value="<?= catalog_render_category_path($product['category'], false) ?>">
    <li>
      <?= label('product-name', 'Nazwa*') ?>
      <input id="product-name" name="product[name]" type="text" value="<?= e($product['name']) ?>" maxlength="100">
    <li>
      <?= label('product-type', 'Typ') ?>
      <input id="product-type" name="product[type]" type="text" value="<?= e($product['type']) ?>" maxlength="100">
    <li>
      <?= label('product-nr', 'Nr') ?>
      <input id="product-nr" name="product[nr]" type="text" value="<?= e($product['nr']) ?>" maxlength="30">
      <?= fff('Wyczyść nr', 'cross', null, 'clear-product-nr') ?>
      <div class="form-field-help">
        <p>Pozostawienie pustego pola sprawi, że nr zostanie stworzony na podstawie wybranego rodzaju, wykonawcy, rewizji, daty produkcji oraz ID produktu.</p>
      </div>
    <li class="horizontal">
      <ol>
        <li class="form-choice">
          <?= render_choice('Rodzaj', 'productKind', 'product[kind]', $kinds, $product['kind']) ?>
        <li class="form-choice">
          <?= render_choice('Wykonawca', 'productManufacturer', 'product[manufacturer]', $manufacturers, $product['manufacturer']) ?>
        <li>
          <?= label('product-revision', 'Rewizja') ?>
          <input id="product-revision" name="product[revision]" type="number" min="0" max="255" step="1" value="<?= e($product['revision']) ?>" maxlength="2">
        <li>
          <?= label('product-productionDate', 'Data produkcji') ?>
          <input id="product-productionDate" name="product[productionDate]" type="text" value="<?= e($product['productionDate']) ?>" maxlength="7" placeholder="YYYY-MM">
      </ol>
    <li>
      <?= label('product-description', 'Opis') ?>
      <textarea id="product-description" name="product[description]" class="markdown"><?= e($product['description']) ?></textarea>
    <li>
      <input id="product-public" name="product[public]" type="checkbox" value="1" <?= checked_if($product['public']) ?>>
      <?= label('product-public', 'Publiczny') ?>
    <li>
      <?= label('product-markings', 'Oznaczenia') ?>
      <? foreach ($markings as $marking): ?>
      <label class="product-marking">
        <input type="checkbox" name="product[markings][]" value="<?= $marking->id ?>" <?= checked_if(in_array($marking->id, $product['markings'])) ?>>
        <img src="<?= $marking->src ?>" alt="" height="25">
        <span><?= e($marking->name) ?></span>
      </label>
      <? endforeach ?>
    <li>
      <ol class="form-actions">
        <li><input type="submit" value="Zapisz produkt">
        <li><a class="cancel" href="<?= $referer ?>">Anuluj</a>
      </ol>
  </ol>
</fieldset>
