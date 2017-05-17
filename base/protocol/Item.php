<?php

namespace ffan\dop\protocol;

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
     * 获取属性的魔术方法
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new Exception('No property ' . $name);
        }
        return $this->$name;
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
     * @param object $data
     */
    public function addPluginData($plugin_name, $data)
    {
        $this->plugin_data_arr[$plugin_name] = $data;
    }

    /**
     * 获取插件数据
     * @param string $plugin_name
     * @return Object
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
}
