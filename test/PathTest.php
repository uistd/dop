<?php

namespace ffan\dop;

require_once '../vendor/autoload.php';

$file = PhpGenerator::requirePath('demo/role/test', 'demo/aaa/bbb');
echo $file, PHP_EOL;