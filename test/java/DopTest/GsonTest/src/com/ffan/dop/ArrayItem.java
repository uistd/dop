package com.ffan.dop;

import java.util.ArrayList;
import java.util.List;

class ArrayItem extends Item {
    /**
     * 所有项
     */
    private List<Item> items;

    /**
     * 构造函数
     */
    ArrayItem(int size) {
        this.type = ItemType.ARR_TYPE;
        this.items = new ArrayList<Item>(size);
    }
    
    /**
     * 获取值
     */
    void add(Item value) {
        this.items.add(value);
    }

    /**
     * 获取所有项
     */
    public List<Item> getItems() {
        return this.items;
    }
}