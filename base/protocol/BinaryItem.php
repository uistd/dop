<?php

namespace ffan\dop\protocol;

use ffan\dop\Exception;

/**
 * Class ItemBinary
 * @package ffan\dop\protocol
 */
class BinaryItem extends Item
{
    /**
     * @var int 类型
     */
    protected $type = ItemType::BINARY;

    /**
     * 设置默认值
     * @param string $value
     * @return void
     * @throws Exception
     */
    public function setDefault($value)
    {
        throw new Exception('Binary 不支持二进制');
    }
}
