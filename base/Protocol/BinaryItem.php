<?php

namespace UiStd\Dop\Protocol;

use UiStd\Dop\Exception;

/**
 * Class ItemBinary
 * @package UiStd\Dop\Protocol
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
