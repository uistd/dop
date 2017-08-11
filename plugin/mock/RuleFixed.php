<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginRule;

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
     * @param \DOMElement $node
     * @param int $item_type
     */
    function init($node, $item_type = 0)
    {
        $fixed_value = $this->read($node, 'value');
        $this->fixed_value = Plugin::fixValue($item_type, $fixed_value);
    }
}
