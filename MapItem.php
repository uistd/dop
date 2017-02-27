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
    private $key_type;

    /**
     * @var Item 值类型
     */
    private $value_type;

    /**
     * @var int 类型
     */
    protected $type = ItemType::MAP;

    /**
     * 设置键类型
     * @param Item $key_type
     * @throws DOPException
     */
    public function setKeyType(Item $key_type)
    {
        //目前只支持int 和 string类型的key
        if($key_type !== ItemType::INT && $key_type !== ItemType::STRING) {
            throw new DOPException('key type error. '. $this->getDocInfo());
        }
        $this->key_type = $key_type;
    }

    /**
     * 设置值类型
     * @param Item $value_type
     */
    public function setValueType(Item $value_type)
    {
        $this->value_type = $value_type;
    }
}
