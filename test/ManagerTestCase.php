<?php

namespace ffan\dop;
require_once '../vendor/autoload.php';
require_once 'config.php';
$manager = new Manager('test/protocol');
$section_name = isset($argv[1]) ? $argv[1] : 'main';
$build_result = $manager->build($section_name);
if (true !== $build_result) {
    echo '编译失败', PHP_EOL;
}
echo $manager->getBuildLog(), PHP_EOL;
