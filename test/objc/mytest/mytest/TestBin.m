//
// Created by huangshunzhao on 2017/7/18.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import "TestBin.h"


@implementation TestBin {

}

- (id)init {

    if (![super init]) {
        return nil;
    }
    buffer = [[NSMutableData alloc] init];

    return self;
}

- (void)writeBytes:(const char *)bin_bytes length:(uint32_t)size {
    if (size > 0) {
        [buffer appendBytes:bin_bytes length:size];
    }
}

- (void)writeBytes:(NSData*) data {
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
    [self writeBytes:(const char *) &value length:sizeof(int)];
}

- (void)writeUInt32:(uint32_t)value {
    [self writeBytes:(const char *) &value length:sizeof(uint32_t)];
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
- (void)writeLength:(int)length {
    if (length < 0) {
        return;
    }
    //如果长度小于252 表示真实的长度
    if (length < 0xfc) {
        [self writeUnsignedChar:(uint8_t) length];
    }
        //如果长度小于等于65535，先写入 0xfc，后面再写入两位表示字符串长度
    else if (length < 0xffff) {
        [self writeUnsignedChar:(uint8_t) 0xfc];
        [self writeUInt16:(uint16_t)length];
    } else {
        [self writeUnsignedChar:(uint8_t) 0xfe];
        [self writeInt32:length];
    }
}

/**
 * 写入字符串
 */
- (void)writeString:(NSString*) str {
    NSData *bytes = [str dataUsingEncoding:NSUTF8StringEncoding];
    [self writeLength:bytes.length];
    [self writeBytes:bytes];
}

/**
 * byte to hex string
 */
+ (NSString *)byteToHex:(Byte)tmp {
    NSMutableString *str = [NSMutableString string];
    //取高四位
    Byte byte1 = tmp>>4;
    //取低四位
    Byte byte2 = tmp & 0xf;
    //拼接16进制字符串
    [str appendFormat:@"%x",byte1];
    [str appendFormat:@"%x",byte2];
    return str;
}

/**
 * 导出为hexstring
 */
- (NSString *)dumpToHex
{
    NSMutableString *str = [NSMutableString string];
    Byte *byte = (Byte *)[buffer bytes];
    for (int i = 0; i<[buffer length]; i++) {
        [str appendString:[TestBin byteToHex:*(byte + i)]];
    }
    return str;
}

@end