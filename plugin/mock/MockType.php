<?php

namespace ffan\dop\plugin\mock;

/**
 * @package ffan\dop
 */
class MockType
{
    /**
     * 指定范围随机
     */
    const MOCK_RANGE = 1;

    /**
     * 指定值随机
     */
    const MOCK_ENUM = 2;

    /**
     * 固定值
     */
    const MOCK_FIXED = 3;

    /**
     * 内置类型
     */
    const MOCK_BUILD_IN_TYPE = 4;

    /**
     * 自增类型
     */
    const MOCK_INCREASE = 5;

    /**
     * 配对
     */
    const MOCK_PAIR = 6;

    /**
     * 直接使用其它对象的某个字段
     */
    const MOCK_USE = 7;
}
