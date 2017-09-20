<?php

namespace FFan\Dop\Protocol;

use FFan\Dop\Exception;

/**
 * Class DoubleItem
 * @package FFan\Dop\Protocol
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
     * @throws Exception
     */
    public function setDefault($value)
    {
        if (!preg_match('/^(-?\d+)(\.\d+)?$/', $value)) {
            throw new Exception($value . ' 不能作为 double 默认值');
        }
        $this->default = $value;
    }
}
