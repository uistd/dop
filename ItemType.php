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
     * 浮点数
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
     * 类型设置
     * @var array
     */
    private static $typeSets = array(
        'varchar' => self::STRING,
        'str' => self::STRING,
        'string' => self::STRING,
        'float' => self::FLOAT,
        'bin' => self::BINARY,
        'int' => self::INT,
        'int32' => self::INT,
        'int8' => self::INT,
        'tinyint' => self::INT,
        'int16' => self::INT,
        'smallint' => self::INT,
        'int64' => self::INT,
        'bigint' => self::INT,
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
        self::MAP => 'ItemMap'
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
     * 填写写的类型字符转换
     * @param string $type
     * @return null|int
     */
    public static function getType($type)
    {
        $type = strtolower($type);
        if (isset(self::$typeSets[$type])) {
            return self::$typeSets[$type];
        } else {
            $first_char = $type[0];
            $last_char = $type[strlen($type) - 1];
            //如果{ }配对，表示 对象
            if ('{' === $first_char && '}' === $last_char ) {
                return ItemType::STRUCT;
            }
            //如果[ ]配对，表示 数组 或者 关联数组
            elseif ('[' === $first_char && ']' === $last_char) {
                //不带 => 表示数组
                if (false === strpos($last_char, '=>')) {
                    return ItemType::ARR;
                } else {
                    return ItemType::MAP;
                }
            }
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
