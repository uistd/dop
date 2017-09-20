<?php

namespace FFan\Dop;

require_once '../vendor/autoload.php';
require_once 'config.php';

$manager = new Manager(__DIR__ . '/protocol', 'build');
$manager->buildPhp();