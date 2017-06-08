<?php

namespace ffan\dop\protocol;

/**
 * Class ItemType 变量类型
 * @package ffan\dop\protocol
 */
class ItemType
{
    /**
     * 字符串
     */
    const STRING = 1;

    /**
     * int
     */
    const INT = 2;

    /**
     * 单精度浮点数
     */
    const FLOAT = 3;

    /**
     * 二进制流
     */
    const BINARY = 4;

    /**
     * 数组
     */
    const ARR = 5;

    /**
     * 结构休
     */
    const STRUCT = 6;

    /**
     * 关联数组
     */
    const MAP = 7;

    /**
     * 双精度浮点数
     */
    const DOUBLE = 8;

    /**
     * 类型设置
     * @var array
     */
    private static $typeSets = array(
        'varchar' => self::STRING,
        'str' => self::STRING,
        'string' => self::STRING,
        'float' => self::FLOAT,
        'bin' => self::BINARY,
        'binary' => self::BINARY,
        'int' => self::INT,
        'uint' => self::INT,
        'int8' => self::INT,
        'uint8' => self::INT,
        'int16' => self::INT,
        'uint16' => self::INT,
        'int32' => self::INT,
        'uint32' => self::INT,
        'int64' => self::INT,
        'list' => self::ARR,
        'struct' => self::STRUCT,
        'map' => self::MAP,
        'double' => self::DOUBLE
    );

    /**
     * 填写写的类型字符转换
     * @param string $type
     * @return null|int
     */
    public static function getType($type)
    {
        $type = strtolower($type);
        if (isset(self::$typeSets[$type])) {
            return self::$typeSets[$type];
        }
        return null;
    }
}
