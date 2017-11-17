<?php

namespace FFan\Dop\Schema;

/**
 * Class Model
 * @package FFan\Dop\Scheme
 */
class Model extends Node
{
    /**
     * 普通struct
     */
    const TYPE_STRUCT = 3;

    /**
     * 请求包
     */
    const TYPE_REQUEST = 2;

    /**
     * 返回包
     */
    const TYPE_RESPONSE = 1;

    /**
     * 普通数据
     */
    const TYPE_DATA = 4;

    /**
     * @var Item[]
     */
    private $item_list = [];

    /**
     * @var string
     */
    private $extend;

    /**
     * @var string
     */
    private $name;

    /**
     * Model constructor.
     * @param string $name
     * @param \DOMElement $node
     */
    public function __construct($name, \DOMElement $node)
    {
        parent::__construct($node);
        $this->name = $name;
    }

    /**
     * @param string $name
     * @param Item $node
     */
    public function addItem($name, $node)
    {
        $name = trim($name);
        $this->item_list[$name] = $node;
    }

    /**
     * 设置继承
     * @param string $extend
     */
    public function setExtend($extend)
    {
        $this->extend = $extend;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取继承的model name
     * @return string|null
     */
    public function getExtend()
    {
        return $this->extend;
    }

    /**
     * 获取所有的node
     * @return Item[]
     */
    public function getItemList()
    {
        return $this->item_list;
    }
}
