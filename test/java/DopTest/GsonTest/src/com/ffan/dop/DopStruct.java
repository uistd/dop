package com.ffan.dop;

import java.util.HashMap;
import java.util.Map;

class DopStruct {
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
     * 获取list
     */
    public Item getItemArray(String name) {
       Item item = this.get(name);
       if (Item.TYPE_ARR != item.type) {
           return null;
       }
       return item;
    }

    /**
     * 加入一项
     */
    public void addItem(String name, Item item) {
       this.item_list.put(name, item); 
    }
}