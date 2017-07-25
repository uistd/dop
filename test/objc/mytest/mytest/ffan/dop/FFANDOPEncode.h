//
// Created by huangshunzhao on 2017/7/20.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>

#define DOP_OPTION_PID 0x1
#define DOP_OPTION_SIGN 0x2
#define DOP_OPTION_MASK 0x4
#define DOP_OPTION_ENDIAN 0x8
#define DOP_SIGN_CODE_LEN 8
#define DOP_MIN_MASK_KEY_LEN 8
#define DOP_ERROR_TOO_BIG_DATA 1



@interface FFANDOPEncode : NSObject {
@private
    NSMutableData *buffer;
    uint8_t opt_flag;
    size_t mask_beg_pos;
    NSString *mask_key;
    int error_code;
}

- (id)init;

/**
 * 写入一个NSData
 */
- (void)writeData:(NSData *)data with_length:(BOOL)with_len;

- (void)writeChar:(char)byte;

- (void)writeUnsignedChar:(unsigned char)byte;

- (void)writeInt16:(int16_t)value;

- (void)writeUInt16:(uint16_t)value;

- (void)writeInt32:(int32_t)value;

- (void)writeUInt32:(uint32_t)value;

- (void)writeInt64:(int64_t)value;

- (void)writeFloat:(float)value;

- (void)writeDouble:(double)value;

- (void)writeLength:(size_t)length;

- (void)writeString:(NSString *)str;

- (void)writePid:(NSString *)pid;

- (void)mask:(NSString *)mask_key;

- (void)sign;

- (uint32_t)getSize;

- (NSData *)pack;

/**
 * 返回二进制数据
 */
- (NSData *)getData;

/**
 * 生成签名串
 */
+ (NSString *)makeSignCode:(NSData *)data offset:(size_t)offset length:(size_t)len;

/**
 * 数据加密
 */
+ (void)maskData:(NSData *)data begin_pos:(size_t)beg_pos mask_key:(NSString *) mask_key;

/**
 * 获取错误码
 */
- (int)getErrorCode;

@end