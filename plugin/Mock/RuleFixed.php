<?php

namespace FFan\Dop\Plugin\Mock;

use FFan\Dop\Build\PluginRule;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\Protocol;
use FFan\Dop\Schema\Item as SchemaItem;

/**
 * @package FFan\Dop
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
     * @param SchemaItem $node
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
