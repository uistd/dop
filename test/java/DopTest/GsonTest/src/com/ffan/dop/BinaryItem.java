package com.ffan.dop;

import java.util.Base64;

class BinaryItem extends Item {
    /**
     * 值
     */
    private byte[] value;

    /**
     * 构造函数
     */
    BinaryItem(byte[] value) {
        this.value = value;
        this.type = ItemType.BINARY_TYPE;
    }

    /**
     * 获取值 byte
     */
    public byte[] getValueByte() {
        return  this.value;
    }

    /**
     * 获取值 string
     */
    public String getValueString() {
        return Base64.getEncoder().encodeToString(this.value);
    }
}