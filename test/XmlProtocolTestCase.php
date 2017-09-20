<?php

namespace FFan\Dop;

require_once '../vendor/autoload.php';
require_once 'config.php';

$manager = new Manager(__DIR__ . '/protocol', 'build');
$manager->parseFile('demo/role.xml');
$all_protocol = $manager->getAllStruct();

/** @var Struct $struct */
foreach ($all_protocol as $struct) {
    echo $struct->getNamespace() . ':' . $struct->getClassName(), PHP_EOL;

    //print_r($struct->getAllItem());
}

$generator = new PhpGenerator($manager);
$generator->generate();
