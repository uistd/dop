//
// Created by huangshunzhao on 2017/7/24.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import "FFANDOPDecode.h"
#import "FFANDOPEncode.h"
#import "FFANDOPProtocol.h"

@interface FFANDOPEncode ()
- (void)unpackHead;

- (BOOL)sizeCheck;

- (BOOL)checkSignCode;

- (BOOL)unmack:(NSString *)mask_key;

- (NSDictionary *)read_protocol:(size_t)length;

- (FFANDOPProtocol *)read_protocol_item;

- (NSDictionary *)read_protocol_data:(NSDictionary *)protocol_list;

- (NSObject *)read_item_data:(FFANDOPProtocol *)item is_property:(BOOL)is_property;

- (NSNumber *)read_int_item_data:(uint8_t)int_type;
@end

@implementation FFANDOPDecode

- (id)initWithData:(NSData *)data {
    if (![super init]) {
        return nil;
    }
    raw_data = data;
    buffer = (char *) data.bytes;
    max_read_pos = data.length;
    return self;
}

- (BOOL)sizeCheck:(size_t)need_size {
    if (read_pos + need_size > max_read_pos) {
        error_code = DOP_ERROR_SIZE;
        return NO;
    }
    return YES;
}

- (uint8_t)readUInt8 {
    if (![self sizeCheck:1]) {
        return 0;
    }
    return (uint8_t) buffer[read_pos++];
}

- (int8_t)readInt8 {
    if (![self sizeCheck:1]) {
        return 0;
    }
    return buffer[read_pos++];
}

- (int16_t)readInt16 {
    if (![self sizeCheck:sizeof(int16_t)]) {
        return 0;
    }
    char *tmp = buffer + read_pos;
    read_pos += sizeof(int16_t);
    int16_t result = *(int16_t *) tmp;
    if (is_big_endian) {
        NTOHS(result);
    }
    return result;
}

- (uint16_t)readUInt16 {
    if (![self sizeCheck:sizeof(uint16_t)]) {
        return 0;
    }
    char *tmp = buffer + read_pos;
    read_pos += sizeof(uint16_t);
    uint16_t result = *(uint16_t *) tmp;
    if (is_big_endian) {
        NTOHS(result);
    }
    return result;
}

- (int32_t)readInt32 {
    if (![self sizeCheck:sizeof(int32_t)]) {
        return 0;
    }
    char *tmp = buffer + read_pos;
    read_pos += sizeof(int32_t);
    int32_t result = *(int32_t *) tmp;
    if (is_big_endian) {
        NTOHL(result);
    }
    return result;
}

- (uint32_t)readUInt32 {
    if (![self sizeCheck:sizeof(uint32_t)]) {
        return 0;
    }
    char *tmp = buffer + read_pos;
    read_pos += sizeof(uint32_t);
    uint32_t result = *(uint32_t *) tmp;
    if (is_big_endian) {
        NTOHL(result);
    }
    return result;
}

- (int64_t)readInt64 {
    if (![self sizeCheck:sizeof(int64_t)]) {
        return 0;
    }
    char *tmp = buffer + read_pos;
    read_pos += sizeof(int64_t);
    int64_t result = *(int64_t *) tmp;
    if (is_big_endian) {
        NTOHLL(result);
    }
    return result;
}

- (float)readFloat {
    if (![self sizeCheck:sizeof(float)]) {
        return 0.0;
    }
    char *tmp = buffer + read_pos;
    read_pos += sizeof(float);
    float result = *(float *) tmp;
    return result;
}

- (double)readDouble {
    if (![self sizeCheck:sizeof(double)]) {
        return 0.0;
    }
    char *tmp = buffer + read_pos;
    read_pos += sizeof(double);
    double result = *(double *) tmp;
    return result;
}

- (NSString *)readString {
    uint32_t str_len = [self readLength];
    if (error_code > 0 || 0 == str_len || ![self sizeCheck:str_len]) {
        return @"";
    }
    NSData *str_data = [raw_data subdataWithRange:NSMakeRange(read_pos, str_len)];
    read_pos += str_len;
    return [[NSString alloc] initWithData:str_data encoding:NSUTF8StringEncoding];
}

/**
 * 读出数据长度
 */
- (uint32_t)readLength {
    uint8_t flag = [self readUInt8];
    if (flag < 0xfc) {
        return flag;
    } else if (0xfc == flag) {
        return [self readUInt16];
    } else {
        return [self readUInt32];
    }
}

/**
 * 读出二进制串
 */
