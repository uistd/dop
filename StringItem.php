<?php
namespace ffan\dop;

/**
 * Class StringItem
 * @package ffan\dop
 */
class StringItem extends Item
{
    /**
     * @var int 类型
     */
    protected $type = ItemType::STRING;

    /**
     * 设置默认值
     * @param string $value
     * @throws DOPException
     */
    public function setDefault($value)
    {
        if (!is_string($value)) {
            throw new DOPException($this->protocol_manager->fixErrorMsg('default value error'));
        }
        $this->default = '"'. self::fixLine($value) .'"';
    }
}
