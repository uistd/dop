<?php
require_once '../vendor/autoload.php';
$file = 'protocol/build.ini';
use ffan\php\utils\Env;
use ffan\php\utils\Utils;

$config = parse_ini_file($file, true);
print_r($config);

var_dump(Env::getRootPath());
var_dump(Env::getRuntimePath());

var_dump(Utils::fixWithRootPath('build'));