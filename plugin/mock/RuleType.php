<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginRule;
use ffan\php\utils\Str as FFanStr;

/**
 * @package ffan\dop
 */
class RuleType extends PluginRule
{
    protected $type = MockType::MOCK_BUILD_IN_TYPE;

    /**
     * @var string 内置的类型
     */
    public $build_in_type;

    private static $allow_type = array(
        'mobile',
        'chineseName',
        'email',
        'date',
        'dateTime'
    );

    /**
     * 解析规则
     * @param \DOMElement $node
     * @param int $item_type
     */
    function init($node, $item_type = 0)
    {
        $this->build_in_type = FFanStr::camelName($this->read($node, 'type'), false);
        if (!in_array($this->build_in_type, self::$allow_type)) {
            throw new Exception('Unknown build in mock type:' . $this->build_in_type);
        }
    }
}
