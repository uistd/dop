<?php

namespace UiStd\Dop\Plugin\Mock;

use UiStd\Dop\Build\PluginRule;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Schema\Protocol;
use UiStd\Common\Str as FFanStr;
use UiStd\Dop\Schema\Plugin as SchemaPlugin;

/**
 * @package UiStd\Dop
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
     * @param SchemaPlugin $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        $item_type = $item->getType();
        $enum_set = FFanStr::split($node->get('enum'), ',');
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
