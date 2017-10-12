<?php

namespace FFan\Dop\Protocol;

/**
 * Class ItemType 变量类型
 * @package FFan\Dop\Protocol
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
     * boolean类型
     */
    const BOOL = 9;
    
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
        'tinyint' => self::INT,
        'int8' => self::INT,
        'uint8' => self::INT,
        'smallint' => self::INT,
        'int16' => self::INT,
        'uint16' => self::INT,
        'int32' => self::INT,
        'uint32' => self::INT,
        'bigint' => self::INT,
        'int64' => self::INT,
        'list' => self::ARR,
        'struct' => self::STRUCT,
        'model' => self::STRUCT,
        'map' => self::MAP,
        'double' => self::DOUBLE,
        'bool' => self::BOOL
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

    /**
     * 类型的类型
     * @param string $node_name
     * @return bool
     */
    public static function isAllowType($node_name)
    {
        $node_name = strtolower($node_name);
        return isset(self::$typeSets[$node_name]);
    }
}
