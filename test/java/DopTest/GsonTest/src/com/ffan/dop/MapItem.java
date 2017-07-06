package com.ffan.dop;

import java.util.HashMap;
import java.util.Map;

class MapItem extends Item {
    /**
     * 所有项
     */
    private Map<Item, Item> items = new HashMap<Item, Item>();

    /**
     * 构造函数
     */
    MapItem() {
        this.type = TYPE_MAP;
    }
    
    /**
     * 获取值
     */
    public void add(Item key, Item value) {
        this.items.put(key, value);
    }

    /**
     * 获取所有项
     */
    public Map<Item, Item> getItems() {
        return this.items;
    }
}