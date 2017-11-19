<?php

namespace FFan\Dop\Plugin\Mock;

use FFan\Dop\Build\PluginRule;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\Protocol;
use FFan\Std\Common\Str as FFanStr;
use FFan\Dop\Schema\Item as SchemaItem;

/**
 * @package FFan\Dop
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
     * @param SchemaItem $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        $field = $node->get('pair');
        if (empty($field)) {
            return 1;
        }
        $map_set = $node->get('map');
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
