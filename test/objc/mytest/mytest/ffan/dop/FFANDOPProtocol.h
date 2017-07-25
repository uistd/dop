//
// Created by huangshunzhao on 2017/7/24.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>

#define DOP_PROTOCOL_TYPE_STRING 1
#define DOP_PROTOCOL_TYPE_INT 2
#define DOP_PROTOCOL_TYPE_FLOAT 3
#define DOP_PROTOCOL_TYPE_BINARY 4
#define DOP_PROTOCOL_TYPE_ARRAY 5
#define DOP_PROTOCOL_TYPE_STRUCT 6
#define DOP_PROTOCOL_TYPE_MAP 7
#define DOP_PROTOCOL_TYPE_DOUBLE 8
#define DOP_PROTOCOL_TYPE_BOOL 9

//int的类型
#define DOP_INT_TYPE_CHAR 0x12
#define DOP_INT_TYPE_U_CHAR 0x92
#define DOP_INT_TYPE_SHORT 0x22
#define DOP_INT_TYPE_U_SHORT 0xa2
#define DOP_INT_TYPE_INT 0x42
#define DOP_INT_TYPE_U_INT 0xc2
#define DOP_INT_TYPE_BIG_INT 0x82

@interface FFANDOPProtocol : NSObject {
}
/**
 * 类型
 */
@property (nonatomic, assign)uint8_t type;

/**
 * 键名
 */
@property (nonatomic, copy)NSString *name;

/**
 * Map 或者 LIST 的值
 */
@property (nonatomic, retain)FFANDOPProtocol *value_item;

@property (nonatomic, retain)FFANDOPProtocol *key_item;

@property (nonatomic, retain)NSArray *sub_struct;

@property (nonatomic, assign)uint8_t int_type;

@end