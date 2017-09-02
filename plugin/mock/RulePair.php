<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginRule;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\Protocol;
use FFan\Std\Common\Str as FFanStr;

/**
 * @package ffan\dop
 */
class RulePair extends PluginRule
{
    protected static $error_msg = array(
        1 => '未找到配对的字段设置',
        2 => 'map值不能为空'
    );
    /**
     * @var int 类型
     */
    protected $type = MockType::MOCK_PAIR;

    /**
     * @var string 与之配对的字段名
     */
    public $key_field;

    /**
     * @var array 值数组
     */
    public $value_set;

    /**
     * 解析规则
     * @param Protocol $parser
     * @param \DOMElement $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        $field = self::read($node, 'pair');
        if (empty($field)) {
            return 1;
        }
        $map_set = self::read($node, 'map');
        $value_set = FFanStr::dualSplit($map_set, ',', ':');
        if (empty($value_set)) {
            return 2;
        }
        $item_type = $item->getType();
        $new_set = array();
        foreach ($value_set as $key => $value) {
            $new_set[Plugin::fixValue(ItemType::STRING, $key)] = Plugin::fixValue($item_type, $value);
        }
        $this->key_field = $field;
        $this->value_set = $new_set;
        return 0;
    }
}
