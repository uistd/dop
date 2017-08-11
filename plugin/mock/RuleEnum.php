<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginRule;
use ffan\dop\Exception;
use ffan\php\utils\Str as FFanStr;

/**
 * @package ffan\dop
 */
class RuleEnum extends PluginRule
{
    protected $type = MockType::MOCK_ENUM;

    /**
     * @var array 指定随机值
     */
    public $enum_set;

    /**
     * @var int 随机值数量
     */
    public $enum_size;

    /**
     * 解析规则
     * @param \DOMElement $node
     * @param int $item_type
     * @throws Exception
     */
    function init($node, $item_type = 0)
    {
        $enum_set = FFanStr::split($this->read($node, 'enum'), ',');
        if (empty($enum_set)) {
            throw new Exception('enum 属性填写出错');
        }
        foreach ($enum_set as $i => $each_value) {
            $enum_set[$i] = Plugin::fixValue($item_type, $each_value);
        }
        $this->enum_set = $enum_set;
        $this->enum_size = count($enum_set);
    }
}
