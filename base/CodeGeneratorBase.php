<?php

namespace ffan\dop;

/**
 * Class LanCodeBase 各语言基类
 * @package ffan\dop
 */
abstract class CodeGeneratorBase implements GenerateInterface
{
    /**
     * @var DOPGenerator
     */
    protected $generator;

    /**
     * @var BuildOption
     */
    protected $build_opt;

    public function __construct(DOPGenerator $generator)
    {
        $this->generator = $generator;
        $this->build_opt = $generator->getBuildOption();
    }

    /**
     * 生成文件开始
     * @return CodeBuf|null
     */
    public function generateBegin()
    {
        return null;
    }

    /**
     * 生成文件结束
     * @return CodeBuf|null
     */
    public function generateFinish()
    {
        return null;
    }

    /**
     * 按类名生成代码
     * @param Struct $struct
     * @return CodeBuf|null
     */
    public function generateByClass($struct)
    {
        return null;
    }

    /**
     * 按协议文件生成代码
     * @param string $xml_file
     * @return CodeBuf|null
     */
    public function generateByXml($xml_file)
    {
        return null;
    }

    /**
     * 合并插件生成的方法 到 类文件
     * @param CodeBuf $code_buf
     * @param string $class_name
     */
    protected function mergePluginFunction($code_buf, $class_name)
    {
        $code_arr = $this->generator->getClassPluginCodeAll($class_name);

        /**
         * @var string $plugin_name
         * @var CodeBuf $plugin_code_buf
         */
        foreach ($code_arr as $plugin_name => $plugin_code_buf) {
            if (CodeBuf::BUF_TYPE_FUNCTION === $plugin_code_buf->getBufType()) {
                $code_buf->pushBuffer($plugin_code_buf);
            }
        }
    }
}
