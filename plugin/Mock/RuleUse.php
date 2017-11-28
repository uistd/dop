<?php

namespace FFan\Dop\Plugin\Mock;

use FFan\Dop\Build\PluginRule;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Schema\Plugin as SchemaPlugin;
use FFan\Dop\Schema\Protocol;

/**
 * @package FFan\Dop
 */
class RuleUse extends PluginRule
{
    /**
     * @var array
     */
    protected static $error_msg = array(
        1 => '未找到配对的字段设置'
    );
    /**
     * @var int 类型
     */
    protected $type = MockType::MOCK_USE;

    /**
     * @var string 使用的字段名
     */
    public $use_item;

    /**
     * @var string 使用的类名
     */
    public $use_class;

    /**
     * 解析规则
     * @param Protocol $parser
     * @param SchemaPlugin $node
     * @param Item $item
     * @return int error_code
     */
    function init(Protocol $parser, $node, $item)
    {
        $use_str = $node->get('use');
        if (empty($use_str)) {
            return 1;
        }
        $this->use_item = $parser->fixItemName(basename($use_str));
        $this->use_class = $use_str;
        return 0;
    }
}
