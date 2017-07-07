package com.ffan.dop;

import java.util.HashMap;
import java.util.Map;

class MapItem extends Item {
    /**
     * 所有项
     */
    private Map<Item, Item> items;

    /**
     * 构造函数
     */
    MapItem(int size) {
        this.type = ItemType.MAP_TYPE;
        this.items = new HashMap<Item, Item>(size);
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