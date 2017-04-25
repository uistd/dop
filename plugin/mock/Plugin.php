<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginBase;
use ffan\dop\Exception;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\php\utils\Str as FFanStr;

/**
 * Class Plugin
 * @package ffan\dop\plugin\mock
 */
class Plugin extends PluginBase
{
    /**
     * @var string 属性前缀
     */
    protected $attribute_name_prefix = 'mock';

    /**
     * 初始化
     * @param \DOMElement $node
     * @param Item $item
     * @throws Exception
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
                throw new Exception($attr_enum . ' 属性填写出错');
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
            $mock_rule->build_in_type = FFanStr::camelName($this->read($node, 'type'), false);
            if (!self::isBuildInType($mock_rule->build_in_type)) {
                throw new Exception('Unknown build in mock type:' . $mock_rule->build_in_type);
            }
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
     * @param int $depth 递归深度
     * @return bool
     */
    public static function isSupport($item, $depth = 0)
    {
        $type = $item->getType();
        if (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $sub_item = $item->getItem();
            return self::isSupport($sub_item, $depth + 1);
        }
        if (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $value_item = $item->getValueItem();
            return self::isSupport($value_item, $depth + 1);
        }
        //最后一项, 如果是list 或者 map里边是 struct， 可以mock
        return ItemType::DOUBLE === $type ||
            ItemType::FLOAT === $type ||
            ItemType::STRING === $type ||
            ItemType::INT === $type ||
            ($depth > 0 && ItemType::STRUCT === $type);
    }

    /**
     * 是否是内置的类型
     * @param string $type_name
     * @return bool
     */
    private static function isBuildInType($type_name)
    {
        return in_array($type_name, array(
            'mobile',
            'chineseName',
            'email',
            'date',
            'dateTime'
        ));
    }
}
