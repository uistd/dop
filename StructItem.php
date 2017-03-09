<?php
namespace ffan\dop;

/**
 * Class ItemStruct 结构体
 * @package ffan\dop
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
    public function setStructName(Struct $struct)
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
     * @throws DOPException
     */
    public function setDefault($value)
    {
        throw new DOPException($this->protocol_manager->fixErrorMsg('`default` is disabled in struct type'));
    }
}
