<?php
namespace ffan\dop;

/**
 * Class ListItem
 * @package ffan\dop
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
     * @throws DOPException
     */
    public function setDefault($value)
    {
        throw new DOPException($this->protocol_manager->fixErrorMsg('`default` is disabled in list type'));
    }
}
