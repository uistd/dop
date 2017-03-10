<?php
namespace ffan\dop;

require_once '../vendor/autoload.php';
require_once 'config.php';

$manager = new ProtocolManager(__DIR__ . '/protocol', 'build');
$manager->build();