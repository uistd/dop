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
        this.type = TYPE_BOOL;
    }

    /**
     * 获取值int
     */
    long getValueInt() {
        return this.value ? 1 : 0;
    }

    /**
     * 获取值bool
     */
    boolean getValueBool() {
        return this.value;
    }

    /**
     * 获取值 string
     */
    String getValueString() {
        return this.value ? "true" : "false";
    }

    /**
     * 获取值 double
     */
    double getValueDouble() {
        return this.value ? 1.0 : 0.0;
    }
}