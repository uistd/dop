<?php

namespace ffan\dop;

/**
 * Interface GenerateInterface 代码生成接口
 * @package ffan\dop
 */
interface GenerateInterface
{
    /**
     * GenerateInterface constructor.
     * @param DOPGenerator $generator
     */
    public function __construct(DOPGenerator $generator);

    /**
     * 生成文件开始
     * @return CodeBuf|null
     */
    public function generateBegin();

    /**
     * 生成文件结束
     * @return CodeBuf|null
     */
    public function generateFinish();

    /**
     * 按类名生成代码
     * @param Struct $struct
     * @return CodeBuf|null
     */
    public function generateByClass($struct);

    /**
     * 按协议文件生成代码
     * @param string $xml_file
     * @return CodeBuf|null
     */
    public function generateByXml($xml_file);
}