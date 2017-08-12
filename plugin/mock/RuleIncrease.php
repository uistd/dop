<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginRule;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\Protocol;

/**
 * @package ffan\dop
 */
class RuleIncrease extends PluginRule
{
    /**
     * @var array
     */
    protected static $error_msg = array(
        1 => '只有 int 类型 才支持 自增长',
    );

    /**
     * @var int 类型
     */
    protected $type = MockType::MOCK_INCREASE;

    /**
     * @var int 开始
     */
    public $begin = 1;

    /**
     * @var int 步长
     */
    public $step = 1;

    /**
     * 解析规则
     * @param Protocol $parser
     * @param \DOMElement $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        if (ItemType::INT !== $item->getType()) {
            return 1;
        }
        $this->begin = self::readInt($node, 'begin', 1);
        $this->step = self::readInt($node, 'step', 1);
        return 0;
    }
}