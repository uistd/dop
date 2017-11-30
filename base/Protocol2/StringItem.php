<?php

namespace FFan\Dop\Protocol;

use FFan\Dop\Exception;

/**
 * Class StringItem
 * @package FFan\Dop\Protocol
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
     * @throws Exception
     */
    public function setDefault($value)
    {
        if (!is_string($value)) {
            throw new Exception('default value error');
        }
        $this->default = '"' . self::fixLine($value) . '"';
    }
}
