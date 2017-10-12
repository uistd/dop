<?php

namespace FFan\Dop;
require_once '../vendor/autoload.php';
require_once 'config.php';
$manager = new Manager('test/protocol');
$files = $manager->getAllFileList();
foreach ($files as $file => $md5) {
    new Scheme\File($manager, $file);
}