<?php

namespace ffan\dop\build;

use ffan\dop\protocol\Struct;

/**
 * Class PackerBase 序列化和反序列化代码生成接口
 * @package ffan\dop\build
 */
abstract class PackerBase
{
    const PACK_METHOD = 1;
    const UNPACK_METHOD = 2;
    
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
     * 生成通用代码（加载时）
     */
    public function onLoad()
    {

    }

    /**
     * 生成通用代码（调用pack方法时）
     * @param FileBuf $file_buf 文件
     * @param Struct $struct
     * @param int $type 类型
     */
    public function onPack(FileBuf $file_buf, Struct $struct, $type = self::PACK_METHOD)
    {
        
    }
    
    /**
     * 生成临时变量名
     * @param string $var
     * @param string $type
     * @return string
     */
    public static function varName($var, $type)
    {
        return $type . '_' . (string)$var;
    }
}
