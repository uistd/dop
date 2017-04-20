<?php
namespace ffan\dop;

/**
 * Class PluginCoder
 * @package ffan\dop\plugin
 */
abstract class PluginCoder
{
    /**
     * @var DOPGenerator
     */
    protected $generator;
    
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
     * @param DOPGenerator $generator
     * @param Plugin $plugin
     */
    public function __construct(DOPGenerator $generator, Plugin $plugin)
    {
        $this->generator = $generator;
        $this->build_opt = $generator->getBuildOption();
        $this->plugin = $plugin;
    }

    /**
     * 生成通用代码
     */
    public function codeCommon()
    {
        
    }

    /**
     * 插件代码是一个方法
     * @param Struct $struct
     * @return null|CodeBuf
     */
    public function codeMethod($struct)
    {
        return null;
    }

    /**
     * 插件代码是一个文件
     * @param string $file_name
     */
    public function codeAsFile($file_name)
    {
        
    }
}
