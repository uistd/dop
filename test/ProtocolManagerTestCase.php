<?php
namespace ffan\dop;

require_once '../vendor/autoload.php';
require_once 'config.php';

$manager = new ProtocolManager(__DIR__ . '/protocol', 'build');
$build_result = $manager->buildPhp();
if (true !== $build_result) {
    echo '编译失败：';
} 
echo $manager->getBuildLog(), PHP_EOL;
