<?php
namespace ffan\dop;

require_once '../vendor/autoload.php';

new XmlProtocol(__DIR__ . '/protocol', 'demo/role.xml');

$all_protocol = ProtocolManager::getAll();

/** @var Struct $struct */
foreach ($all_protocol as $struct) {
    echo $struct->getNamespace() .':'. $struct->getClassName(), PHP_EOL;
    
    //print_r($struct->getAllItem());
}
