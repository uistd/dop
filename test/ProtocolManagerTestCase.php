<?php
namespace ffan\dop;

require_once '../vendor/autoload.php';
require_once 'config.php';
$build_config = array(
    'disable_cache' => true,
    'plugin' => array(
        'validator' => null,
        'mock' => null
    )
);
$manager = new ProtocolManager(__DIR__ . '/protocol', 'build', $build_config);
$build_result = $manager->buildPhp();
if (true !== $build_result) {
    echo '编译失败', PHP_EOL;
}
echo $manager->getBuildLog(), PHP_EOL;
