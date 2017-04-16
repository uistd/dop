<?php

namespace ffan\dop;

/**
 * Class MapItem
 * @package ffan\dop
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
     * @throws DOPException
     */
    public function setKeyItem(Item $key_item)
    {
        $type = $key_item->getType();
        //目前只支持int 和 string类型的key
        if ($type !== ItemType::INT && $type !== ItemType::STRING) {
            throw new DOPException($this->protocol_manager->fixErrorMsg('Map的key只能是int or string类型'));
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
     * @throws DOPException
     */
    public function setDefault($value)
    {
        throw new DOPException($this->protocol_manager->fixErrorMsg('`default` is disabled in map type'));
    }
}
