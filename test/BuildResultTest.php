<?php
use ffan\dop\demo\role\ResponseDemoRole;

require_once 'runtime/build/php/dop.php';

$result = new ResponseDemoRole();
$result->status = 200;
$result->message = 'success';
$result->data = array(
    array(1, 2, 3, 4),
    array(1, 2, 3, 4),
    array(1, 2, 3, 4)
);
print_r($result);
