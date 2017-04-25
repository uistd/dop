<?php

namespace ffan\dop\build;

use ffan\dop\Builder;

/**
 * Class PluginCoder
 * @package ffan\dop\build
 */
abstract class PluginHandlerBase
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var BuildOption
     */
    protected $build_opt;

    /**
     * @var PluginBase
     */
    protected $plugin;

    /**
     * GenerateInterface constructor.
     * @param Builder $builder
     * @param PluginBase $plugin
     */
    public function __construct(Builder $builder, PluginBase $plugin)
    {
        $this->builder = $builder;
        $this->build_opt = $builder->getBuildOption();
        $this->plugin = $plugin;
    }

    /**
     * 生成插件代码
     * @param Builder $builder
     */
    public function buildCode(Builder $builder)
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