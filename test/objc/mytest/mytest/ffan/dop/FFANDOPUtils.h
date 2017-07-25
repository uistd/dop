//
// Created by huangshunzhao on 2017/7/19.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>

@interface FFANDOPUtils : NSObject

/**
 * 读出number
 */
+ (NSNumber *)idToNumber:(id)pointer;

/**
 * 读出string
 */
+ (NSString *)idToString:(id)pointer;

/**
 * 读出二进制
 */
+ (NSMutableData *)idToData:(id)pointer;

/**
 * 读出map
 */
+ (NSDictionary *)idToDictionary:(id)pointer;

/**
 * 读出array
 */
+ (NSArray *)idToArray:(id)pointer;

/**
 * md5加密hex输出
 */
+ (NSString *)md5Hex:(unsigned char *)bytes length:(size_t)length;

@end