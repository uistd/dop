<?php

namespace ffan\dop\plugin\validator;

/**
 * Class ValidRule 数据有效规则
 * @package ffan\dop
 */
class ValidRule
{
    /**
     * 字符串长度计算方式：按显示宽度 传统的 ascii 占1位，其它2位
     */
    const STR_LEN_BY_DISPLAY = 1;

    /**
     * 字符串长度计算方式：按实际占用字节数
     */
    const STR_LEN_BY_BYTE = 2;

    /**
     * 字符串长度计算方式: 按字数 英文字母和汉字都是1的长度，比较容易理解
     */
    const STR_LEN_BY_LETTER = 4;

    /**
     * @var array 内置数据格式
     */
    private static $build_in_type = array(
        'mobile' => true,
        'email' => true,
        'url' => true,
        'ip' => true,
        'qq' => true,
        'zip_code' => true, //邮编
        'plate_number' => true, //车牌
        'date' => true, //日期
        'date_time' => true, //时间
        'phone' => true, //电话号码
        'id_card' => true, //中国居民身份,
        'price' => true, //价格
        'number' => true,
    );

    /**
     * @var int|float 最小值
     */
    public $min_value;

    /**
     * @var int|float 最大值
     */
    public $max_value;

    /**
     * @var int 最大长度
     */
    public $max_str_len;

    /**
     * @var int 最小长度
     */
    public $min_str_len;

    /**
     * @var int 字符串长度计算方式：1：显示宽度 2：实际字节数 3：固定为1
     */
    public $str_len_type;

    /**
     * @var bool 是否是必须的参数
     */
    public $is_require;

    /**
     * var bool 是否自动 trim
     */
    public $is_trim;

    /**
     * @var bool 是否自动加 slashes
     */
    public $is_add_slashes;

    /**
     * @var bool 是否添加html special chars
     */
    public $is_html_special_chars;

    /**
     * @var bool 是否添加 strip tags
     */
    public $is_strip_tags;

    /**
     * @var string 字符串的格式
     */
    public $format_set;

    /**
     * @var int 小数点精度
     */
    public $precision;

    /**
     * @var string require 检查出错后的消息
     */
    public $require_msg;

    /**
     * @var string range 检查出错后的消息
     */
    public $range_msg;

    /**
     * @var string format 检查出错后的消息
     */
    public $format_msg;

    /**
     * @var string length检查出错后的消息
     */
    public $length_msg;

    /**
     * @var string 通用的错误消息
     */
    public $err_msg;

    /**
     * 数据类型格式化
     * @param string $type
     * @return bool
     */
    public static function isBuildInType($type)
    {
        return isset(self::$build_in_type[$type]);
    }
}
