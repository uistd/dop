package com.ffan.dop;

class ItemType {
    //数据类型
    static final byte STRING_TYPE = 1;
    static final byte INT_TYPE = 2;
    static final byte FLOAT_TYPE = 3;
    static final byte BINARY_TYPE = 4;
    static final byte ARR_TYPE = 5;
    static final byte STRUCT_TYPE = 6;
    static final byte MAP_TYPE = 7;
    static final byte DOUBLE_TYPE = 8;
    static final byte BOOL_TYPE = 9;
    static final byte NULL_TYPE = 0;
    
    //int的类型
    static final byte INT_TYPE_CHAR = 0x12;
    static final byte INT_TYPE_U_CHAR = (byte) 0x92;
    static final byte INT_TYPE_SHORT = 0x22;
    static final byte INT_TYPE_U_SHORT = (byte) 0xa2;
    static final byte INT_TYPE_INT = 0x42;
    static final byte INT_TYPE_U_INT = (byte) 0xc2;
    static final byte INT_TYPE_BIG_INT = (byte) 0x82;
}