<?php

namespace ffan\dop;

/**
 * Class IntItem int类型元素
 * @package ffan\dop
 */
class IntItem extends Item
{
    /**
     * 1位
     */
    const BYTE_TINY = 1;

    /**
     * 2位int
     */
    const BYTE_SMALL = 2;

    /**
     * 4位int
     */
    const BYTE_INT = 4;

    /**
     * 8位int
     */
    const BYTE_BIG = 8;

    /**
     * @var int 位数（默认4字节 32位）
     */
    private $byte = 4;

    /**
     * @var bool 是否是无符号数
     */
    private $is_unsigned = false;

    /**
     * @var int 类型
     */
    protected $type = ItemType::INT;

    /**
     * 设置字节数
     * @param int $byte
     */
    public function setByte($byte)
    {
        if (!is_int($byte) || ($byte !== self::BYTE_INT && $byte !== self::BYTE_SMALL
                && $byte !== self::BYTE_TINY && $byte !== self::BYTE_BIG)
        ) {
            throw new \InvalidArgumentException('invalid byte');
        }
        $this->byte = $byte;
    }

    /**
     * 设置无符号
     */
    public function setUnsigned()
    {
        $this->is_unsigned = true;
    }

    /**
     * 是否是无符号
     */
    public function isUnsigned()
    {
        return $this->is_unsigned;
    }

    /**
     * 设置默认值
     * @param string $value
     * @throws DOPException
     */
    public function setDefault($value)
    {
        if (!is_numeric($value)) {
            throw new DOPException('默认值只能是int类型');
        }
        $this->default = $value;
    }
}
