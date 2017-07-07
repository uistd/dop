package com.ffan.dop;

class StringItem extends Item {
    /**
     * 值
     */
    private String value;
    
    /**
     * 构造函数
     */
    StringItem(String value) {
        this.value = value;
        this.type = ItemType.STRING_TYPE;
    }

    /**
     * 获取值 String
     */
    String getValueString() {
        return this.value;
    }

    /**
     * 获取值 int
     */
    long getValueInt() {
        return Long.parseLong(this.value);
    }

    /**
     * 获取值double
     */
    double getValueDouble() {
        return Double.valueOf(this.value);
    }

    /**
     * 获取值 bool
     */
    boolean getValueBool() {
        if (value.length() <= 5) {
            String tmp_value = this.value.toLowerCase();
            if (tmp_value.equals("true")) {
                return true;
            }
            if (tmp_value.equals("false")) {
                return false;
            }
        }
        return 0 != this.getValueInt();
    }

    /**
     * 获取值 byte[]
     */
    byte[] getValueByte() {
        return this.value.getBytes();
    }
}