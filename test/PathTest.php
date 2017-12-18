<?php

namespace UiStd\Dop;

use UiStd\Dop\Build\CoderBase;

require_once '../vendor/autoload.php';

$file = CoderBase::relativePath('demo/role/test', 'demo/aaa/bbb');
echo $file, PHP_EOL;

$file = CoderBase::relativePath('demo/role/test', 'demo/role/test');
echo $file, PHP_EOL;

$file = CoderBase::relativePath('demo/role/test', 'demo');
echo $file, PHP_EOL;

$file = CoderBase::relativePath('demo', 'demo/aaa/bbb');
echo $file, PHP_EOL;

$file = CoderBase::relativePath('demo/role/test', 'demo/role/bbb');
echo $file, PHP_EOL;
