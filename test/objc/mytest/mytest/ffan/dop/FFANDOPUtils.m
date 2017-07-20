//
// Created by huangshunzhao on 2017/7/19.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import "FFANDOPUtils.h"


@implementation FFANDOPUtils {

}

/**
 * 读出number
 */
+ (NSNumber *)jsonReadNumber:(id)pointer {
    NSNumber *def = @0;
    if (nil == pointer) {
        return def;
    }
    if ([pointer isKindOfClass:[NSNumber class]]) {
        return (NSNumber *) pointer;
    }
    if ([pointer isKindOfClass:[NSString class]]) {
        NSNumberFormatter *formatter = [NSNumberFormatter new];
        formatter.numberStyle = NSNumberFormatterDecimalStyle;
        return [formatter numberFromString:(NSString *) pointer];
    }
    return def;
}

/**
 * 读出string
 */
+ (NSString *)jsonReadString:(id)pointer {
    NSString *def = @"";
    if (nil == pointer) {
        return def;
    }
    if ([pointer isKindOfClass:[NSString class]]) {
        return (NSString *) pointer;
    }
    if ([pointer isKindOfClass:[NSNumber class]]) {
        NSNumberFormatter *formatter = [NSNumberFormatter new];
        formatter.numberStyle = NSNumberFormatterDecimalStyle;
        return [formatter stringFromNumber:(NSNumber *) pointer];
    }
    return def;
}

/**
 * 读出二进制
 */
+ (NSMutableData *)jsonReadData:(id)pointer {
    NSData *def = [NSData new];
    if (nil == pointer) {
        return def;
    }
    if ([pointer isKindOfClass:[NSString class]]) {
        NSData *data = [[NSData alloc] initWithBase64EncodedString:(NSString *) pointer options:NSDataBase64DecodingIgnoreUnknownCharacters];
        return data;
    }
    return def;
}

/**
 * 读出map
 */
+ (NSDictionary *)jsonReadDictionary:(id)pointer {
    NSDictionary *def = [NSDictionary new];
    if (nil == pointer) {
        return def;
    }
    if ([pointer isKindOfClass:[NSArray class]]) {
        NSMutableDictionary *result = [NSMutableDictionary new];
        int index = 0;
        for (NSObject *item in (NSArray *) pointer) {
            result[@(index)] = item;
            ++index;
        }
        return result;
    }
    if ([pointer isKindOfClass:[NSDictionary class]]) {
        return (NSDictionary *) pointer;
    }
    return def;
}

+ (NSArray *)jsonReadArray:(id)pointer {
    NSArray *def = [NSArray new];
    if (nil == pointer) {
        return def;
    }
    if ([pointer isKindOfClass:[NSArray class]]) {
        return (NSArray *) pointer;
    }
    if ([pointer isKindOfClass:[NSDictionary class]]) {
        NSMutableArray *result = [NSMutableArray new];
        NSEnumerator *enumeratorValue = [(NSDictionary *) pointer objectEnumerator];
        for (NSObject *object in enumeratorValue) {
            [result addObject:object];
        }
    }
    return def;
}

@end