package com.ffan.dop;

import java.util.HashMap;
import java.util.Map;

public class DopStruct {
    /**
     * 所有的项
     */
    private Map<String, Item> item_list = new HashMap<String, Item>();

    /**
     * 获取一项
     */
    public Item get(String name) {
        return item_list.get(name);
    }

    /**
     * 加入一项
     */
    void addItem(String name, Item item) {
       this.item_list.put(name, item); 
    }
}