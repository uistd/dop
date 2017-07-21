//
// Created by huangshunzhao on 2017/7/20.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import "FFANDOPEncode.h"
#import <CommonCrypto/CommonDigest.h>


@implementation FFANDOPEncode

- (id)init {
    if (![super init]) {
        return nil;
    }
    buffer = [[NSMutableData alloc] init];

    return self;
}

/**
 * 写入一个NSData
 */
- (void)writeData:(NSData *)data with_length:(BOOL)with_len
{
    if (with_len) {
        [self writeLength:data.length];
    }
    [buffer appendData:data];
}

- (void)writeBytes:(const char *)bin_bytes length:(uint32_t)size {
    if (size > 0) {
        [buffer appendBytes:bin_bytes length:size];
    }
}

- (void)writeBytes:(NSData *)data {
    if (0 == data.length) {
        return;
    }
    [buffer appendData:data];
}


- (void)writeChar:(char)byte {
    [self writeBytes:(const char *) &byte length:sizeof(char)];
}

- (void)writeUnsignedChar:(unsigned char)byte {
    [self writeBytes:(const char *) &byte length:sizeof(unsigned char)];
}

- (void)writeInt16:(int16_t)value {
    [self writeBytes:(const char *) &value length:sizeof(int16_t)];
}

- (void)writeUInt16:(uint16_t)value {
    [self writeBytes:(const char *) &value length:sizeof(uint16_t)];
}

- (void)writeInt32:(int32_t)value {
    [self writeBytes:(const char *) &value length:sizeof(int32_t)];
}

- (void)writeUInt32:(uint32_t)value {
    [self writeBytes:(const char *) &value length:sizeof(uint32_t)];
}

- (void)writeFloat:(float)value
{
    [self writeBytes:(const char *)&value length:sizeof(float)];
}

- (void)writeDouble:(double)value
{
    [self writeBytes:(const char *)&value length:sizeof(double)];
}

- (NSString *)encode {
    return [buffer base64EncodedStringWithOptions:0];
}

/**
 * 写入64位Int
 */
- (void)writeInt64:(int64_t)value {
    [self writeBytes:(const char *) &value length:sizeof(int64_t)];
}

/**
 * 写入长度
 */
- (void)writeLength:(size_t)length {
    if (0 == length) {
        return;
    }
    //如果长度小于252 表示真实的长度
    if (length < 0xfc) {
        [self writeUnsignedChar:(uint8_t) length];
    }
        //如果长度小于等于65535，先写入 0xfc，后面再写入两位表示字符串长度
    else if (length < 0xffff) {
        [self writeUnsignedChar:(uint8_t) 0xfc];
        [self writeUInt16:(uint16_t) length];
    } else {
        [self writeUnsignedChar:(uint8_t) 0xfe];
        [self writeUInt32:(uint32_t)length];
    }
}

/**
 * 写入字符串
 */
- (void)writeString:(NSString *)str {
    NSData *bytes = [str dataUsingEncoding:NSUTF8StringEncoding];
    [self writeLength:bytes.length];
    [self writeBytes:bytes];
}

/**
 * 写入数据ID
 */
- (void)writePid:(NSString *)pid {
    opt_flag |= DOP_OPTION_PID;
    [self writeString:pid];
    //数据加密的开始位置
    mask_beg_pos = buffer.length;
}

/**
 * 数据加密
 */
- (void)mask:(NSString *)key {
    mask_key = key;
    opt_flag |= DOP_OPTION_MASK;
    [self sign];
}

/**
 * 签名
 */
- (void)sign {
    opt_flag |= DOP_OPTION_SIGN;
}

/**
 * 数据打包
 */
- (NSData *)pack {
    if (opt_flag & DOP_OPTION_SIGN) {
        NSString *sign_code = [FFANDOPEncode makeSignCode:buffer length:buffer.length];
        NSData *bytes = [sign_code dataUsingEncoding:NSASCIIStringEncoding];
        [self writeBytes:bytes];
    }
    if (opt_flag & DOP_OPTION_MASK) {
        [FFANDOPEncode maskData:buffer begin_pos:mask_beg_pos mask_key:mask_key];
    }
    //记录当前的长度
    size_t current_len = buffer.length;
    [self writeUnsignedChar:opt_flag];
    [self writeLength:current_len];
    //截取出标志位 和 数据长度 的字节
    NSData *swap_data = [buffer subdataWithRange:NSMakeRange(current_len, buffer.length - current_len)];
    Byte *raw_data = [buffer bytes];
    //把数据段 往后移，腾出空间放 标志位 和 数据长度
    memcpy(&raw_data[swap_data.length], raw_data, current_len);
    //将标志位 和 长度 写入头
    memcpy(raw_data, [swap_data bytes], swap_data.length);
    return buffer;
}

/**
 * 生成签名串
 */
+ (NSString *)makeSignCode:(NSData *)data length:(size_t)len {
    unsigned char hex[16];
    CC_MD5(data.bytes, (CC_LONG)len, hex);
    return [NSString stringWithFormat: @"%02x%02x%02x%02x%02x%02x%02x%02x",
                    hex[0], hex[1], hex[2], hex[3], hex[4], hex[5], hex[6], hex[7]];
}

/**
 * 数据加密
 */
+ (void)maskData:(NSMutableData *)data begin_pos:(size_t)beg_pos mask_key:(NSString *)mask_key {
    Byte *raw_data = [data bytes];
    //如果mask_key太短了，就md5一下
    if (mask_key.length < DOP_MIN_MASK_KEY_LEN) {
        unsigned char hex[16];
        NSData *mask_data = [mask_key dataUsingEncoding:NSUTF8StringEncoding];
        CC_MD5(mask_data.bytes, (CC_LONG)mask_data.length, hex);
        mask_key = [NSString stringWithFormat:@"%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x",
        hex[0], hex[1], hex[2], hex[3], hex[4], hex[5], hex[6], hex[7], hex[8], hex[9], hex[10], hex[11], hex[12], hex[13], hex[14], hex[15]];
    }
    NSData *mask_data = [mask_key dataUsingEncoding:NSASCIIStringEncoding];
    Byte *mask_byte = [mask_data bytes];
    size_t key_len = mask_data.length, pos = 0;
    for (size_t i = beg_pos, len = data.length; i < len; ++i) {
        size_t index = pos++ % key_len;
        raw_data[i] ^= mask_byte[index];
    }
}

/**
 * 返回二进制数据
 */
- (NSData *)getData
{
    return buffer;
}

- (int)getErrorCode {
    return error_code;
}

@end