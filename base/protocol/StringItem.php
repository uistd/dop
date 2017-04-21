<?php

namespace ffan\dop\protocol;

use ffan\dop\Exception;

/**
 * Class StringItem
 * @package ffan\dop\protocol
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
