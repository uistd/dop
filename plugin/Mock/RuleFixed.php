<?php

namespace UiStd\Dop\Plugin\Mock;

use UiStd\Dop\Build\PluginRule;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Schema\Plugin as SchemaPlugin;
use UiStd\Dop\Schema\Protocol;

/**
 * @package UiStd\Dop
 */
class RuleFixed extends PluginRule
{
    protected $type = MockType::MOCK_FIXED;

    /**
     * @var mixed 固定值
     */
    public $fixed_value;

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
        $fixed_value = $node->get('value');
        $this->fixed_value = Plugin::fixValue($item_type, $fixed_value);
        return 0;
    }
}
