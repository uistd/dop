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
     * @var Action
     */
    private $action;

    /**
     * @var int 类型
     */
    private $type;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string 文档
     */
    private $doc;

    /**
     * Model constructor.
     * @param string $namespace
     * @param string $name
     * @param int $type
     * @param \DOMElement $node
     */
    public function __construct($namespace, $name, $type, \DOMElement $node)
    {
        parent::__construct($node);
        $this->doc = $namespace . '/' . $name . ' line:' . $node->getLineNo() . PHP_EOL . $node->C14N();
        $this->name = $name;
        $this->namespace = $namespace;
        $this->type = $type;
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

    /**
     * @param Action $action
     */
    public function setAction(Action $action)
    {
        $this->action = $action;
    }

    /**
     * @return Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getNameSpace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getDoc()
    {
        return $this->doc;
    }
}
