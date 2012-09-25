<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_REQUEST['page']));

no_access_if_not_allowed('catalog/manage');

$page = fetch_one('SELECT * FROM catalog_card_pages WHERE id=? LIMIT 1', array(1 => $_REQUEST['page']));

not_found_if(empty($page));

$layouts = catalog_get_card_layouts();

bad_request_if(empty($layouts[$page->layout]));

$product = catalog_get_card_product($page->product);

bad_request_if(empty($product));

$canManageProducts = true;

include $layouts[$page->layout]->templateFile;
