<?php

namespace ffan\dop;

/**
 * Interface PackInterface 序列化和反序列化代码生成接口
 * @package ffan\dop
 */
interface PackInterface
{
    /**
     * 获取依赖的packer
     * @return null|array
     */
    public function getRequirePacker();

    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf);

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf);
}
