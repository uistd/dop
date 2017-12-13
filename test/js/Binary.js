const console = require('console');
var TestData = require('./runtime/build/js/demo/data/TestData');
var TestDataStruct = require('./runtime/build/js/demo/data/TestDataStruct');
var TestArr = require('./runtime/build/js/demo/data/TestArr');
var dopBase = require('./runtime/build/js/dop');
var DopDecode = require('./runtime/build/js/DopDecode');

var data = new TestData;
data.int8 = 0x7f;
data.uint8 = 0xff;
data.int16 = 0x7fff;
data.uint16 = 0xffff;
data.int = 0x7fffffff;
data.uint = 0xffffffff;
data.int64 = 0xfffffffffff;
data.float = 100.1;
data.double = 1000.1001010;
data.string = 'This is DOP test';
data.binary = 'This is binary string';
data.list = [1,2,3,4,5];
data.map = {1: 'test1', 2: 'test2', 3:'test3'};
data.struct = new TestDataStruct();
data.struct.first_name = 'Li';
data.struct.last_name = 'Gang';
data.struct.gender = 1;
data.test_arr = new TestArr();
data.test_arr.name = 'bluebird';
data.test_arr.age = 20;
data.test_arr.mobile = '18018684626';
data.null_struct = null;
var bin_data = data.binaryEncode();
console.log('len:', bin_data.length, dopBase.md5(bin_data));
var bin_data2 = data.binaryEncode(true);
console.log('len:', bin_data2.length, dopBase.md5(bin_data2));
var bin_data3 = data.binaryEncode(true, true);
console.log('len:', bin_data3.length, dopBase.md5(bin_data3));
var bin_data4 = data.binaryEncode(true, true, 'www.abc.com');
console.log('len:', bin_data4.length, dopBase.md5(bin_data4));


var decoder = new DopDecode(bin_data);
var arr = decoder.unpack();
console.dir(arr);

var decoder2 = new DopDecode(bin_data2);
var arr2 = decoder2.unpack();
console.dir(arr2);

var decoder3 = new DopDecode(bin_data3);
var arr3 = decoder3.unpack();
console.dir(arr3);

var decoder4 = new DopDecode(bin_data4);
var arr4 = decoder4.unpack('www.abc.com');
console.dir(arr4);