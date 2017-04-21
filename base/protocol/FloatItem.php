<?php

namespace ffan\dop\protocol;

use ffan\dop\Exception;

/**
 * Class ItemFloat
 * @package ffan\dop\protocol
 */
class FloatItem extends Item
{
    /**
     * @var int 类型
     */
    protected $type = ItemType::FLOAT;

    /**
     * 设置默认值
     * @param string $value
     * @throws Exception
     */
    public function setDefault($value)
    {
        if (!preg_match('/^(-?\d+)(\.\d+)?$/', $value)) {
            throw new Exception($value . ' 不能作为 float 默认值');
        }
        $this->default = $value;
    }
}
