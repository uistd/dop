<?php
namespace ffan\dop;

require_once '../vendor/autoload.php';

$manager = new ProtocolManager(__DIR__ . '/protocol', 'build');
$obj = new PhpGenerator($manager);
$file = $obj->requirePath('demo/role/test', 'demo/aaa/bbb');
echo $file, PHP_EOL;