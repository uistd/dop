<?php

namespace ffan\dop\protocol;

use ffan\dop\Exception;

/**
 * Class ItemStruct 结构体
 * @package ffan\dop\protocol
 */
class StructItem extends Item
{
    /**
     * @var int 类型
     */
    protected $type = ItemType::STRUCT;

    /**
     * @var string 结构体名称
     */
    protected $struct_name;

    /**
     * @var Struct
     */
    private $struct;

    /**
     * 设置struct_name
     * @param Struct $struct
     */
    public function setStruct(Struct $struct)
    {
        $this->struct_name = $struct->getClassName();
        $this->struct = $struct;
    }

    /**
     * 获取struct名称
     * @return string
     */
    public function getStructName()
    {
        return $this->struct_name;
    }

    /**
     * 获取struct
     * @return Struct
     */
    public function getStruct()
    {
        return $this->struct;
    }

    /**
     * 设置默认值
     * @param string $value
     * @throws Exception
     */
    public function setDefault($value)
    {
        throw new Exception('`default` is disabled in struct type');
    }
}
