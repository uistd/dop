//
// Created by huangshunzhao on 2017/7/18.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>


@interface TestBin : NSObject {
@private
    NSMutableData *buffer;
    char opt_flag;
    int mask_beg_pos;
    NSString *mask_key;
    int error_code;
}

- (id)init;

- (void)writeChar:(char)byte;

- (void)writeUnsignedChar:(unsigned char)byte;

- (void)writeInt16:(int16_t)value;

- (void)writeUInt16:(uint16_t)value;

- (void)writeInt32:(int32_t)value;

- (void)writeUInt32:(uint32_t)value;

- (void)writeInt64:(int64_t)value;

- (void)writeLength:(int)length;

- (void)writeString:(NSString *)str;

- (NSString *)encode;

- (NSString *)dumpToHex;
@end