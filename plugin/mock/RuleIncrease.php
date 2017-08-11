<?php

namespace ffan\dop\plugin\mock;

/**
 * @package ffan\dop
 */
class RuleIncrease extends MockRule
{
    protected $type = MockRule::MOCK_INCREASE;

    /**
     * @var int 开始
     */
    public $begin = 1;

    /**
     * @var int 结束
     */
    public $end = 10;

    /**
     * @var int 步长
     */
    public $step = 1;
}
