<?php

namespace ffan\dop\build;

/**
 * Class PluginCoder
 * @package ffan\dop\build
 */
abstract class PluginHandlerBase
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
     * @param CoderBase $coder
     * @param PluginBase $plugin
     */
    public function __construct(CoderBase $coder, PluginBase $plugin)
    {
        $this->coder = $coder;
        $this->plugin = $plugin;
    }

    /**
     * 生成插件代码
     * @param CoderBase $coder
     */
    public function buildCode(CoderBase $coder)
    {

    }

    /**
     * 获取插件名称
     * @return string
     */
    public function getName()
    {
        return $this->plugin->getName();
    }
}
