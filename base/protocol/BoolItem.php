<?php

namespace FFan\Dop\Protocol;

use FFan\Dop\Exception;

/**
 * Class BoolItem
 * @package FFan\Dop\Protocol
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
