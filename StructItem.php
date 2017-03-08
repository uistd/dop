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
     * 设置struct_name
     * @param string $struct_name
     * @throws DOPException
     */
    public function setStructName($struct_name)
    {
        $this->struct_name = $struct_name;
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
     * 设置默认值
     * @param string $value
     * @throws DOPException
     */
    public function setDefault($value)
    {
        throw new DOPException($this->protocol_manager->fixErrorMsg('`default` is disabled in struct type'));
    }
}
