<?php

namespace FFan\Dop\Scheme;

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
     * @var string 依赖的Model
     */
    private $require_model;

    /**
     * @var Plugin[] 插件列表
     */
    private $plugin_list;

    /**
     * Node constructor.
     * @param int $type
     * @param \DOMElement $node
     */
    public function __construct($type, \DOMElement $node)
    {
        parent::__construct($node);
        $this->type = $type;
        $this->attributes = self::getAllAttribute($node);
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
     * 设置依赖的model
     * @param string $model_name
     */
    public function setRequireModel($model_name)
    {
        $this->require_model = $model_name;
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
}
