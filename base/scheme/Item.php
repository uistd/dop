<?php

namespace FFan\Dop\Scheme;


/**
 * Class Item
 * @package FFan\Dop\Scheme
 */
class Item
{
    /**
     * @var string
     */
    private $xml_doc;

    /**
     * @var array 所有属性
     */
    private $attributes;

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
     * Node constructor.
     * @param int $type
     * @param \DOMElement $node
     */
    public function __construct($type, \DOMElement $node)
    {
        $this->xml_doc = $node->C14N();
        $this->type = $type;
        $this->parse($node);
        $this->attributes = self::getAllAttribute($node);
    }

    /**
     * @param \DOMElement $node
     */
    public function parse($node)
    {

    }

    /**
     * @param \DOMElement $node
     * @return null|array
     */
    public static function getAllAttribute($node)
    {
        $attributes = $node->attributes;
        $count = $attributes->length;
        $result = null;
        for ($i = 0; $i < $count; ++$i) {
            $tmp = $attributes->item($i);
            $name = $tmp->nodeName;
            $value = $tmp->nodeValue;
            $result[$name] = $value;
        }
        return $result;
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
}
