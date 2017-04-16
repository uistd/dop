<?php

namespace ffan\dop;

/**
 * Class DoubleItem
 * @package ffan\dop
 */
class DoubleItem extends Item
{
    /**
     * @var int 类型
     */
    protected $type = ItemType::DOUBLE;

    /**
     * 设置默认值
     * @param string $value
     * @throws DOPException
     */
    public function setDefault($value)
    {
        if (!preg_match('/^(-?\d+)(\.\d+)?$/', $value)) {
            throw new DOPException($this->protocol_manager->fixErrorMsg($value . ' 不能作为 double 默认值'));
        }
        $this->default = $value;
    }
}
