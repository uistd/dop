//
// Created by huangshunzhao on 2017/7/18.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import "JsonTest.h"


@implementation JsonTest {

}

- (NSString *)toJsonStr {

    NSMutableArray<NSMutableArray <NSString *>*> *test = [NSMutableArray new];
    NSMutableArray<NSNumber *> *test_int;
    [test addObject:@"hahaha"];
    [test addObject:@"12"];

    NSMutableDictionary *request = [[NSMutableDictionary alloc] init];
    [request setObject:@"hahaha" forKey:@"str"];
    [request setObject:@10 forKey:@"int"];
    [request setObject:@1200.33 forKey:@"float"];
    NSMutableArray *arr = [[NSMutableArray alloc] init];
    BOOL b = true;
    [arr addObject:[NSNumber numberWithBool:b]];
    [arr addObject:[NSNumber numberWithFloat:100.32]];
    [arr addObject:@"one"];
    [arr addObject:@"tow"];
    [arr addObject:[NSNull new]];
    [request setObject:arr forKey:@"arr"];
    [request setObject:test forKey:@"test"];
    NSData *json_data = [NSJSONSerialization dataWithJSONObject:request options:kNilOptions error:nil];
    NSString *json_str = [[NSString alloc] initWithData:json_data encoding:NSUTF8StringEncoding];
    NSLog(@"result %@", json_str);

    return json_str;
}


@end