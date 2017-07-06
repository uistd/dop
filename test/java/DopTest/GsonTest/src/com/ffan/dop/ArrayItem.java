package com.ffan.dop;

import java.util.ArrayList;
import java.util.List;

class ArrayItem extends Item {
    /**
     * 所有项
     */
    private List<Item> items = new ArrayList<Item>();

    /**
     * 构造函数
     */
    ArrayItem() {
        this.type = TYPE_ARR;
    }
    
    /**
     * 获取值
     */
    public void add(Item value) {
        this.items.add(value);
    }

    /**
     * 获取所有项
     */
    public List<Item> getItems() {
        return this.items;
    }
}