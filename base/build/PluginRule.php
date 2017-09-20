<?php

namespace FFan\Dop\Build;

use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\Protocol;

/**
 * Class PluginRule 插件规则
 * @package FFan\Dop\Build
 */
class PluginRule extends NodeBase
{

    /**
     * @var array 错误消息设置
     */
    protected static $error_msg;

    /**
     * @var string 继承设置
     */
    public $extend_item;

    /**
     * @var string 继承的类名
     */
    public $extend_class;

    /**
     * @var int 类型
     */
    protected $type;

    /**
     * 解析规则
     * @param Protocol $parser
     * @param \DOMElement $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {

    }

    /**
     * 获取类型
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 设置类型
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * 获取错误数据
     * @param $error_code
     * @return string
     */
    public function getErrorMsg($error_code)
    {
        return isset(static::$error_msg[$error_code]) ? static::$error_msg[$error_code] : 'Unknown error';
    }
}