- (NSData *)readBytes {
    uint32_t length = [self readLength];
    if (error_code > 0 || 0 == length || ![self sizeCheck:length]) {
        return [NSData new];
    }
    NSData *result = [raw_data subdataWithRange:NSMakeRange(read_pos, length)];
    read_pos += length;
    return result;
}

- (void)unpackHead {
    if (is_unpack_head) {
        return;
    }
    is_unpack_head = YES;
    //opt_flag
    opt_flag = [self readUInt8];
    if (opt_flag & DOP_OPTION_ENDIAN) {
        is_big_endian = YES;
    }
    uint32_t total_len = [self readLength];
    if (max_read_pos - read_pos != total_len) {
        error_code = DOP_ERROR_SIZE;
        return;
    }
    sign_data_pos = read_pos;
    if (opt_flag & DOP_OPTION_PID) {
        pid = [self readString];
    }
    mask_data_pos = read_pos;
}

/**
 * 数据是否加密
 */
- (BOOL)isMask {
    if (!is_unpack_head) {
        [self unpackHead];
    }
    return (opt_flag & DOP_OPTION_MASK) > 0;
}

/**
 * 获取数据ID
 */
- (NSString *)getPid {
    if (nil == pid) {
        return @"";
    }
    return pid;
}

/**
 * 签名串
 */
- (BOOL)checkSignCode {
    if (max_read_pos - read_pos < DOP_SIGN_CODE_LEN) {
        error_code = DOP_ERROR_DATA;
        return NO;
    }
    size_t sign_code_pos = max_read_pos - DOP_SIGN_CODE_LEN;
    NSData *sign_code_bytes = [raw_data subdataWithRange:NSMakeRange(sign_code_pos, DOP_SIGN_CODE_LEN)];
    NSString *sign_code = [FFANDOPEncode makeSignCode:raw_data offset:sign_data_pos length:sign_code_pos - sign_data_pos];
    NSString *old_sign_code = [[NSString alloc] initWithData:sign_code_bytes encoding:NSASCIIStringEncoding];
    if (![sign_code isEqualToString:old_sign_code]) {
        error_code = DOP_ERROR_SIGN_CODE;
        return NO;
    }
    max_read_pos -= DOP_SIGN_CODE_LEN;
    opt_flag ^= DOP_OPTION_SIGN;
    return YES;
}

/**
 * 数据解密
 */
- (BOOL)unmack:(NSString *)mask_key {
    [FFANDOPEncode maskData:raw_data begin_pos:mask_data_pos mask_key:mask_key];
    opt_flag ^= DOP_OPTION_MASK;
    if (![self checkSignCode]) {
        error_code = DOP_ERROR_MASK;
        return NO;
    }
    return YES;
}

- (NSDictionary *)unpack {
    if (!is_unpack_head) {
        [self unpackHead];
    }
    if ([self isMask]) {
        error_code = DOP_ERROR_MASK;
        return nil;
    }
    if ((opt_flag & DOP_OPTION_SIGN) > 0 && ![self checkSignCode]) {
        return nil;
    }
    NSArray *dop_protocol = [self read_protocol:[self readLength]];
    if (nil == dop_protocol || error_code > 0) {
        return nil;
    }
    return [self read_protocol_data:dop_protocol];
}

- (NSDictionary *)unpack:(NSString *)mask_key {
    if ([self isMask] && ![self unmack:mask_key]) {
        return nil;
    }
    return [self unpack];
}

/**
 * 读出一组协议
 */
- (NSArray *)read_protocol:(size_t)length {
    size_t end_pos = read_pos + length;
    if (end_pos > max_read_pos) {
        error_code = DOP_ERROR_DATA;
        return nil;
    }
    NSMutableArray *result = [NSMutableArray new];
    while (0 == error_code && read_pos < end_pos) {
        NSString *name = [self readString];
        FFANDOPProtocol *item = [self read_protocol_item];
        item.name = name;
        [result addObject:item];
    }
    if (error_code > 0) {
        return nil;
    }
    return result;
}

/**
 * 读出一项协议
 */
