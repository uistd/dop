<?php

namespace FFan\Dop\Schema;

/**
 * Class Item
 * @package FFan\Dop\Scheme
 */
class Item extends Node
{
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
     * Node constructor.
     * @param int $type
     * @param \DOMElement $node
     */
    public function __construct($type, \DOMElement $node)
    {
        parent::__construct($node);
        $this->type = $type;
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
     * @return Item[]
     */
    public function getSubItems()
    {
        return $this->sub_item;
    }

    /**
     * @param string $model_name
     */
    public function setSubModel($model_name) {
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
}
