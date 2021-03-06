<?php

namespace UiStd\Dop\Protocol;

use UiStd\Dop\Exception;

/**
 * Class MapItem
 * @package UiStd\Dop\Protocol
 */
class MapItem extends Item
{
    /**
     * @var Item 键类型
     */
    protected $key_item;

    /**
     * @var Item 值类型
     */
    protected $value_item;

    /**
     * @var int 类型
     */
    protected $type = ItemType::MAP;

    /**
     * 设置键类型
     * @param Item $key_item
     * @throws Exception
     */
    public function setKeyItem(Item $key_item)
    {
        $type = $key_item->getType();
        //目前只支持int 和 string类型的key
        if ($type !== ItemType::INT && $type !== ItemType::STRING) {
            throw new Exception('Map的key只能是int or string类型');
        }
        $this->key_item = $key_item;
    }

    /**
     * 设置值类型
     * @param Item $value_item
     */
    public function setValueItem(Item $value_item)
    {
        $this->value_item = $value_item;
    }

    /**
     * 获取键的item
     * @return Item
     */
    public function getKeyItem()
    {
        return $this->key_item;
    }

    /**
     * 获取值的item
     * @return Item
     */
    public function getValueItem()
    {
        return $this->value_item;
    }

    /**
     * 设置默认值
     * @param string $value
     * @throws Exception
     */
    public function setDefault($value)
    {
        throw new Exception('`default` is disabled in map type');
    }
}
