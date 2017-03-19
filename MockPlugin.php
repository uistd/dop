<?php
namespace ffan\dop;

use ffan\php\tpl\Tpl;
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
     * @return array
     * @throws DOPException
     */
    public function init(\DOMElement $node, Item $item)
    {
        if (!$this->isSupport($item)) {
            return null;
        }
        $mock_rule = new MockRule();
        $find_flag = false;
        $attr_range = $this->attributeName('range');
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
            $enum_set = FFanStr::split($this->read($node, 'enum'));
            if (empty($enum_set)) {
                $msg = $this->manager->fixErrorMsg($attr_enum . ' 属性填写出错');
                throw new DOPException($msg);
            }
            $mock_rule->enum_set = $enum_set;
            $mock_rule->enum_size = count($enum_set);
            $find_flag = true;
        }
        //固定值
        $attr_fix = $this->attributeName('');
        if (!$find_flag && $node->hasAttribute($attr_fix)) {
            $mock_rule->fixed_value = $this->read($node, '');
            $find_flag = true;
        }
        if (!$find_flag) {
            return null;
        }
        return get_object_vars($mock_rule);
    }

    /**
     * 是否支持 目前支持 int, string, float, list[int], list[string], list[float]类型
     * @param Item $item
     * @return bool
     */
    private function isSupport($item)
    {
        $type = $item->getType();
        if (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $sub_item = $item->getItem();
            if (ItemType::STRUCT === $sub_item->getType()) {
                return true;
            }
            return $this->isSupport($sub_item);
        }
        return ItemType::FLOAT === $type && ItemType::STRING === $type && ItemType::INT;
    }

    /**
     * 生成代码
     * @param Struct $struct
     * @return string
     */
    public function generateCode(Struct $struct)
    {
        $tpl_type = $this->manager->getBuildTplType();
        $tpl = $tpl_type . '/plugin_' . $this->name;
        if (!Tpl::hasTpl($tpl)) {
            return '';
        }
        return Tpl::get($tpl, $struct);
    }
}
