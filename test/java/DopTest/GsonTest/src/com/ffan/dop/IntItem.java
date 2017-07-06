package com.ffan.dop;

class IntItem extends Item {
    
    /**
     * 值
     */
    private long value;

    /**
     * 构造函数
     */
    IntItem(long value) {
        this.value = value;
        this.type = TYPE_INT;
    }

    /**
     * 获取值int
     */
    long getValueInt() {
        return value;
    }

    /**
     * 获取值bool
     */
    boolean getValueBool() {
        return 0 != value;
    }

    /**
     * 获取值 string
     */
    String getValueString() {
        return String.valueOf(value);
    }

    /**
     * 获取值 double
     */
    double getValueDouble() {
        return (double) value;
    }
}