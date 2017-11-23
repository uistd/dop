<?php

namespace FFan\Dop\Schema;

/**
 * Class Node
 * @package FFan\Dop\Scheme
 */
class Node
{
    /**
     * @var array 属性列表
     */
    protected $attributes;

    /**
     * @var string
     */
    private $node_name;

    /**
     * @var string 文档
     */
    private $doc;

    /**
     * Node constructor.
     * @param \DOMElement $node
     */
    public function __construct(\DOMElement $node)
    {
        $this->doc = $node->C14N();
        $this->node_name = $node->nodeName;
        $this->attributes = $this->getAllAttribute($node);
    }

    /**
     * @param \DOMElement $node
     * @return null|array
     */
    public function getAllAttribute($node)
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
     * 读取插件属性
     * @param string $attr_name 属性名
     * @param null $default 默认值
     * @return mixed
     */
    public function get($attr_name, $default = null)
    {
        if (!isset($this->attributes[$attr_name])) {
            return $default;
        }
        return trim($this->attributes[$attr_name]);
    }

    /**
     * 读取一个bool值
     * @param string $name
     * @param bool $default 默认值
     * @return bool
     */
    public function getBool($name, $default = false)
    {
        return (bool)$this->get($name, $default);
    }

    /**
     * 读取一个int值
     * @param string $attr_name
     * @param int $default 默认值
     * @return int
     */
    public function getInt($attr_name, $default = 0)
    {
        return (int)$this->get($attr_name, $default);
    }

    /**
     * 读取范围限制
     * @param string $name
     * @param bool $is_int
     * @return array|null
     */
    public function getSplitSet($name, $is_int = true)
    {
        $set_str = $this->get($name);
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
     * 返回所有属性
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * 是否存在某个属性
     * @param string $name
     * @return bool
     */
    public function hasAttribute($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @return string
     */
    public function getNodeName()
    {
        return $this->node_name;
    }

    /**
     * @return string
     */
    public function getDoc()
    {
        return $this->doc;
    }
}
