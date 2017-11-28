<?php

namespace FFan\Dop\Plugin\Mock;

use FFan\Dop\Build\PluginRule;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Schema\Plugin as SchemaPlugin;
use FFan\Dop\Schema\Protocol;

/**
 * @package FFan\Dop
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
     * @param Protocol $parser
     * @param SchemaPlugin $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        list($min, $max) = $node->getSplitSet('range');
        $min = (int)$min;
        $max = (int)$max;
        if ($max < $min) {
            $max = $min + 1;
        }
        $this->range_min = $min;
        $this->range_max = $max;
        return 0;
    }
}
