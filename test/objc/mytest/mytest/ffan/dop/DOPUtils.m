//
// Created by huangshunzhao on 2017/7/19.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import "DOPUtils.h"


@implementation DOPUtils {

}

/**
 * 读出number
 */
+ (NSNumber *)jsonReadNumber:(id)pointer {
    NSNumber *def = @0;
    if (nil == pointer) {
        return def;
    } else if ([pointer isKindOfClass:[NSNumber class]]) {
        return (NSNumber *) pointer;
    } else if ([pointer isKindOfClass:[NSString class]]) {
        NSNumberFormatter *formatter = [NSNumberFormatter new];
        formatter.numberStyle = NSNumberFormatterDecimalStyle;
        return [formatter numberFromString:(NSString *) pointer];
    } else {
        return def;
    }
}

/**
 * 读出string
 */
+ (NSString *)jsonReadString:(id)pointer {
    NSString *def = @"";
    if (nil == pointer) {
        return def;
    } else if ([pointer isKindOfClass:[NSString class]]) {
        return (NSString *) pointer;
    } else if ([pointer isKindOfClass:[NSNumber class]]) {
        NSNumberFormatter *formatter = [NSNumberFormatter new];
        formatter.numberStyle = NSNumberFormatterDecimalStyle;
        return [formatter stringFromNumber:(NSNumber *) pointer];
    } else {
        return def;
    }
}

/**
 * 读出二进制
 */
+ (NSData *)jsonReadData:(id)pointer {
    NSData *def = [NSData new];
    if (nil == pointer) {
        return def;
    } else if ([pointer isKindOfClass:[NSString class]]) {
        NSData *data = [[NSData alloc] initWithBase64EncodedString:(NSString *)pointer options:NSDataBase64DecodingIgnoreUnknownCharacters];
        return data;
    } else {
        return def;
    }
}

/**
 * 读出map
 */
+ (NSDictionary *)jsonReadDictionary:(id)pointer
{
    NSDictionary *def = [NSDictionary new];
    if (nil == pointer) {
        return def;
    } else if ([pointer isKindOfClass:[NSDictionary class]]) {
        return (NSDictionary *)pointer;
    } else {
        return def;
    }
}


@end