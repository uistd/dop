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
        this.type = ItemType.FLOAT_TYPE;
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
     * 获取值 float
     */
    public float getValueFloat() {
        return this.value;
    }

    /**
     * 获取值 bool
     */
    public boolean getValueBool() {
        return 0 != this.getValueInt();
    }
}