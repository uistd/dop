<?php
namespace ffan\dop\coder\php;

require_once '../vendor/autoload.php';

$bin_arr = new BinaryArray();
$bin_arr->writeShort(-600);
echo $bin_arr->dumpHex() . PHP_EOL;
