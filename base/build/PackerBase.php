<?php

namespace FFan\Dop\Build;

use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\Struct;

/**
 * Class PackerBase 序列化和反序列化代码生成接口
 * @package FFan\Dop\Build
 */
abstract class PackerBase
{
    /**
     * pack方法
     */
    const METHOD_PACK = 1;

    /**
     * unpack方法
     */
    const METHOD_UNPACK = 2;

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
     * @var string
     */
    private $packer_name;

    /**
     * @var int 当前type
     */
    private $current_method;

    /**
     * @var int 代码side
     */
    private $code_side = 0;

    /**
     * @var PackerBase 主packer
     */
    private $main_packer;

    /**
     * @var bool 是否只生成 packer-extra 的协议
     */
    private $is_extra = false;

    /**
     * PackerBase constructor.
     * @param CoderBase $coder
     */
    public function __construct(CoderBase $coder)
    {
        $this->coder = $coder;
        $pack_name = basename(str_replace('\\', '/', static::class));
        $this->packer_name = strtolower(substr($pack_name, 0, -4));
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
     * 获取当前的方法
     * @return int
     */
    public function getCurrentMethod()
    {
        return $this->current_method;
    }

    /**
     * 设置当前的方法
     * @param int $method
     */
    public function setCurrentMethod($method)
    {
        if (self::METHOD_PACK !== $method) {
            $method = self::METHOD_UNPACK;
        }
        $this->current_method = $method;
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
     * 检查item的trigger
     * @param CodeBuf $code_buf
     * @param Item $item
     */
    public function itemTrigger(CodeBuf $code_buf, Item $item)
    {
        $triggers = $item->getTrigger();
        if (null === $triggers) {
            return;
        }
        /** @var Trigger $trigger */
        foreach ($triggers as $trigger) {
            $trigger->trigger($code_buf, $this->file_buf, $this);
        }
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

    /**
     * 获取pack名称
     * @return string
     */
    public function getName()
    {
        return $this->packer_name;
    }

    /**
     * 获取coder
     * @return CoderBase
     */
    public function getCoder()
    {
        return $this->coder;
    }

    /**
     * 设置code side
     * @param int $side
     */
    public function setCodeSide($side)
    {
        $side = (int)$side;
        $this->code_side |= $side;
    }

    /**
     * 获取code side
     * @return int
     */
    public function getCodeSide()
    {
        return $this->code_side;
    }

    /**
     * 设置主packer
     * @param PackerBase $packer
     */
    public function setMainPacker($packer)
    {
        $this->main_packer = $packer;
    }

    /**
     * 获取 主 packer
     * @return PackerBase
     */
    public function getMainPacker()
    {
        return $this->main_packer;
    }

    /**
     * 获取主 packer 的名称
     * @return string
     */
    public function getMainPackerName()
    {
        if ($this->main_packer) {
            return $this->main_packer->getMainPackerName();
        } else {
            return $this->getName();
        }
    }

    /**
     * 设置 is_extra 标志
     * @param bool $flag
     */
    public function setExtraFlag($flag)
    {
        $this->is_extra = (bool)$flag;
    }

    /**
     * @return bool
     */
    public function getExtraFlag()
    {
        return $this->is_extra;
    }
}
