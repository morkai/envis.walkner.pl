<?php

include '../../_common.php';

bad_request_if(empty($_GET['term']));

no_access_if_not_allowed('catalog*');

$term = trim($_GET['term']);

$q = <<<SQL
SELECT id, nr, name
FROM catalog_products
WHERE nr LIKE '%{$term}%'
  OR name LIKE '%{$term}%'
ORDER BY name ASC
LIMIT 15
SQL;

output_json(fetch_all($q));
