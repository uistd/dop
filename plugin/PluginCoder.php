<?php
namespace ffan\dop\plugin;

use ffan\dop\BuildOption;
use ffan\dop\CodeBuf;
use ffan\dop\CoderInterface;
use ffan\dop\DOPGenerator;
use ffan\dop\Struct;

/**
 * Class PluginCoder
 * @package ffan\dop\plugin
 */
abstract class PluginCoder implements CoderInterface
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
     * 生成文件开始
     * @return CodeBuf|null
     */
    public function codeBegin()
    {
        return null;
    }

    /**
     * 生成文件结束
     * @return CodeBuf|null
     */
    public function codeFinish()
    {
        return null;
    }

    /**
     * 按类名生成代码
     * @param Struct $struct
     * @return CodeBuf|null
     */
    public function codeByClass($struct)
    {
        return null;
    }

    /**
     * 按协议文件生成代码
     * @param string $xml_file
     * @return CodeBuf|null
     */
    public function codeByXml($xml_file)
    {
        return null;
    }
}
