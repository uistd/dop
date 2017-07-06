package com.ffan.dop;

class StructItem extends Item{
    /**
     * 值
     */
    private DopStruct value;

    /**
     * 构造函数
     */
    StructItem(DopStruct value) {
        this.value = value;
        this.type = TYPE_STRUCT;
    }

    /**
     * 获取值 Struct
     */
    DopStruct getValueStruct() {
        return this.value;
    }
}