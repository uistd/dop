<?php

namespace ffan\dop;

require_once '../vendor/autoload.php';
require_once 'config.php';
$manager = new Manager(__DIR__ . '/protocol');
$build_result = $manager->build();
if (true !== $build_result) {
    echo '编译失败', PHP_EOL;
}
echo $manager->getBuildLog(), PHP_EOL;
