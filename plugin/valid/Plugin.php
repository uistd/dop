<?php

namespace ffan\dop\plugin\valid;

use ffan\dop\build\PluginBase;
use ffan\dop\build\PluginRule;
use ffan\dop\Exception;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\Protocol;
use ffan\dop\protocol\Struct;

/**
 * Class Plugin 数据有效性检验
 * @package ffan\dop\plugin\validator
 */
class Plugin extends PluginBase
{
    /**
     * @var string 属性名前缀
     */
    protected $attribute_name_prefix = 'v';

    /**
     * 初始化
     * @param \DOMElement $node
     * @param Item $item
     * @param Protocol $parser 解析器
     */
    public function init(Protocol $parser, \DOMElement $node, Item $item)
    {
        if (!$this->isSupport($item)) {
            return;
        }
        $valid_rule = new ValidRule();
        $valid_rule->is_require = PluginRule::readBool($node, 'require', false);
        $valid_rule->require_msg = PluginRule::read($node, 'require-msg');
        $valid_rule->range_msg = PluginRule::read($node, 'range-msg');
        $valid_rule->length_msg = PluginRule::read($node, 'length-msg');
        $valid_rule->format_msg = PluginRule::read($node, 'format-msg');
        $valid_rule->err_msg = PluginRule::read($node, 'msg');
        if (null === $valid_rule->err_msg) {
            $valid_rule->err_msg = 'Invalid `'. $item->getName() .'`';
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
        $item->addPluginData($this->plugin_name, $valid_rule);
    }

    /**
     * int 配置
     * @param \DOMElement $node
     * @param ValidRule $valid_rule
     */
    private function readIntSet($node, $valid_rule)
    {
        list($min, $max) = PluginRule::readSplitSet($node, 'range');
        if (null !== $min) {
            $valid_rule->min_value = $min;
        }
        if (null !== $max) {
            $valid_rule->max_value = $max;
        }
    }

    /**
     * float 配置
     * @param \DOMElement $node
     * @param ValidRule $valid_rule
     */
    private function readFloatSet($node, $valid_rule)
    {
        list($min, $max) = PluginRule::readSplitSet($node, 'range', false);
        if (null !== $min) {
            $valid_rule->min_value = $min;
        }
        if (null !== $max) {
            $valid_rule->max_value = $max;
        }
    }

    /**
     * 字符串配置
     * @param \DOMElement $node
     * @param ValidRule $valid_rule
     * @throws Exception
     */
    private function readStringSet($node, $valid_rule)
    {
        list($min_len, $max_len) = PluginRule::readSplitSet($node, 'length');
        if ($min_len) {
            $valid_rule->min_str_len = $min_len;
        }
        if ($max_len) {
            $valid_rule->max_str_len = $max_len;
        }
        //默认trim()
        $valid_rule->is_trim = PluginRule::readBool($node, 'trim', true);
        //默认转义危险字符
        $valid_rule->is_add_slashes = PluginRule::readBool($node, 'slashes', true);
        //默认过滤html标签
        $valid_rule->is_strip_tags = PluginRule::readBool($node, 'html-strip', true);
        //如果不过滤html标签，默认html-encode
        $valid_rule->is_html_special_chars = PluginRule::readBool($node, 'html-encode', true);
        //内容格式
        $format_set = PluginRule::read($node, 'format');
        if (!empty($format_set)) {
            $valid_rule->format_set = str_replace('#', '\#', $format_set);
            if ('/' !== $valid_rule->format_set[0] && !ValidRule::isBuildInType($valid_rule->format_set)) {
                throw new Exception('Unknown format set:'. $format_set);
            }
        }
        //调用其它函数
        $func_set = PluginRule::read($node, 'func');
        if (!empty($func_set)) {
            if (!preg_match('/^[a-zA-z_][a-zA-Z_\d:]?/', $func_set)) {
                throw new Exception('Unknown func set:'. $func_set);
            }
        }
        //长度计算方式
        $valid_rule->str_len_type = (int)PluginRule::read($node, 'strlen_type', 1);
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
        return $struct->hasReferType(Struct::TYPE_REQUEST);
    }
}
