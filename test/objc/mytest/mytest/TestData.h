//
// Created by huangshunzhao on 2017/7/16.
// Copyright (c) 2017 __DOP__. All rights reserved.
//

#import <Foundation/Foundation.h>

@interface TestData : NSObject

@property (nonatomic) int uint;

@property (nonatomic) int int64;

@property (nonatomic, copy) NSString* string;

@property (nonatomic, retain) NSMutableArray <NSMutableArray <NSNumber*>*>* list_list;

-(void) print;

@end