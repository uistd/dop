//
// Created by huangshunzhao on 2017/7/24.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>

#define DOP_ERROR_SIZE 1
#define DOP_ERROR_SIGN_CODE 2
#define DOP_ERROR_DATA 3
#define DOP_ERROR_MASK 4

typedef struct dop_protocol_item_s dop_protocol_item_t;

@interface FFANDOPDecode : NSObject {
@private
    NSData *raw_data;
    char *buffer;
    size_t read_pos;
    size_t max_read_pos;
    int error_code;
    BOOL is_unpack_head;
    NSString *pid;
    uint8_t opt_flag;
    size_t sign_data_pos;
    size_t mask_data_pos;
    BOOL is_big_endian;
}

- (id)initWithData:(NSData*)data;

/**
 * 数据是否加密
 */
- (BOOL)isMask;

/**
 * 获取数据ID
 */
- (NSString *)getPid;

/**
 * 数据解包
 */
- (NSDictionary *)unpack;

/**
 * 带密钥的数据解包
 */
- (NSDictionary *)unpack:(NSString *)mask_key;

/**
 * 获取错误码
 */
- (int)getErrorCode;
@end