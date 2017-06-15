<?php
require_once 'runtime/build/php/dop.php';

use ffan\dop\demo\data\TestArr;
use ffan\dop\demo\data\TestData;
use ffan\dop\demo\data\TestDataStruct;

$data = new TestData();

$data->int8 = 0x7f;
$data->uint8 = 0xff;
$data->int16 = 0x7fff;
$data->uint16 = 0xffff;
$data->int = 0x7fffffff;
$data->uint = 0xffffffff;
$data->int64 = 0x7fffffffffffffff;
$data->float = 100.1;
$data->double = 1000.1001010;
$data->string = 'This is DOP test';
$data->binary = 'This is binary string';
$data->list = array(1,2,3,4,5);
$data->map = array(1 => 'test1', 2 => 'test2', 3 => 'test3');
$data->struct = new TestDataStruct();
$data->struct->first_name = 'Li';
$data->struct->last_name = 'Gang';
$data->struct->gender = 1;
$data->test_arr = new TestArr();
$data->test_arr->name = 'bluebird';
$data->test_arr->age = 20;
$data->test_arr->mobile = '18018684626';
$bin_data = $data->binaryEncode();

$new_data = new TestData();
$buffer = new \ffan\dop\BinaryBuffer($bin_data);
$re = $new_data->binaryDecode($buffer);
if ($re) {
    echo 'success', PHP_EOL;
} else {
    echo $buffer->getErrorMessage(), PHP_EOL;
}

$bin_data2 = $data->binaryEncode(true);

$new_data2 = new TestData();
$buffer2 = new \ffan\dop\BinaryBuffer($bin_data2);
$re = $new_data2->binaryDecode($buffer2);
if ($re) {
    echo $buffer2->getPid() .' success', PHP_EOL;
} else {
    echo $buffer2->getErrorMessage(), PHP_EOL;
}

$bin_data3 = $data->binaryEncode(true, true);
$new_data3 = new TestData();
$buffer3 = new \ffan\dop\BinaryBuffer($bin_data3);
$re = $new_data3->binaryDecode($buffer3);
if ($re) {
    echo $buffer3->getPid() .' success', PHP_EOL;
} else {
    echo $buffer3->getErrorMessage(), PHP_EOL;
}

$bin_data4 = $data->binaryEncode(true, true, 'www.ffan.com');
$new_data4 = new TestData();
$buffer4 = new \ffan\dop\BinaryBuffer($bin_data4);
$buffer4->unmask('www.ffan.com');
$re = $new_data4->binaryDecode($buffer4);
if ($re) {
    echo $buffer4->getPid() .' success', PHP_EOL;
} else {
    echo $buffer4->getErrorMessage(), PHP_EOL;
}