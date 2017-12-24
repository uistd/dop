<?php

namespace UiStd\Dop\Schema;

/**
 * Class Item
 * @package UiStd\Dop\Scheme
 */
class Item extends Node
{
    /**
     * define类型
     */
    const TYPE_DEFINE = 0xff;

    /**
     * @var int
     */
    private $type;

    /**
     * @var array 子节点
     */
    private $sub_item;

    /**
     * @var Plugin[] 插件列表
     */
    private $plugin_list;

    /**
     * @var string
     */
    private $sub_model_name;

    /**
     * @var string
     */
    private $doc;

    /**
     * @var string 如果 是define类型，使用的namespace
     */
    private $use_ns;

    /**
     * Node constructor.
     * @param int $type
     * @param \DOMElement $node
     * @param string $namespace
     */
    public function __construct($type, \DOMElement $node, $namespace)
    {
        parent::__construct($node);
        $this->type = $type;
        $this->doc = $namespace . ' Line' . $node->getLineNo() . ' ' . $node->C14N();
    }

    /**
     * 增加子item
     * @param Item $item
     */
    public function addSubItem($item)
    {
        $this->sub_item[] = $item;
    }

    /**
     * @param $name
     * @param Plugin $plugin
     */
    public function addPlugin($name, Plugin $plugin)
    {
        $this->plugin_list[$name] = $plugin;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $ns
     */
    public function setUseNs($ns)
    {
        $this->use_ns = $ns;
    }

    /**
     * 获取使用的ns
     * @return string
     */
    public function getUseNs()
    {
        return $this->use_ns;
    }

    /**
     * @return Item[]
     */
    public function getSubItems()
    {
        return $this->sub_item;
    }

    /**
     * @param string $model_name
     */
    public function setSubModel($model_name)
    {
        $this->sub_model_name = $model_name;
    }

    /**
     * @return string
     */
    public function getSubModelName()
    {
        return $this->sub_model_name;
    }

    /**
     * @return Plugin[]
     */
    public function getPluginList()
    {
        return $this->plugin_list;
    }

    /**
     * 获取某个Plugin
     * @param string $plugin_name
     * @return Plugin|null
     */
    public function getPlugin($plugin_name)
    {
        return isset($this->plugin_list[$plugin_name]) ? $this->plugin_list[$plugin_name] : null;
    }

    /**
     * @return string
     */
    public function getDoc()
    {
        return $this->doc;
    }
}
