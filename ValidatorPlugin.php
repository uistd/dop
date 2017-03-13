<?php
namespace ffan\dop;

/**
 * Class ValidatorPlugin 数据有效性检验
 * @package ffan\dop
 */
class ValidatorPlugin
{
    /**
     * @var array 规则
     */
    private $rule_list;

    /**
     * @var \DOMElement 节点
     */
    private $node;
    
    /**
     * ValidatorPlugin constructor.
     * @param \DOMElement $node
     */
    public function __construct(\DOMElement $node)
    {
        $this->node = $node;
    }

    /**
     * 规则读取
     */
    private function readRule()
    {
        
        
    }

    /**
     * 读取一条规则
     * @param string $name 规则名
     * @param null $default
     * @return mixed
     */
    private function read($name, $default = null)
    {
        $attr_name = $this->attributeName($name);
        if (!$this->node->hasAttribute($attr_name)) {
            return $default;
        }
        return trim($this->node->getAttribute($attr_name));
    }

    /**
     * 属性名称
     * @param string $name 属性名
     * @return string
     */
    private function attributeName($name)
    {
        return 'v-'. $name;
    }
}