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
     * @var string 文档信息
     */
    private $doc_info;

    /**
     * Item constructor.
     * @param string $name 名称
     * @param string $doc_info 文档信息 用于报错
     */
    public function __construct($name, $doc_info)
    {
        $this->name = $name;
        $this->doc_info = $doc_info;
    }

    /**
     * 获取元素名称
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取文档信息（在哪个文件的第几行）
     * @return string 
     */
    protected function getDocInfo()
    {
        return $this->doc_info;
    }
}
