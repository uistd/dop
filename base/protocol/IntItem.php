<?php

namespace ffan\dop\protocol;

use ffan\dop\Exception;

/**
 * Class IntItem int类型元素
 * @package ffan\dop\protocol
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
     * @var array int的字节数设置
     */
    private static $int_byte = array(
        'int' => IntItem::BYTE_INT,
        'uint' => IntItem::BYTE_INT,
        'int8' => IntItem::BYTE_TINY,
        'uint8' => IntItem::BYTE_TINY,
        'int16' => IntItem::BYTE_SMALL,
        'uint16' => IntItem::BYTE_SMALL,
        'int32' => IntItem::BYTE_INT,
        'uint32' => IntItem::BYTE_INT,
        'int64' => IntItem::BYTE_BIG,
        'uint64' => IntItem::BYTE_BIG,
    );

    /**
     * @var array 无符号int标签
     */
    private static $unsigned_set = array(
        'uint' => true,
        'uint8' => true,
        'uint16' => true,
        'uint32' => true,
        'uint64' => true,
    );

    /**
     * 设置int字节数 和 是否无符号
     * @param string $tag_name
     * @throws Exception
     */
    public function setIntType($tag_name)
    {
        $tag_name = strtolower($tag_name);
        if (!isset(self::$int_byte[$tag_name])) {
            throw new Exception('Unknown int type:'. $tag_name);
        }
        $this->byte = self::$int_byte[$tag_name];
        $this->is_unsigned = isset(self::$unsigned_set[$tag_name]);
    }

    /**
     * 设置默认值
     * @param string $value
     * @throws Exception
     */
    public function setDefault($value)
    {
        if (!is_numeric($value)) {
            throw new Exception('默认值只能是int类型');
        }
        $this->default = $value;
    }

    /**
     * 获取类型的二进制表示
     * @return int
     */
    public function getBinaryType()
    {
        //第1位表示 是否有符号  第2-4位 字节长度 最后4位表示 int
        $result = $this->type;
        $byte_len = $this->byte << 4;
        if ($this->is_unsigned) {
            $result |= 0x80;
        }
        $result |= $byte_len;
       return $result; 
    }
}
