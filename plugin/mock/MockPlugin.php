<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\DOPException;
use ffan\dop\Item;
use ffan\dop\ItemType;
use ffan\dop\ListItem;
use ffan\dop\plugin\Plugin;
use ffan\php\utils\Str as FFanStr;

/**
 * Class MockPlugin
 * @package ffan\dop
 */
class MockPlugin extends Plugin
{
    /**
     * @var string
     */
    protected $name = 'mock';

    /**
     * @var string 属性前缀
     */
    protected $attribute_name_prefix = 'mock';

    /**
     * 初始化
     * @param \DOMElement $node
     * @param Item $item
     * @throws DOPException
     */
    public function init(\DOMElement $node, Item $item)
    {
        if (!self::isSupport($item)) {
            return;
        }
        $mock_rule = new MockRule();
        $find_flag = false;
        $attr_range = $this->attributeName('range');
        $item_type = $item->getType();
        //在一个范围内 mock
        if ($node->hasAttribute($attr_range)) {
            list($min, $max) = $this->readSplitSet($node, 'range');
            $min = (int)$min;
            $max = (int)$max;
            if ($max < $min) {
                $max = $min + 1;
            }
            $mock_rule->range_min = $min;
            $mock_rule->range_max = $max;
            $find_flag = true;
        }
        //在指定的列表里随机
        $attr_enum = $this->attributeName('enum');
        if (!$find_flag && $node->hasAttribute($attr_enum)) {
            $enum_set = FFanStr::split($this->read($node, 'enum'), '|');
            if (empty($enum_set)) {
                $msg = $this->manager->fixErrorMsg($attr_enum . ' 属性填写出错');
                throw new DOPException($msg);
            }
            foreach ($enum_set as $i => $each_value) {
                $enum_set[$i] = self::fixValue($item_type, $each_value);
            }
            $mock_rule->enum_set = $enum_set;
            $mock_rule->enum_size = count($enum_set);
            $find_flag = true;
        }
        //指定类型
        $mock_type = $this->attributeName('type');
        if (!$find_flag && $node->hasAttribute($mock_type)) {
            $mock_rule->type = $this->read($node, '');
            $find_flag = true;
        }
        //固定值
        $attr_fix = $this->attributeName('');
        if (!$find_flag && $node->hasAttribute($attr_fix)) {
            $fixed_value = $this->read($node, '');
            $mock_rule->fixed_value = self::fixValue($item_type, $fixed_value);
            $find_flag = true;
        }
        if ($find_flag) {
            $item->addPluginData($this->name, $mock_rule);
        }
    }

    /**
     * 格式化值
     * @param int $item_type
     * @param string $value
     * @return float|int|string
     */
    private static function fixValue($item_type, $value)
    {
        switch ($item_type) {
            case ItemType::INT:
                return (int)$value;
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                return (float)$value;
                break;
            case ItemType::STRING:
                return '"' . $value . '"';
                break;
            //数组，值就是长度
            case ItemType::ARR:
                return (int)$value;
                break;
        }
        return '';
    }

    /**
     * 是否支持 目前支持 int, string, float, double,
     * list[int], list[string], list[float], list[double], list[struct]类型
     * @param Item $item
     * @return bool
     */
    public static function isSupport($item)
    {
        $type = $item->getType();
        if (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $sub_item = $item->getItem();
            //list Struct 也是可以mock的
            if (ItemType::STRUCT === $sub_item->getType()) {
                return true;
            }
            return self::isSupport($sub_item);
        }
        return ItemType::DOUBLE === $type &&
            ItemType::FLOAT === $type &&
            ItemType::STRING === $type &&
            ItemType::INT === $type;
    }
}
