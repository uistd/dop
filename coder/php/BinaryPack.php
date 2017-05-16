<?php
namespace ffan\dop\coder\php;
use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\protocol\Struct;

/**
 * Class BinaryPack
 * @package ffan\dop\coder\php
 */
class BinaryPack extends PackerBase
{

    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        
    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        
    }
}