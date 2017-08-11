<?php

namespace ffan\dop\build;

/**
 * Class PluginRule 插件规则
 * @package ffan\dop\build
 */
abstract class PluginRule {
    /**
     * 解析规则
     * @param \DOMElement $node
     * @param int $item_type
     */
    abstract function init($node, $item_type = 0);

    /**
     * 获取类型
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 长度范围限制
     * @param \DOMElement $node
     * @param string $name
     * @param bool $is_int
     * @return array|null
     */
    protected function readSplitSet($node, $name, $is_int = true)
    {
        $set_str = $this->read($node, $name);
        $min = null;
        $max = null;
        if (0 === strlen($set_str)) {
            return [$min, $max];
        }
        if (false === strpos($set_str, ',')) {
            $max = ($is_int) ? (int)$set_str : (float)$set_str;
        } else {
            $tmp = explode(',', $set_str);
            $min_str = trim($tmp[0]);
            $max_str = trim($tmp[1]);
            if ($is_int) {
                if (strlen($min_str) > 0) {
                    $min = (int)$min_str;
                }
                if (strlen($max_str) > 0) {
                    $max = (int)$max_str;
                }
            } else {
                if (strlen($min_str)) {
                    $min = (float)$min_str;
                }
                if (strlen($max_str) > 0) {
                    $max = (float)$max_str;
                }
            }
        }
        if ($min !== null && $max !== null && $max < $min) {
            $this->manager->buildLogError('v-length:' . $set_str . ' 无效');
            $max = $min = null;
        }
        return [$min, $max];
    }

    /**
     * 读取一个bool值
     * @param \DOMElement $node
     * @param string $name
     * @param bool $default 默认值
     * @return bool
     */
    protected function readBool($node, $name, $default = false)
    {
        $set_str = $this->read($node, $name, $default);
        return (bool)$set_str;
    }

    /**
     * 读取插件属性
     * @param \DOMElement $node
     * @param string $attr_name 规则名
     * @param null $default
     * @return mixed
     */
    protected function read($node, $attr_name, $default = null)
    {
        if (!$node->hasAttribute($attr_name)) {
            return $default;
        }
        return trim($node->getAttribute($attr_name));
    }
}