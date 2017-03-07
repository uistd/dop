<?php
namespace ffan\dop;

require_once '../vendor/autoload.php';
require_once 'config.php';

$manager = new ProtocolManager(__DIR__ . '/protocol', 'build');
$manager->parseFile('demo/role.xml');
$all_protocol = $manager->getAll();

/** @var Struct $struct */
foreach ($all_protocol as $struct) {
    echo $struct->getNamespace() .':'. $struct->getClassName(), PHP_EOL;
    
    //print_r($struct->getAllItem());
}

$generator = new PhpGenerator($manager);
$generator->generate();
