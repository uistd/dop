<?php

namespace ffan\dop;

/**
 * Class ItemType 变量类型
 * @package ffan\dop
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
        'int32' => self::INT,
        'int8' => self::INT,
        'tinyint' => self::INT,
        'int16' => self::INT,
        'smallint' => self::INT,
        'int64' => self::INT,
        'bigint' => self::INT,
        'list' => self::ARR,
        'struct' => self::STRUCT,
        'map' => self::MAP,
        'double' => self::DOUBLE
    );

    /**
     * @var array int的字节数设置
     */
    private static $int_byte = array(
        'int' => IntItem::BYTE_INT,
        'int32' => IntItem::BYTE_INT,
        'int8' => IntItem::BYTE_TINY,
        'tinyint' => IntItem::BYTE_TINY,
        'int16' => IntItem::BYTE_SMALL,
        'smallint' => IntItem::BYTE_SMALL,
        'int64' => IntItem::BYTE_BIG,
        'bigint' => IntItem::BYTE_BIG,
    );

    /**
     * @var array 每种类型对应的class_name
     */
    private static $class_set = array(
        self::STRING => 'ItemString',
        self::FLOAT => 'ItemFloat',
        self::BINARY => 'ItemBinary',
        self::INT => 'ItemInt',
        self::STRUCT => 'ItemStruct',
        self::ARR => 'ItemList',
        self::MAP => 'ItemMap',
        self::DOUBLE => 'ItemDouble'
    );

    /**
     * 每一种类型对应的字符串
     * @var array
     */
    private static $type_str_set = array(
        self::STRING => 'string',
        self::FLOAT => 'float',
        self::BINARY => 'string',
        self::INT => 'int',
        self::STRUCT => 'struct',
        self::ARR => 'list',
        self::MAP => 'map',
        self::DOUBLE => 'double'
    );

    /**
     * 获取对应的类名
     * @param string $type
     * @return string
     */
    public static function getClassName($type)
    {
        return isset(self::$class_set[$type]) ? self::$class_set[$type] : 'Item';
    }

    /**
     * 获取类型
     * @param int $type
     * @return string
     * @throws DOPException
     */
    public static function getTypeName($type)
    {
        if (!isset(self::$type_str_set[$type])) {
            throw new DOPException('Type:' . $type . ' is not support!');
        }
        return self::$type_str_set[$type];
    }

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
     * 获取int的字节数
     * @param string $type
     * @return int|null
     */
    public static function getIntByte($type)
    {
        return isset(self::$int_byte[$type]) ? self::$int_byte[$type] : IntItem::BYTE_INT;
    }
}
