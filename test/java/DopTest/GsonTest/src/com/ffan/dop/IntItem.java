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
        this.type = ItemType.INT_TYPE;
    }

    /**
     * 获取值int
     */
    public long getValueInt() {
        return value;
    }

    /**
     * 获取值bool
     */
    public boolean getValueBool() {
        return 0 != value;
    }

    /**
     * 获取值 string
     */
    public String getValueString() {
        return String.valueOf(value);
    }

    /**
     * 获取值 double
     */
    public double getValueDouble() {
        return (double) value;
    }
}