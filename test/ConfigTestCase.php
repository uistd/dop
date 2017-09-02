<?php
require_once '../vendor/autoload.php';
$file = 'protocol/build.ini';
use FFan\Std\Common\Env;
use FFan\Std\Common\Utils;

$config = parse_ini_file($file, true);
print_r($config);

var_dump(Env::getRootPath());
var_dump(Env::getRuntimePath());

var_dump(Utils::fixWithRootPath('build'));