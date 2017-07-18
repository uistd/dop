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

        JsonTest *json_test = [[JsonTest alloc] init];
        [json_test toJsonStr];
    }
    return 0;

}