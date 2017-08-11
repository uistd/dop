<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginRule;

/**
 * @package ffan\dop
 */
class RuleRange extends PluginRule
{
    protected $type = MockType::MOCK_RANGE;

    /**
     * @var int|float 随机范围上限
     */
    public $range_min;

    /**
     * @var int|float 随机范围下限
     */
    public $range_max;

    /**
     * 解析规则
     * @param \DOMElement $node
     * @param int $item_type
     */
    function init($node, $item_type = 0)
    {
        list($min, $max) = $this->readSplitSet($node, 'range');
        $min = (int)$min;
        $max = (int)$max;
        if ($max < $min) {
            $max = $min + 1;
        }
        $this->range_min = $min;
        $this->range_max = $max;
    }
}
