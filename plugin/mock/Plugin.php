<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\PluginBase;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Protocol;

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
     * @param Protocol $parser 解析器
     */
    public function init(Protocol $parser, \DOMElement $node, Item $item)
    {
        if (!self::isSupport($item)) {
            return;
        }
        $mock_rule = null;
        if ($node->hasAttribute('range')) {
            $mock_rule = new RuleRange();
        } elseif ($node->hasAttribute('enum')) {
            $mock_rule = new RuleEnum();
        } elseif ($node->hasAttribute('value')) {
            $mock_rule = new RuleFixed();
        } elseif ($node->hasAttribute('type')) {
            $mock_rule = new RuleType();
        } elseif ($node->hasAttribute('begin')) {
            $mock_rule = new RuleIncrease();
        } elseif ($node->hasAttribute('pair')) {
            $mock_rule = new RulePair();
        }
        if (null !== $mock_rule) {
            $error_code = $mock_rule->init($parser, $node, $item);
            if (0 !== $error_code) {
                $this->manager->buildLogError($mock_rule->getErrorMsg($error_code));
            }
        }
        $item->addPluginData($this->plugin_name, $node, $mock_rule, $parser);
    }

    /**
     * 格式化值
     * @param int $item_type
     * @param string $value
     * @return float|int|string
     */
    public static function fixValue($item_type, $value)
    {
        switch ($item_type) {
            case ItemType::INT:
                return (int)$value;
                break;
            case ItemType::BOOL:
                return (bool)$value;
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
            ItemType::BOOL === $type ||
            ($depth > 0 && ItemType::STRUCT === $type);
    }
}
