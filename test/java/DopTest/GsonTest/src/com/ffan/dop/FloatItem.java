package com.ffan.dop;

class FloatItem extends Item {
    /**
     * 值
     */
    private float value;

    /**
     * 构造函数
     */
    FloatItem(float value) {
        this.value = value;
        this.type = TYPE_FLOAT;
    }

    /**
     * 获取值 String
     */
    String getValueString() {
        return String.valueOf(this.value);
    }

    /**
     * 获取值 int
     */
    long getValueInt() {
        return Math.round(this.value);
    }

    /**
     * 获取值double
     */
    double getValueDouble() {
        return this.value;
    }

    /**
     * 获取值 float
     */
    float getValueFloat() {
        return this.value;
    }

    /**
     * 获取值 bool
     */
    boolean getValueBool() {
        return 0 != this.getValueInt();
    }
}