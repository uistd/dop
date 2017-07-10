package com.ffan.dop;

class DoubleItem extends Item {
    /**
     * 值
     */
    private double value;

    /**
     * 构造函数
     */
    DoubleItem(double value) {
        this.value = value;
        this.type = ItemType.DOUBLE_TYPE;
    }

    /**
     * 获取值 String
     */
    public String getValueString() {
        return String.valueOf(this.value);
    }

    /**
     * 获取值 int
     */
    public long getValueInt() {
        return Math.round(this.value);
    }

    /**
     * 获取值double
     */
    public double getValueDouble() {
        return this.value;
    }

    /**
     * 获取值 bool
     */
    public boolean getValueBool() {
        return 0 != this.getValueInt();
    }
}