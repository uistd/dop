package com.ffan.dop;

class BoolItem extends Item {
    /**
     * 值
     */
    private boolean value;

    /**
     * 构造函数
     */
    BoolItem(boolean value) {
        this.value = value;
        this.type = ItemType.BOOL_TYPE;
    }

    /**
     * 获取值int
     */
    public long getValueInt() {
        return this.value ? 1 : 0;
    }

    /**
     * 获取值bool
     */
    public boolean getValueBool() {
        return this.value;
    }

    /**
     * 获取值 string
     */
    public String getValueString() {
        return this.value ? "true" : "false";
    }

    /**
     * 获取值 double
     */
    public double getValueDouble() {
        return this.value ? 1.0 : 0.0;
    }
}