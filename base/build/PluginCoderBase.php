<?php

namespace ffan\dop\build;

use ffan\dop\Exception;

use ffan\php\utils\str as FFanStr;

/**
 * Class PluginCoderBase
 * @package ffan\dop\build
 */
abstract class PluginCoderBase
{
    /**
     * @var CoderBase
     */
    protected $coder;

    /**
     * @var PluginBase
     */
    protected $plugin;

    /**
     * GenerateInterface constructor.
     * @param PluginBase $plugin
     * @throws Exception
     */
    public function __construct(PluginBase $plugin)
    {
        $this->coder = $plugin->getManager()->getCurrentCoder();
        if (null === $this->coder) {
            throw new Exception('Can not instance new PluginCoderBase');
        }
        $this->plugin = $plugin;
    }

    /**
     * 生成插件代码
     */
    public function buildCode()
    {

    }

    /**
     * 获取继承于其它的规则
     * @param PluginRule $rule
     * @param string $plugin_name
     */
    public function getExtendRule(PluginRule $rule, $plugin_name)
    {
        if (null === $rule->extend_item || null === $rule->extend_class) {
            return;
        }
        $manager = $this->coder->getManager();
        $struct =$manager->getStruct($rule->extend_class);
        if (null === $struct) {
            return;
        }
        $item = $struct->getItem($rule->extend_item);
        if (null === $item) {
            return;
        }
        /** @var PluginRule $extend_rule */
        $extend_rule = $item->getPluginData($plugin_name);
        $rule->extend_item = $rule->extend_class = null;
    }
}
