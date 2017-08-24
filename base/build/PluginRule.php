<?php

namespace ffan\dop\build;

use ffan\dop\protocol\Item;
use ffan\dop\protocol\Protocol;

/**
 * Class PluginRule 插件规则
 * @package ffan\dop\build
 */
class PluginRule
{

    /**
     * @var array 错误消息设置
     */
    protected static $error_msg;

    /**
     * @var string 继承设置
     */
    public $extend_item;

    /**
     * @var string 继承的类名
     */
    public $extend_class;

    /**
     * @var int 类型
     */
    protected $type;

    /**
     * 解析规则
     * @param Protocol $parser
     * @param \DOMElement $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {

    }

    /**
     * 获取类型
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 设置类型
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * 长度范围限制
     * @param \DOMElement $node
     * @param string $name
     * @param bool $is_int
     * @return array|null
     */
    public static function readSplitSet($node, $name, $is_int = true)
    {
        $set_str = self::read($node, $name);
        $min = null;
        $max = null;
        if (0 === strlen($set_str)) {
            return [$min, $max];
        }
        if (false === strpos($set_str, ',')) {
            $max = ($is_int) ? (int)$set_str : (float)$set_str;
        } else {
            $tmp = explode(',', $set_str);
            $min_str = trim($tmp[0]);
            $max_str = trim($tmp[1]);
            if ($is_int) {
                if (strlen($min_str) > 0) {
                    $min = (int)$min_str;
                }
                if (strlen($max_str) > 0) {
                    $max = (int)$max_str;
                }
            } else {
                if (strlen($min_str)) {
                    $min = (float)$min_str;
                }
                if (strlen($max_str) > 0) {
                    $max = (float)$max_str;
                }
            }
        }
        if ($min !== null && $max !== null && $max < $min) {
            $max = $min = null;
        }
        return [$min, $max];
    }

    /**
     * 读取一个bool值
     * @param \DOMElement $node
     * @param string $name
     * @param bool $default 默认值
     * @return bool
     */
    public static function readBool($node, $name, $default = false)
    {
        $set_str = self::read($node, $name, $default);
        return (bool)$set_str;
    }

    /**
     * 读取插件属性
     * @param \DOMElement $node
     * @param string $attr_name 属性名
     * @param null $default 默认值
     * @return mixed
     */
    public static function read($node, $attr_name, $default = null)
    {
        if (!$node->hasAttribute($attr_name)) {
            return $default;
        }
        return trim($node->getAttribute($attr_name));
    }

    /**
     * 读取一个int值
     * @param \DOMElement $node
     * @param string $attr_name
     * @param int $default 默认值
     * @return int
     */
    public static function readInt($node, $attr_name, $default = 0)
    {
        if (!$node->hasAttribute($attr_name)) {
            return $default;
        }
        return (int)trim($node->getAttribute($attr_name));
    }

    /**
     * 获取错误数据
     * @param $error_code
     * @return string
     */
    public function getErrorMsg($error_code)
    {
        return isset(static::$error_msg[$error_code]) ? static::$error_msg[$error_code] : 'Unknown error';
    }
}
