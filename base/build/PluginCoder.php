<?php

namespace ffan\dop\build;

use ffan\dop\Builder;
use ffan\dop\protocol\Plugin;
use ffan\dop\protocol\Struct;

/**
 * Class PluginCoder
 * @package ffan\dop\build
 */
abstract class PluginCoder
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
     * @var Plugin
     */
    protected $plugin;

    /**
     * GenerateInterface constructor.
     * @param Builder $builder
     * @param Plugin $plugin
     */
    public function __construct(Builder $builder, Plugin $plugin)
    {
        $this->builder = $builder;
        $this->build_opt = $builder->getBuildOption();
        $this->plugin = $plugin;
    }

    /**
     * 生成通用代码
     * @param FileBuf $file_buf
     */
    public function buildCommonCode(FileBuf $file_buf)
    {

    }

    /**
     * 生成struct的代码
     * @param Struct $struct
     * @param FileBuf $file_buf
     */
    public function buildStructCode($struct, FileBuf $file_buf)
    {

    }

    /**
     * 按命名空间生成代码
     * @param string $name_space
     * @param FileBuf $fileBuf
     */
    public function buildNsCode($name_space, FileBuf $fileBuf)
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
