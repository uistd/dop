<?php
namespace ffan\dop;

use ffan\php\utils\Str as FFanStr;

/**
 * Class ValidatorPlugin 数据有效性检验
 * @package ffan\dop
 */
class ValidatorPlugin
{
    /**
     * @var ProtocolManager
     */
    private $manager;

    /**
     * ValidatorPlugin constructor.
     * @param ProtocolManager $manager
     */
    public function __construct(ProtocolManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * 规则读取
     * @param \DOMElement $node
     * @param Item $item
     * @return ValidRule
     */
    private function readRule(\DOMElement $node, Item $item)
    {
        if (!$this->isSupport($item)) {
            return null;
        }
        $valid_rule = new ValidRule();
        $valid_rule->data_from = $this->readValidFrom($node);
        $valid_rule->is_require = $this->readRequireSet($node);
        $type = $item->getType();
        //如果是字符串
        if (ItemType::STRING === $type) {
            $this->readStringSet($node, $valid_rule);
        } elseif (ItemType::INT === $type) {
            $this->readIntSet($node, $valid_rule);
        } elseif (ItemType::FLOAT === $type) {
            $this->readFloatSet($node, $valid_rule);
        }
    }

    /**
     * int 配置
     * @param \DOMElement $node
     * @param ValidRule $valid_rule
     */
    private function readIntSet($node, $valid_rule){
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
     * 读取一个bool值
     * @param \DOMElement $node
     * @param string $name
     * @param bool $default 默认值
     * @return bool
     */
    private function readBool($node, $name, $default = false)
    {
        $set_str = $this->read($node, $name, $default);
        return (bool)$set_str;
    }

    /**
     * 字符串长度限制
     * @param \DOMElement $node
     * @param string $name
     * @param bool $is_int
     * @return array|null
     */
    private function readSplitSet($node, $name, $is_int = true)
    {
        $set_str = $this->read($node, $name);
        $min = null;
        $max = null;
        if (!empty($set_str)) {
            if (false === strpos($set_str, ',')) {
                $max = $set_str;
            } else {
                $tmp = explode(',', $set_str);
                $min = trim($tmp[0]);
                $max = trim($tmp[1]);
            }
            if ($is_int) {
                $min = (int)$min;
                $max = (int)$max;
            } else {
                $min = (float)$min;
                $max = (float)$max;
            }
            if ($max < $min) {
                $msg = $this->manager->fixErrorMsg('v-length:'. $set_str .' 无效');
                $this->manager->buildLogError($msg);
                $max = $min = null;
            }
        }
        return [$min, $max];
    }

    /**
     * 是否是必须的参数
     * @param \DOMElement $node
     * @return bool
     */
    private function readRequireSet($node)
    {
        $set_str = $this->read($node, 'require', false);
        return (bool)$set_str;
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
     * 读取一条规则
     * @param \DOMElement $node
     * @param string $name 规则名
     * @param null $default
     * @return mixed
     */
    private function read($node, $name, $default = null)
    {
        $attr_name = $this->attributeName($name);
        if (!$node->hasAttribute($attr_name)) {
            return $default;
        }
        return trim($node->getAttribute($attr_name));
    }

    /**
     * 属性名称
     * @param string $name 属性名
     * @return string
     */
    private function attributeName($name)
    {
        return 'v-' . $name;
    }
}