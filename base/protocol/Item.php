<?php

namespace ffan\dop\protocol;

use ffan\dop\build\PluginRule;
use ffan\dop\Exception;
use ffan\dop\Manager;

/**
 * Class Item 协议的每一项
 * @package ffan\dop\protocol
 */
abstract class Item
{
    /**
     * @var string 名称
     */
    private $name;

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
     * @var string 字段的真实名字
     */
    private $real_name;

    /**
     * Item constructor.
     * @param string $name 名称
     * @param Manager $manger
     */
    public function __construct($name, Manager $manger)
    {
        $this->name = $name;
        $this->protocol_manager = $manger;
    }

    /**
     * 获取元素名称
     */
    public function getName()
    {
        return $this->name;
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
     * 设置真实的字段名
     * @param string $name
     * @throws Exception
     */
    public function setRealName($name)
    {
        if (!is_string($name) || empty($name)) {
            throw new Exception('Invalid item name');
        }
        $this->real_name = $name;
    }

    /**
     * 获取字段的真实name
     * @return string
     */
    public function getRealName()
    {
        return $this->real_name || '';
    }
}
