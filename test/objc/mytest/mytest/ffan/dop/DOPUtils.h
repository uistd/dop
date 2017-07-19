//
// Created by huangshunzhao on 2017/7/19.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>

@interface DOPUtils : NSObject

/**
 * 读出number
 */
+ (NSNumber *)jsonReadNumber:(id)pointer;

/**
 * 读出string
 */
+ (NSString *)jsonReadString:(id)pointer;

/**
 * 读出二进制
 */
+ (NSData *)jsonReadData:(id)pointer;

/**
 * 读出map
 */
+ (NSDictionary *)jsonReadDictionary:(id)pointer;

/**
 * 读出array
 */
+ (NSArray *)jsonReadArray:(id)pointer;

@end