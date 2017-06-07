<?php

namespace ffan\dop\coder\php;

require_once '../vendor/autoload.php';
require_once 'runtime/build/php/dop.php';
use ffan\dop\BinaryBuffer;

$bin_arr = new BinaryBuffer();
$bin_arr->writeChar(0x7f);
$bin_arr->writeChar(-0x7f);
$bin_arr->writeChar(0xff);

$bin_arr->writeShort(0x7fff);
$bin_arr->writeShort(-0x7fff);
$bin_arr->writeShort(0xffff);

$bin_arr->writeInt(0x7fffffff);
$bin_arr->writeInt(-0x7fffffff);
$bin_arr->writeInt(0xffffffff);

$bin_arr->writeBigInt(0x7fffffffffffffff);
$bin_arr->writeBigInt(-0x7fffffffffffffff);
$bin_arr->writeBigInt(0xffffffffffffffff);

$bin_arr->writeString(null);
$bin_arr->writeString('www.ffan.com');
$bin_arr->writeString(str_repeat('www.ffan.com', 100));

$bin_arr->writeFloat(100.10);
$bin_arr->writeDouble(100000000000.1000222110);

echo base64_encode($bin_arr->dump()) . PHP_EOL;
