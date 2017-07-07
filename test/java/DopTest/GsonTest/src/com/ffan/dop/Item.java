package com.ffan.dop;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

abstract class Item {
    /**
     * 名称
     */
    String name;

    /**
     * 类型
     */
    protected byte type;
    
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