<?php
require_once 'runtime/build/php/dop.php';

use FFan\Dop\Demo\Data\TestArr;
use FFan\Dop\Demo\Data\TestData;
use FFan\Dop\Demo\Data\TestDataTestStruct;

$data = new TestData();
$data->binary = pack('l', 100);
$data->int8 = 0x7f;
$data->uint8 = 0xff;
$data->int16 = 0x7fff;
$data->uint16 = 0xffff;
$data->int32 = 0x7fffffff;
$data->uint = 0xffffffff;
$data->int64 = 0x7fffffffffffffff;
$data->float32 = 100.1;
$data->double64 = 1000.1001010;
$data->string = 'This is DOP test';
$data->is_ok = true;
$data->list = array("this", "many");
$data->list_list = [[20, 10, 40]];
$data->map = array(1 => 'this is one', 2 => 'this is two', 10 => 'this is ten');
$data->test_arr = new TestArr();
$data->test_arr->name = 'bluebird';
$data->test_arr->age = 30;
$data->test_arr->mobile = '18018684626';

$data->test_struct = new TestDataTestStruct();
$data->test_struct->first_name = "huang";
$data->test_struct->last_name = "shunzhao";
$data->test_struct->gender = 1;


$bin_data = $data->binaryEncode();
echo 'pack result:' . md5($bin_data) . ' strlen:' . strlen($bin_data), PHP_EOL;
/**
 * $js_bin = base64_decode('APyIAdgEaW50OBIFdWludDiSBWludDE2IgZ1aW50MTaiBWludDMyQgR1aW50wgVpbnQ2NIIGc3RyaW5nAQdmbG9hdDMyAwhkb3VibGU2NAgGYmluYXJ5BAVpc19vawkEbGlzdAUBCWxpc3RfbGlzdAUFQgNtYXAHQgELbnVsbF9zdHJ1Y3QGCghub192YWx1ZUILdGVzdF9zdHJ1Y3QGHwpmaXJzdF9uYW1lAQlsYXN0X25hbWUBBmdlbmRlchIIdGVzdF9hcnIGEwRuYW1lAQZtb2JpbGUBA2FnZUJ///9///////9///////////////9/EFRoaXMgaXMgRE9QIHRlc3QzM8hCYMrAAc1Aj0AEZAAAAAECBHRoaXMEbWFueQEDFAAAAAoAAAAoAAAAAwEAAAALdGhpcyBpcyBvbmUCAAAAC3RoaXMgaXMgdHdvCgAAAAt0aGlzIGlzIHRlbgD/BWh1YW5nCHNodW56aGFvAf8IYmx1ZWJpcmQLMTgwMTg2ODQ2MjYeAAAA');
 * $js_len = strlen($js_bin);
 * $php_len = strlen($bin_data);
 * for ($i = 0; $i < min($js_len, $php_len); ++$i) {
 * if ($js_bin{$i} !== $bin_data{$i}) {
 * echo $i, ' ', ord($js_bin{$i}), ':', ord($bin_data{$i}), PHP_EOL;
 * }
 * }
 * die();
 * //*/
$new_data = new TestData();
$buffer = new \FFan\Dop\DopDecode($bin_data);
$re = $new_data->binaryDecode($buffer);
if ($re) {
    echo 'success', PHP_EOL;
} else {
    echo $buffer->getErrorMessage(), PHP_EOL;
}

$bin_data2 = $data->binaryEncode(true);
echo 'pack sign result:' . md5($bin_data2) . ' strlen:' . strlen($bin_data2), PHP_EOL;

$new_data2 = new TestData();
$buffer2 = new \FFan\Dop\DopDecode($bin_data2);
$re = $new_data2->binaryDecode($buffer2);
if ($re) {
    echo $buffer2->getPid() . ' success', PHP_EOL;
} else {
    echo $buffer2->getErrorMessage(), PHP_EOL;
}

$bin_data3 = $data->binaryEncode(true, true);
echo 'pack mask result:' . md5($bin_data3) . ' strlen:' . strlen($bin_data3), PHP_EOL;

$new_data3 = new TestData();
$buffer3 = new \FFan\Dop\DopDecode($bin_data3);
$re = $new_data3->binaryDecode($buffer3);
if ($re) {
    echo $buffer3->getPid() . ' success', PHP_EOL;
} else {
    echo $buffer3->getErrorMessage(), PHP_EOL;
}

$bin_data4 = $data->binaryEncode(true, true, 'www.abc.com');
echo 'pack mask result:' . md5($bin_data4) . ' strlen:' . strlen($bin_data4), PHP_EOL;
$new_data4 = new TestData();
$re = $new_data4->binaryDecode($new_data4, 'www.abc.com');
if ($re) {
    echo ' success', PHP_EOL;
}