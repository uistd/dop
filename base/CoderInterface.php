<?php

namespace ffan\dop;

/**
 * Interface CoderInterface 代码生成接口
 * @package ffan\dop
 */
interface CoderInterface
{
    /**
     * 生成文件开始
     * @return CodeBuf|null
     */
    public function codeBegin();

    /**
     * 生成文件结束
     * @return CodeBuf|null
     */
    public function codeFinish();

    /**
     * 按类名生成代码
     * @param Struct $struct
     * @return CodeBuf|null
     */
    public function codeByClass($struct);

    /**
     * 按协议文件生成代码
     * @param string $xml_file
     * @return CodeBuf|null
     */
    public function codeByXml($xml_file);
}