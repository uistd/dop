package com.ffan.dop;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public abstract class Item {
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
    public long getValueInt() {
        return 0;
    }

    /**
     * 获取值String
     */
    public String getValueString() {
        return "";
    }

    /**
     * 获取值Float
     */
    public float getValueFloat() {
       return (float) this.getValueDouble(); 
    }

    /**
     * 获取值 double
     */
    public double getValueDouble() {
        return 0.0;
    }

    /**
     * 获取值 byte
     */
    public byte[] getValueByte() {
        return new byte[0];
    }

    /**
     * 获取值 bool
     */
    public boolean getValueBool() {
        return false;
    }
    
    /**
     * 获取值Map
     */
    public Map<Item, Item> getValueMap() {
        return new HashMap<Item, Item>();
    }

    /**
     * 获取值List
     */
    public List<Item> getValueArray() {
        return new ArrayList<Item>();
    }

    /**
     * 获取值 struct
     */
    public DopStruct getValueStruct() {
        return new DopStruct();
    }
}