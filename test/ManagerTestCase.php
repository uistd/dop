<?php

namespace UiStd\Dop;
require_once '../vendor/autoload.php';
require_once 'config.php';
$manager = new Manager('test/protocol');
$manager->registerPacker('fix_data', __DIR__ .'/FixDataPack.php');
$section_name = isset($argv[1]) ? $argv[1] : 'main';
$build_result = $manager->build($section_name);
if (true !== $build_result) {
    echo '编译失败', PHP_EOL;
}
echo $manager->getBuildLog(), PHP_EOL;
