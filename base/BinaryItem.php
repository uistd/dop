<?php

namespace ffan\dop;

/**
 * Class ItemBinary
 * @package ffan\dop
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
     * @throws DOPException
     */
    public function setDefault($value)
    {
        throw new DOPException('Binary 不支持二进制');
    }
}
