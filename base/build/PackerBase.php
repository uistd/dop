<?php

namespace ffan\dop\build;

use ffan\dop\protocol\Struct;

/**
 * Class PackerBase 序列化和反序列化代码生成接口
 * @package ffan\dop\build
 */
abstract class PackerBase
{
    /**
     * @var CoderBase
     */
    protected $coder;

    /**
     * PackerBase constructor.
     * @param CoderBase $coder
     */
    public function __construct(CoderBase $coder)
    {
        $this->coder = $coder;
    }

    /**
     * 获取依赖的packer
     * @return null|array
     */
    public function getRequirePacker()
    {
        return null;
    }
    
    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    abstract public function buildPackMethod($struct, $code_buf);

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    abstract public function buildUnpackMethod($struct, $code_buf);

    /**
     * 生成通用代码
     */
    public function buildCommonCode()
    {
        
    }
}
