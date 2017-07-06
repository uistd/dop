package com.ffan.dop;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

abstract class Item {
    static final int TYPE_STRING = 1;
    static final int TYPE_INT = 2;
    static final int TYPE_FLOAT = 3;
    static final int TYPE_BINARY = 4;
    static final int TYPE_ARR = 5;
    static final int TYPE_STRUCT = 6;
    static final int TYPE_MAP = 7;
    static final int TYPE_DOUBLE = 8;
    static final int TYPE_BOOL = 9;

    /**
     * 名称
     */
    String name;

    /**
     * 类型
     */
    byte type;
    
    /**
     * 获取类型
     */
    public byte getType() {
        return this.type;
    }
    
    /**
     * 获取int
     */
    long getValueInt() {
        return 0;
    }

    /**
     * 获取值String
     */
    String getValueString() {
        return "";
    }

    /**
     * 获取值Float
     */
    float getValueFloat() {
       return (float) this.getValueDouble(); 
    }

    /**
     * 获取值 double
     */
    double getValueDouble() {
        return 0.0;
    }

    /**
     * 获取值 byte
     */
    byte[] getValueByte() {
        return new byte[0];
    }

    /**
     * 获取值 bool
     */
    boolean getValueBool() {
        return false;
    }
    
    /**
     * 获取值Map
     */
    Map<Item, Item> getValueMap() {
        return new HashMap<Item, Item>();
    }

    /**
     * 获取值List
     */
    List<Item> getValueArray() {
        return new ArrayList<Item>();
    }

    /**
     * 获取值 struct
     */
    DopStruct getValueStruct() {
        return new DopStruct();
    }
}