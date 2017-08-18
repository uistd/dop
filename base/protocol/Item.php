<?php

namespace ffan\dop\protocol;

use ffan\dop\build\PluginRule;
use ffan\dop\Exception;
use ffan\dop\Manager;
use ffan\php\utils\Str as FFanStr;

/**
 * Class Item 协议的每一项
 * @package ffan\dop\protocol
 */
abstract class Item
{
    /**
     * @var string 名称
     */
    private $camelName;

    /**
     * @var int 类型
     */
    protected $type;

    /**
     * @var string 注释
     */
    protected $note = '';

    /**
     * @var Manager
     */
    protected $protocol_manager;

    /**
     * @var string 默认值
     */
    protected $default;

    /**
     * @var array 插件数据
     */
    protected $plugin_data_arr;

    /**
     * @var string 下划线命名
     */
    private $underline_name;

    /**
     * @var string 原始名称
     */
    private $original_name;

    /**
     * @var bool 是否保持原始名称
     */
    private $keep_original_name = false;

    /**
     * Item constructor.
     * @param string $camel_name_name 驼峰名称
     * @param Manager $manger
     */
    public function __construct($camel_name_name, Manager $manger)
    {
        $this->camelName = $camel_name_name;
        $this->protocol_manager = $manger;
        $this->underline_name = FFanStr::underlineName($camel_name_name);
    }

    /**
     * 获取元素名称（默认是驼峰名称）
     */
    public function getName()
    {
        return $this->camelName;
    }

    /**
     * 设置注释
     * @param string $note
     * @throws Exception
     */
    public function setNote($note)
    {
        if (!is_string($note)) {
            return;
        }
        $this->note = self::fixLine($note);
    }

    /**
     * 设置默认值
     * @param string $value
     * @return void
     */
    abstract public function setDefault($value);

    /**
     * 获取类型
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 将多行转换成1行
     * @param string $str
     * @return string
     */
    public static function fixLine($str)
    {
        static $patten = array("\r\n", "\n", "\r");
        $str = str_replace($patten, '', $str);
        return $str;
    }

    /**
     * 添加插件数据
     * @param string $plugin_name
     * @param PluginRule $rule
     */
    public function addPluginData($plugin_name, PluginRule $rule)
    {
        $this->plugin_data_arr[$plugin_name] = $rule;
    }

    /**
     * 获取插件Rule
     * @param string $plugin_name
     * @return PluginRule|null
     */
    public function getPluginData($plugin_name)
    {
        return isset($this->plugin_data_arr[$plugin_name]) ? $this->plugin_data_arr[$plugin_name] : null;
    }

    /**
     * 获取注释
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * 是否有设置默认值
     * @return bool
     */
    public function hasDefault()
    {
        return null !== $this->default;
    }

    /**
     * 获取默认值
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * 获取类型的二进制表示
     * @return int
     */
    public function getBinaryType()
    {
        return $this->type;
    }

    /**
     * 获取字段的underline name
     * @return string
     */
    public function getUnderLineName()
    {
        return $this->underline_name;
    }

    /**
     * 获取原始名称
     */
    public function getOriginalName()
    {
        return $this->original_name;
    }

    /**
     * 设置原始名称
     * @param string $original_name
     */
    public function setOriginalName($original_name)
    {
        $this->original_name = $original_name;
    }

    /**
     * 设置是否保持原始名称
     * @param bool $is_keep
     */
    public function setKeepOriginalFlag($is_keep)
    {
        $this->keep_original_name = (bool)$is_keep;
    }

    /**
     * 获取是否原始原始名称
     * @return bool
     */
    public function isKeepOriginalName()
    {
        return $this->keep_original_name;
    }
}
