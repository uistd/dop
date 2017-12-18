<?php

namespace UiStd\Dop\Protocol;

use UiStd\Dop\Exception;

/**
 * Class ListItem
 * @package UiStd\Dop\Protocol
 */
class ListItem extends Item
{
    /**
     * @var Item 数组
     */
    protected $item;

    /**
     * @var int 类型
     */
    protected $type = ItemType::ARR;

    /**
     * 设置数组类型
     * @param Item $item
     */
    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    /**
     * 获取数组类型
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * 设置默认值
     * @param string $value
     * @throws Exception
     */
    public function setDefault($value)
    {
        throw new Exception('`default` is disabled in list type');
    }
}