- (FFANDOPProtocol *)read_protocol_item {
    uint8_t type = [self readUInt8];
    FFANDOPProtocol *item = [FFANDOPProtocol new];
    item.type = type;
    switch (type) {
        case DOP_PROTOCOL_TYPE_ARRAY:
            item.value_item = [self read_protocol_item];
            break;
        case DOP_PROTOCOL_TYPE_MAP:
            item.key_item = [self read_protocol_item];
            item.value_item = [self read_protocol_item];
            break;
        case DOP_PROTOCOL_TYPE_STRUCT: {
            uint32_t len = [self readLength];
            item.sub_struct = [self read_protocol:len];
            break;
        }
        case DOP_PROTOCOL_TYPE_STRING:
        case DOP_PROTOCOL_TYPE_FLOAT:
        case DOP_PROTOCOL_TYPE_DOUBLE:
        case DOP_PROTOCOL_TYPE_BINARY:
        case DOP_PROTOCOL_TYPE_BOOL:
            break;
        case DOP_INT_TYPE_CHAR:
        case DOP_INT_TYPE_U_CHAR:
        case DOP_INT_TYPE_SHORT:
        case DOP_INT_TYPE_U_SHORT:
        case DOP_INT_TYPE_INT:
        case DOP_INT_TYPE_U_INT:
        case DOP_INT_TYPE_BIG_INT:
            item.int_type = type;
            item.type = DOP_PROTOCOL_TYPE_INT;
            break;
        default:
            error_code = DOP_ERROR_DATA;
    }
    return item;
}

/**
 * 读object
 */
- (NSDictionary *)read_protocol_data:(NSArray *)protocol_list {
    NSMutableDictionary *result = [NSMutableDictionary new];
    for (FFANDOPProtocol *item in protocol_list) {
        NSObject *value = [self read_item_data:item is_property:YES];
        result[item.name] = value;
    }
    return result;
}

/**
 * 读属性
 */
- (NSObject *)read_item_data:(FFANDOPProtocol *)item is_property:(BOOL)is_property {
    uint8_t type = item.type;
    switch (type) {
        case DOP_PROTOCOL_TYPE_INT:
            return [self read_int_item_data:item.int_type];
        case DOP_PROTOCOL_TYPE_STRING:
            return [self readString];
        case DOP_PROTOCOL_TYPE_BOOL:
            return [self read_int_item_data:0];
        case DOP_PROTOCOL_TYPE_FLOAT:
            return [[NSNumber alloc] initWithFloat:[self readFloat]];
        case DOP_PROTOCOL_TYPE_DOUBLE:
            return [[NSNumber alloc] initWithDouble:[self readDouble]];
        case DOP_PROTOCOL_TYPE_BINARY:
            return [self readBytes];
        case DOP_PROTOCOL_TYPE_STRUCT:
            //如果是属性，要先读出一个标志位，判断是否为NULL
            if (is_property) {
                uint8_t flag = [self readUInt8];
                if (0xff != flag) {
                    return [NSNull new];
                }
            }
            return [self read_protocol_data:item.sub_struct];
        case DOP_PROTOCOL_TYPE_ARRAY: {
            NSMutableArray *arr = [NSMutableArray new];
            uint32_t arr_size = [self readLength];
            for (uint32_t i = 0; i < arr_size; ++i) {
                if (error_code > 0) {
                    break;
                }
                [arr addObject:[self read_item_data:item.value_item is_property:NO]];
            }
            return arr;
        }
        case DOP_PROTOCOL_TYPE_MAP: {
            NSMutableDictionary *map = [NSMutableDictionary new];
            uint32_t arr_size = [self readLength];
            for (uint32_t i = 0; i < arr_size; ++i) {
                NSObject *key = [self read_item_data:item.key_item is_property:NO];
                NSObject *value = [self read_item_data:item.value_item is_property:NO];
                if (error_code > 0) {
                    break;
                }
                map[(id)key] = value;
            }
            return map;
        }
        default:
            error_code = DOP_ERROR_DATA;
            return @0;
    }
}

/**
 * 读出int值
 */
- (NSNumber *)read_int_item_data:(uint8_t)int_type {
    switch (int_type) {
        case DOP_INT_TYPE_CHAR:
            return @([self readInt8]);
        case DOP_INT_TYPE_U_CHAR:
            return @([self readUInt8]);
        case DOP_INT_TYPE_SHORT:
            return @([self readInt16]);
        case DOP_INT_TYPE_U_SHORT:
            return @([self readUInt16]);
        case DOP_INT_TYPE_INT:
            return @([self readInt32]);
        case DOP_INT_TYPE_U_INT:
            return @([self readUInt32]);
        case DOP_INT_TYPE_BIG_INT:
            return @([self readInt64]);
        case 0:
            //bool值，特殊处理
            return [[NSNumber alloc] initWithBool:[self readInt8]];
        default:
            error_code = DOP_ERROR_DATA;
    }
    return @0;
}

/**
 * 获取错误码
 */
- (int)getErrorCode {
    return error_code;
}

@end