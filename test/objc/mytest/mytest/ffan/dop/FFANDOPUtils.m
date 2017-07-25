//
// Created by huangshunzhao on 2017/7/19.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import "FFANDOPUtils.h"
#import <CommonCrypto/CommonDigest.h>

@implementation FFANDOPUtils {

}

/**
 * 读出number
 */
+ (NSNumber *)idToNumber:(id)pointer {
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
+ (NSString *)idToString:(id)pointer {
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
+ (NSMutableData *)idToData:(id)pointer {
    NSData *def = [NSData new];
    if (nil == pointer) {
        return [def mutableCopy];
    }
    if ([pointer isKindOfClass:[NSString class]]) {
        NSMutableData *data = [[NSMutableData alloc] initWithBase64EncodedString:(NSString *) pointer options:NSDataBase64DecodingIgnoreUnknownCharacters];
        return data;
    }
    return [def mutableCopy];
}

/**
 * 读出map
 */
+ (NSDictionary *)idToDictionary:(id)pointer {
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

+ (NSArray *)idToArray:(id)pointer {
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
        return result;
    }
    return def;
}

/**
 * md5加密hex输出
 */
+ (NSString *)md5Hex:(unsigned char *)bytes length:(size_t)length {
    unsigned char hex[16];
    CC_MD5(bytes, (CC_LONG)length, hex);
    return [NSString stringWithFormat:@"%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x",
                    hex[0], hex[1], hex[2], hex[3], hex[4], hex[5], hex[6], hex[7], hex[8],
                    hex[9], hex[10], hex[11], hex[12], hex[13], hex[14], hex[15]];
}
@end