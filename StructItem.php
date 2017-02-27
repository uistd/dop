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
    private $struct_name;

    /**
     * 设置struct_name
     * @param string $struct_name
     * @throws DOPException
     */
    public function setStructName($struct_name)
    {
        if (!$this->validName($struct_name)) {
            throw new DOPException('struct name error!' . $this->getDocInfo());
        }
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
     * struct name是否合法
     * @param string $name
     * @return int
     */
    private function validName($name)
    {
        return preg_match('/^\/?[a-zA-Z_][a-zA-Z_a\d]*(\/[a-zA-Z_][a-zA-Z_\d]*)*$/', $name) > 0;
    }
}
