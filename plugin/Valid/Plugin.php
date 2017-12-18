<?php

namespace UiStd\Dop\Plugin\Valid;

use UiStd\Dop\Build\PackerBase;
use UiStd\Dop\Build\PluginBase;
use UiStd\Dop\Exception;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Schema\Protocol;
use UiStd\Dop\Schema\Plugin as SchemaPlugin;
use UiStd\Common\Str;

/**
 * Class Plugin 数据有效性检验
 * @package UiStd\Dop\Plugin\Validator
 */
class Plugin extends PluginBase
{
    /**
     * @var string 属性名前缀
     */
    protected $attribute_name_prefix = 'v';

    /**
     * 初始化
     * @param Protocol $parser 解析器
     * @param SchemaPlugin $node
     * @param Item $item
     */
    public function init(Protocol $parser, SchemaPlugin $node, Item $item)
    {
        if (!$this->isSupport($item)) {
            return;
        }
        $valid_rule = new ValidRule();
        $valid_rule->is_require = $node->getBool('require', false);
        $valid_rule->require_msg = $node->get('require-msg');
        $valid_rule->range_msg = $node->get('range-msg');
        $valid_rule->length_msg = $node->get('length-msg');
        $valid_rule->format_msg = $node->get('format-msg');
        $valid_rule->err_msg = $node->get('msg');
        if (null === $valid_rule->err_msg) {
            $valid_rule->err_msg = 'Invalid `' . $item->getName() . '`';
        }
        $type = $item->getType();
        //如果是字符串
        if (ItemType::STRING === $type) {
            $this->readStringSet($node, $valid_rule);
        } elseif (ItemType::INT === $type) {
            $this->readIntSet($node, $valid_rule);
        } elseif (ItemType::FLOAT === $type) {
            $this->readFloatSet($node, $valid_rule);
        } //如果是数组，可以检查长度
        elseif (ItemType::ARR === $type || ItemType::MAP === $item) {
            $this->readIntSet($node, $valid_rule);
        }
        //指定集合
        if ($node->hasAttribute('set')) {
            $this->readSet($node->get('set'), $type, $valid_rule);
        }
        $item->addPluginData($this->plugin_name, $node, $valid_rule, $parser);
    }

    /**
     * 指定集合
     * @param string $value
     * @param int $type
     * @param ValidRule $valid_rule
     */
    private function readSet($value, $type, $valid_rule)
    {
        if (ItemType::STRING !== $type && ItemType::INT !== $type) {
            return;
        }
        $enum_arr = Str::split($value, ',');
        if (ItemType::INT === $type) {
            foreach ($enum_arr as &$item) {
                $item = (int)$item;
            }
        } else {
            foreach ($enum_arr as &$item) {
                $item = "'" . addcslashes($item, "'") . "'";
            }
        }
        $valid_rule->sets = $enum_arr;
    }

    /**
     * int 配置
     * @param SchemaPlugin $node
     * @param ValidRule $valid_rule
     */
    private function readIntSet($node, $valid_rule)
    {
        list($min, $max) = $node->getSplitSet('range');
        if (null !== $min) {
            $valid_rule->min_value = $min;
        }
        if (null !== $max) {
            $valid_rule->max_value = $max;
        }
    }

    /**
     * float 配置
     * @param SchemaPlugin $node
     * @param ValidRule $valid_rule
     */
    private function readFloatSet($node, $valid_rule)
    {
        list($min, $max) = $node->getSplitSet('range', false);
        if (null !== $min) {
            $valid_rule->min_value = $min;
        }
        if (null !== $max) {
            $valid_rule->max_value = $max;
        }
    }

    /**
     * 字符串配置
     * @param SchemaPlugin $node
     * @param ValidRule $valid_rule
     * @throws Exception
     */
    private function readStringSet($node, $valid_rule)
    {
        list($min_len, $max_len) = $node->getSplitSet('length');
        if ($min_len) {
            $valid_rule->min_str_len = $min_len;
        }
        if ($max_len) {
            $valid_rule->max_str_len = $max_len;
        }
        //默认trim()
        $valid_rule->is_trim = $node->getBool('trim', true);
        //默认转义危险字符
        $valid_rule->is_add_slashes = $node->getBool('slashes', false);
        //默认过滤html标签
        $valid_rule->is_strip_tags = $node->getBool('html-strip', true);
        //如果不过滤html标签，默认html-encode
        $valid_rule->is_html_special_chars = $node->getBool('html-encode', true);
        //内容格式
        $format_set = $node->get('format');
        if (!empty($format_set)) {
            $valid_rule->format_set = str_replace('#', '\#', $format_set);
            if ('/' !== $valid_rule->format_set[0] && !ValidRule::isBuildInType($valid_rule->format_set)) {
                throw new Exception('Unknown format set:' . $format_set);
            }
        }
        //长度计算方式
        $valid_rule->str_len_type = $node->getInt('strlen_type', 3);
    }

    /**
     * 是否支持 二进制不支持
     * @param Item $item
     * @return bool
     */
    private function isSupport($item)
    {
        return ItemType::BINARY !== $item->getType();
    }

    /**
     * 生成生成代码
     * @param Struct $struct
     * @return bool
     */
    public function isBuildCode($struct)
    {
        $type = $struct->getType();
        //模拟一个pack的方法
        $pack_name = 'plugin:valid';
        if (Struct::TYPE_REQUEST === $type) {
            $struct->addPackerMethod($pack_name, PackerBase::METHOD_PACK);
            return true;
        } else {
            return $struct->hasPackerMethod($pack_name, PackerBase::METHOD_PACK);
        }
    }
}
