<?php

namespace ffan\dop\build;

use ffan\dop\protocol\ItemType;
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
     * @var FileBuf 当前正在编辑的文件
     */
    protected $file_buf;

    /**
     * @var CodeBuf 生成import的buf
     */
    private $import_buf;

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

    /**
     * 生成通用代码（加载时）
     */
    public function onLoad()
    {

    }

    /**
     * 生成通用代码（类代码）
     */
    public function build()
    {

    }

    /**
     * 生成通用代码（调用pack方法时）
     * @param FileBuf $file_buf 文件
     */
    public function setFileBuf(FileBuf $file_buf)
    {
        $this->file_buf = $file_buf;
        if (null !== $file_buf) {
            $this->import_buf = $file_buf->getBuf(FileBuf::IMPORT_BUF);
        } else {
            $this->import_buf = null;
        }
    }

    /**
     * 写入import代码
     * @param string $str
     */
    protected function pushImportCode($str)
    {
       if (!$this->import_buf) {
           return;
       }
       $this->import_buf->pushUniqueStr($str);
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

    /**
     * 注释
     * @param int $type
     * @return string
     */
    protected function typeComment($type)
    {
        static $comment_arr = array(
            ItemType::STRING => 'string',
            ItemType::BINARY => 'binary',
            ItemType::ARR => 'list',
            ItemType::MAP => 'map',
            ItemType::STRUCT => 'struct',
            ItemType::FLOAT => 'float',
            ItemType::DOUBLE => 'double',
            ItemType::BOOL => 'bool',
            0x12 => 'int8',
            0x92 => 'unsigned int8',
            0x22 => 'int16',
            0xa2 => 'unsigned int16',
            0x42 => 'int32',
            0xc2 => 'unsigned int32',
            0x82 => 'int64',
        );
        return isset($comment_arr[$type]) ? $comment_arr[$type] : '';
    }
}
