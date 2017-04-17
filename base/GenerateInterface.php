<?php
namespace ffan\dop;

interface GenerateInterface
{
    /**
     * 生成文件开始
     * @param DOPGenerator $generator
     * @return void
     */
    public function generateBegin(DOPGenerator $generator);

    /**
     * 生成文件结束
     * @param DOPGenerator $generator
     * @return void
     */
    public function generateFinish(DOPGenerator $generator);

    /**
     * 按类名生成代码
     * @param DOPGenerator $generator
     * @param Struct $struct
     * @return string
     */
    public function generateByClass(DOPGenerator $generator, $struct);

    /**
     * 按协议文件生成代码
     * @param DOPGenerator $generator
     * @param string $xml_file
     * @return string
     */
    public function generateByXml(DOPGenerator $generator, $xml_file);
}