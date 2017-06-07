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
$bin_arr->writeBigInt(0x7fffffffffffffff);

$bin_arr->writeString(null);
$bin_arr->writeString('www.ffan.com');
$bin_arr->writeString(str_repeat('www.ffan.com', 100));

$bin_arr->writeFloat(100.10);
$bin_arr->writeDouble(100.10);

echo base64_encode($bin_arr->dump()) . PHP_EOL;

var_dump($bin_arr->readChar());
var_dump($bin_arr->readChar());
var_dump($bin_arr->readUnsignedChar());

var_dump($bin_arr->readShort());
var_dump($bin_arr->readShort());
var_dump($bin_arr->readUnsignedShort());

var_dump($bin_arr->readInt());
var_dump($bin_arr->readInt());
var_dump($bin_arr->readUnsignedInt());

var_dump($bin_arr->readBigInt());
var_dump($bin_arr->readBigInt());
var_dump($bin_arr->readUnsignedBigInt());

var_dump($bin_arr->readString());
var_dump($bin_arr->readString());
var_dump($bin_arr->readString());

var_dump($bin_arr->readFloat());
var_dump($bin_arr->readDouble());
