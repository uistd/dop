<?php

namespace ffan\dop;

use ffan\dop\build\BuildOption;

require_once '../vendor/autoload.php';
require_once 'config.php';
$build_config = array(
    'disable_cache' => true,
    'plugin' => array(
        'validator' => null,
        'mock' => null
    )
);
$manager = new Manager(__DIR__ . '/protocol', $build_config);
$build_opt = new BuildOption();
$build_opt->addPacker('json');
$build_opt->build_path = __DIR__ . '/runtime/build';
$build_result = $manager->buildPhp($build_opt);
if (true !== $build_result) {
    echo '编译失败', PHP_EOL;
}
echo $manager->getBuildLog(), PHP_EOL;
