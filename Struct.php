<?php
namespace ffan\dop;

/**
 * Class Struct
 * @package ffan\dop
 */
class Struct
{
    /**
     * @var array
     */
    private $item_list = array();

    /**
     * @var string 命名空间
     */
    private $namespace;

    /**
     * @var string
     */
    private $name;
    
    /**
     * Struct constructor.
     * @param string $namespace 命名空间
     * @param string $name
     */
    public function __construct($namespace, $name)
    {
        if (!is_string($namespace) || '/' !== $namespace[0]) {
            throw new \InvalidArgumentException('namespace error');
        }
        $this->namespace = $namespace;
        $this->name = $name;
    }

    /**
     * 添加元素项
     * @param string $name 名称
     * @param Item $item
     * @return bool
     */
    public function addItem($name, Item $item)
    {
        if (isset($this->item_list[$name])) {
            return false;
        }
        $this->item_list[$name] = $item;
        return true;
    }

    /**
     * 是否存在某个key
     * @param string $name
     * @return bool
     */
    public function hasItem($name)
    {
        return isset($this->item_list[$name]);
    }
}
