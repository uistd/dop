<?php
namespace ffan\dop;

/**
 * Class Item 协议的每一项
 * @package ffan\dop
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
     * Item constructor.
     * @param string $name 名称
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * 获取元素名称
     */
    public function getName()
    {
        return $this->name;
    }
}
