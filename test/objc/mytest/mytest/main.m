//
//  main.m
//  mytest
//
//  Created by huangshunzhao on 2017/7/14.
//  Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>
#import "TestData.h"
#import "TestBin.h"
#import "JsonTest.h"
#import "DOPDataTestData.h"
#import "DOPDataTestDataTestStruct.h"
#import "DOPDataTestArr.h"

int main(int argc, const char * argv[]) {
    @autoreleasepool {
        // insert code here...
        NSLog(@"Hello, World!");
        TestData *test_data = [[TestData alloc] init];
        test_data.string = @"Test string";
        test_data.uint = 20;
        test_data.int64 = 100;
        [test_data print];

        TestBin *bin = [[TestBin alloc] init];
        [bin writeChar:0x7f];
        [bin writeUnsignedChar:0xff];
        [bin writeInt16:0x7fff];
        [bin writeUInt16:0xffff];
        [bin writeInt32:0x7fffffff];
        [bin writeUInt32:0xffffffff];
        [bin writeInt64:0x7fffffffffffffff];
        [bin writeString:@"This is test string"];
        NSLog(@"%@", [bin dumpToHex]);

        //JsonTest *json_test = [[JsonTest alloc] init];
        //NSString *json_str = [json_test toJsonStr];
        //[json_test parseJson:json_str];

        DOPDataTestData *test = [DOPDataTestData new];
        test.int8 = 0x7f;
        test.uint8 = 0xff;
        test.int16 = 0x7fff;
        test.uint16 = 0xffff;
        test.int32 = 0x7fffffff;
        test.uint = 0xffffffff;
        test.int64 = 0x7fffffffffffffff;
        test.float32 = 100.1;
        test.double64 = 1000.1001010;
        test.string = @"This is DOP test";
        test.binary = [NSMutableData new];
        int a = 100;
        [test.binary appendBytes:&a length:sizeof(int)];
        test.is_ok = YES;
        test.list = [NSMutableArray new];
        [test.list addObject:@"this"];
        [test.list addObject:@"many"];
        test.list_list = [NSMutableArray new];
        NSMutableArray *arr_test = [NSMutableArray new];
        [arr_test addObject:@20];
        [arr_test addObject:@10];
        [arr_test addObject:@40];
        [test.list_list addObject:arr_test];

        test.map = [NSMutableDictionary new];
        test.map[@"1"] = @"this is one";
        test.map[@"2"] = @"this is two";
        test.map[@"10"] = @"this is ten";

        test.test_struct = [DOPDataTestDataTestStruct new];
        test.test_struct.first_name = @"huang";
        test.test_struct.last_name = @"shunzhao";
        test.test_struct.gender = 1;

        test.test_arr = [DOPDataTestArr new];
        test.test_arr.name = @"bluebird";
        test.test_arr.age = 30;
        test.test_arr.mobile = @"18018684626";

        NSString *test_json = [test jsonEncode];
        NSLog(@"result: \n %@", test_json);

        DOPDataTestData *new_test = [DOPDataTestData new];
        BOOL re = [new_test jsonDecode:test_json];
        NSLog(@"json decode result:%d", re);
    }
    return 0;

}