<?php

namespace ffan\dop\plugin\mock;

/**
 * Class MockRule
 * @package ffan\dop
 */
class MockRule
{
    /**
     * 指定范围随机
     */
    const TYPE_RANGE = 1;

    /**
     * 指定值随机
     */
    const TYPE_ENUM = 2;

    /**
     * 固定值
     */
    const TYPE_FIXED = 3;

    /**
     * @var int 数据mock方式
     */
    public $mock_type;

    /**
     * @var int|float 随机范围上限
     */
    public $range_min;

    /**
     * @var int|float 随机范围下限
     */
    public $range_max;

    /**
     * @var array 指定随机值
     */
    public $enum_set;

    /**
     * @var int 随机值数量
     */
    public $enum_size;

    /**
     * @var mixed 固定值
     */
    public $fixed_value;
}
