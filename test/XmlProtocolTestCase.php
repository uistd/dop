<?php
namespace ffan\dop;

require_once '../vendor/autoload.php';

$dop = new XmlProtocol(__DIR__ . '/protocol', 'demo/role.xml');
$dop->setMatchHttpMethod(true);
$dop->parse();
$all_protocol = ProtocolManager::getAll();

/** @var Struct $struct */
foreach ($all_protocol as $struct) {
    echo $struct->getNamespace() .':'. $struct->getClassName(), PHP_EOL;
    
    //print_r($struct->getAllItem());
}
