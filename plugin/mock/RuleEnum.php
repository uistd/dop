<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginRule;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\Protocol;
use ffan\php\utils\Str as FFanStr;

/**
 * @package ffan\dop
 */
class RuleEnum extends PluginRule
{
    /**
     * @var array 错误消息
     */
    protected static $error_msg = array(
        1 => 'enum 属性填写出错'
    );

    /**
     * @var int 类型
     */
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
     * @param Protocol $parser
     * @param \DOMElement $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        $item_type = $item->getType();
        $enum_set = FFanStr::split(self::read($node, 'enum'), ',');
        if (empty($enum_set)) {
            return 1;
        }
        foreach ($enum_set as $i => $each_value) {
            $enum_set[$i] = Plugin::fixValue($item_type, $each_value);
        }
        $this->enum_set = $enum_set;
        $this->enum_size = count($enum_set);
        return 0;
    }
}
