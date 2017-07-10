package com.ffan.dop;

class NullItem extends Item {
    /**
     * 构造函数
     */
    NullItem() {
        this.type = 0;
    }

    /**
     * 获取值int
     */
    public long getValueInt() {
        return 0;
    }

    /**
     * 获取值bool
     */
    public boolean getValueBool() {
        return false;
    }

    /**
     * 获取值 string
     */
    public String getValueString() {
        return "null";
    }

    /**
     * 获取值 double
     */
    public double getValueDouble() {
        return 0.0;
    }
}