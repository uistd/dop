<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginRule;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\Protocol;

/**
 * @package ffan\dop
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
     * @param \DOMElement $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        $item_type = $item->getType();
        $fixed_value = self::read($node, 'value');
        $this->fixed_value = Plugin::fixValue($item_type, $fixed_value);
        return 0;
    }
}
