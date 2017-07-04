<?php

namespace ffan\dop\protocol;

use ffan\dop\Exception;

/**
 * Class BoolItem
 * @package ffan\dop\protocol
 */
class BoolItem extends Item
{
    /**
     * @var int 类型
     */
    protected $type = ItemType::BOOL;

    /**
     * 设置默认值
     * @param string $value
     * @throws Exception
     */
    public function setDefault($value)
    {
        if (!is_string($value)) {
            throw new Exception('default value error');
        }
        $value = strtolower($value);
        $this->default = 'true' === $value ? 'true' : 'false';
    }
}
