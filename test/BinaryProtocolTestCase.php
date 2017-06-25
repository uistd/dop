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
$data->int64 = 0xffffffff;
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
echo 'pack result:'. md5($bin_data) . ' strlen:'. strlen($bin_data), PHP_EOL;
$js_bin = base64_decode('APxJAboEaW50OBIFdWludDiSBWludDE2IgZ1aW50MTaiA2ludEIEdWludMIFaW50NjSCBnN0cmluZwEFZmxvYXQDBmRvdWJsZQgGYmluYXJ5BARsaXN0BQVCA21hcAdCAQtudWxsX3N0cnVjdAYKCG5vX3ZhbHVlQgZzdHJ1Y3QGHwpmaXJzdF9uYW1lAQlsYXN0X25hbWUBBmdlbmRlchIIdGVzdF9hcnIGEwRuYW1lAQZtb2JpbGUBA2FnZUJ///9///////9//////wAAAAD/////EFRoaXMgaXMgRE9QIHRlc3QzM8hCYMrAAc1Aj0AVVGhpcyBpcyBiaW5hcnkgc3RyaW5nAAMBAAAABXRlc3QxAgAAAAV0ZXN0MgMAAAAFdGVzdDMA/wJMaQRHYW5nAf8IYmx1ZWJpcmQLMTgwMTg2ODQ2MjYUAAAA');
for ($i = 0; $i < strlen($js_bin); ++$i) {
    if ($js_bin{$i} !== $bin_data{$i}) {
        echo $i, ' ', ord($js_bin{$i}), ':', ord($bin_data{$i}), PHP_EOL;
    }
}
die();

$new_data = new TestData();
$buffer = new \ffan\dop\DopDecode($bin_data);
$re = $new_data->binaryDecode($buffer);
if ($re) {
    echo 'success', PHP_EOL;
} else {
    echo $buffer->getErrorMessage(), PHP_EOL;
}

$bin_data2 = $data->binaryEncode(true);
echo 'pack sign result:'. md5($bin_data2), PHP_EOL;

$new_data2 = new TestData();
$buffer2 = new \ffan\dop\DopDecode($bin_data2);
$re = $new_data2->binaryDecode($buffer2);
if ($re) {
    echo $buffer2->getPid() .' success', PHP_EOL;
} else {
    echo $buffer2->getErrorMessage(), PHP_EOL;
}

$bin_data3 = $data->binaryEncode(true, true);
echo 'pack mask result:'. md5($bin_data3), PHP_EOL;

$new_data3 = new TestData();
$buffer3 = new \ffan\dop\DopDecode($bin_data3);
$re = $new_data3->binaryDecode($buffer3);
if ($re) {
    echo $buffer3->getPid() .' success', PHP_EOL;
} else {
    echo $buffer3->getErrorMessage(), PHP_EOL;
}

$bin_data4 = $data->binaryEncode(true, true, 'www.ffan.com');
$new_data4 = new TestData();
$re = $new_data4->binaryDecode($new_data4, 'www.ffan.com');
if ($re) {
    echo ' success', PHP_EOL;
}