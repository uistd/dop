<?php

namespace FFan\Dop\Plugin\Mock;

use FFan\Dop\Build\PluginRule;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\Protocol;
use FFan\Std\Common\Str as FFanStr;

/**
 * @package FFan\Dop
 */
class RuleType extends PluginRule
{
    protected static $error_msg = array(
        1 => '不支持的类型'
    );
    /**
     * @var int
     */
    protected $type = MockType::MOCK_BUILD_IN_TYPE;

    /**
     * @var string 内置的类型
     */
    public $build_in_type;

    /**
     * @var array 支持的类型
     */
    private static $allow_type = array(
        'mobile',
        'chineseName',
        'email',
        'date',
        'dateTime'
    );

    /**
     * 解析规则
     * @param Protocol $parser
     * @param \DOMElement $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        $this->build_in_type = FFanStr::camelName(self::read($node, 'type'), false);
        if (!in_array($this->build_in_type, self::$allow_type)) {
            return 1;
        }
        return 0;
    }
}
