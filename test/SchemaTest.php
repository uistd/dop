<?php

namespace FFan\Dop;
require_once '../vendor/autoload.php';
require_once 'config.php';
$manager = new Manager('test/protocol');
$files = $manager->getAllFileList();
try {
    foreach ($files as $file => $md5) {
        $file = new Schema\File($manager, $file);
        print_r($file);
    }
} catch (Exception $excp) {
    echo $excp->getMessage();
}