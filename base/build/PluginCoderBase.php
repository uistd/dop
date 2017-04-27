<?php

namespace ffan\dop\build;
use ffan\dop\Exception;

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
}
