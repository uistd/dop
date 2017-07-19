//
// Created by huangshunzhao on 2017/7/18.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>


@interface JsonTest : NSObject
- (NSString *)toJsonStr;
- (BOOL)parseJson:(NSString *) json_str;
@end