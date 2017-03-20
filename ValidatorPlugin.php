<?php
namespace ffan\dop;

use ffan\php\utils\Str as FFanStr;
use ffan\php\tpl\Tpl;
use ffan\php\utils\Str;

/**
 * Class ValidatorPlugin 数据有效性检验
 * @package ffan\dop
 */
class ValidatorPlugin extends Plugin
{
    /**
     * @var string 属性名前缀
     */
    protected $attribute_name_prefix = 'v';

    /**
     * @var string
     */
    protected $name = 'validator';

    /**
     * 初始化
     * @param \DOMElement $node
     * @param Item $item
     * @return array
     */
    public function init(\DOMElement $node, Item $item)
    {
        if (!$this->isSupport($item)) {
            return null;
        }
        $valid_rule = new ValidRule();
        $valid_rule->data_from = $this->readValidFrom($node);
        $valid_rule->is_require = $this->readBool($node, 'require', false);
        $type = $item->getType();
        //如果是字符串
        if (ItemType::STRING === $type) {
            $this->readStringSet($node, $valid_rule);
        } elseif (ItemType::INT === $type) {
            $this->readIntSet($node, $valid_rule);
        } elseif (ItemType::FLOAT === $type) {
            $this->readFloatSet($node, $valid_rule);
        }
        return get_object_vars($valid_rule);
    }

    /**
     * int 配置
     * @param \DOMElement $node
     * @param ValidRule $valid_rule
     */
    private function readIntSet($node, $valid_rule)
    {
        list($min, $max) = $this->readSplitSet($node, 'range');
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
        list($min, $max) = $this->readSplitSet($node, 'range', false);
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
     */
    private function readStringSet($node, $valid_rule)
    {
        list($min_len, $max_len) = $this->readSplitSet($node, 'length');
        if ($min_len) {
            $valid_rule->min_str_len = $min_len;
        }
        if ($max_len) {
            $valid_rule->max_str_len = $max_len;
        }
        //默认trim()
        $valid_rule->is_trim = $this->readBool($node, 'trim', true);
        //默认转义危险字符
        $valid_rule->is_add_slashes = $this->readBool($node, 'slashes', true);
        //默认过滤html标签
        $valid_rule->is_strip_tags = $this->readBool($node, 'html-strip', true);
        //如果不过滤html标签，默认html-encode
        $valid_rule->is_html_special_chars = $this->readBool($node, 'html-encode', true);
        //内容正则
        $preg_set = $this->read($node, 'preg');
        if (!empty($preg_set)) {
            $valid_rule->preg_set = str_replace('#', '\#', $preg_set);
        }
    }

    /**
     * 读取允许的from值
     * @param \DOMElement $node
     * @return int
     */
    private function readValidFrom($node)
    {
        $from = $this->read($node, 'from');
        static $set_arr = array(
            'get' => ValidRule::FROM_HTTP_URI,
            'post' => ValidRule::FROM_HTTP_BODY,
            'uri' => ValidRule::FROM_HTTP_URI,
            'body' => ValidRule::FROM_HTTP_BODY
        );
        $from_set = 0;
        if (!empty($from)) {
            $from_arr = FFanStr::split(strtolower($from));
            foreach ($from_arr as $each_item) {
                if (!isset($set_arr[$each_item])) {
                    $msg = $this->manager->fixErrorMsg('无法识别的 v-from 设置:' . $each_item);
                    $this->manager->buildLogError($msg);
                }
                $from_set |= $set_arr[$each_item];
            }
        }
        if (0 === $from_set) {
            $from_set = ValidRule::FROM_HTTP_URI | ValidRule::FROM_HTTP_BODY;
        }
        return $from_set;
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
            return $this->isSupport($item->getItem());
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
        $type = $struct->getType();
        //如果这是一个struct，没有被request引用，不需要生成相关代码
        if (Struct::TYPE_STRUCT === $type && !$struct->hasReferType(Struct::TYPE_REQUEST)) {
            return '';
        }//如果不是请求的协议
        elseif (Struct::TYPE_REQUEST !== $type) {
            return '';
        }
        //如果模板文件不存在
        if (!$this->hasPluginTpl()) {
            return '';
        }
        $tpl_name = $this->getPluginTplName();
        return Tpl::get($tpl_name, $struct);
    }
}