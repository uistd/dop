package com.ffan.dop;

import java.util.Map;

class DopProtocol {
    /**
     * 类型
     */
    byte type;

    /**
     * Map 或者 List 的值类型
     */
    DopProtocol value_item;

    /**
     * Map的key
     */
    DopProtocol key_item;

    /**
     * Struct类型
     */
    Map<String, DopProtocol> struct;

    /**
     * 如果是int的时候。int的字节数和是否有符号
     */
    byte int_type;
}