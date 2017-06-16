<?php

namespace ffan\dop\coder\php;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\FileBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;

class StructPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;
    
    /**
     * 通用代码
     */
    public function buildCommonCode()
    {
        $folder = $this->coder->getFolder();
        $dop_encode = $folder->touch('', 'DopEncode.php');
        $namespace = $this->coder->joinNameSpace('');
        $tpl_data = array('namespace' => $namespace);
        $this->coder->loadTpl($dop_encode, 'tpl/DopEncode.tpl', $tpl_data);
    }

    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        $this->buildUseCode($struct);
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 生成二进制协议头');
        $code_buf->pushStr(' * @return DopEncode');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public static function binaryStruct()');
        $code_buf->pushStr('{');
        $code_buf->indentIncrease();
        $code_buf->pushStr('$buffer = new DopEncode();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $code_buf->pushStr('$buffer->writeString(\'' . $name . '\');');
            $this->writeItemType($code_buf, $item);
        }
        $code_buf->pushStr('$buffer->writeLengthAtBegin($buffer->getLength());');
        $code_buf->pushStr('return $buffer;');
        $code_buf->indentDecrease()->pushStr('}');
    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        $this->buildUseCode($struct);
    }

    /**
     * 将use ffan\dop\BinaryBuffer写入
     * @param Struct $struct
     */
    private function buildUseCode($struct)
    {
        $class_file = $this->coder->getClassFileBuf($struct);
        $use_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        if ($use_buf) {
            $use_buf->pushLockStr('use '. $this->coder->joinNameSpace('', 'DopEncode') .';');
            $use_buf->pushLockStr('use '. $this->coder->joinNameSpace('', 'BinaryBuffer') .';');
        }
    }

    /**
     * 写入类型
     * @param CodeBuf $code_buf
     * @param Item $item
     */
    private function writeItemType($code_buf, $item)
    {
        $bin_type = $item->getBinaryType();
        $code_buf->pushStr('//'. $this->typeComment($bin_type));
        $code_buf->pushStr('$buffer->writeChar(0x' . dechex($bin_type) . ');');
        $type = $item->getType();
        switch ($type) {
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $this->writeItemType($code_buf, $sub_item);
                break;
            case ItemType::MAP:
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $this->writeItemType($code_buf, $key_item);
                $this->writeItemType($code_buf, $value_item);
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $class_name = $item->getStructName();
                $code_buf->pushStr('$buffer->joinBuffer('.$class_name.'::binaryStruct());');
                break;
        }
    }

    /**
     * 注释
     * @param int $type
     * @return string
     */
    private function typeComment($type)
    {
        static $comment_arr = array(
            ItemType::STRING => 'string',
            ItemType::BINARY => 'binary',
            ItemType::ARR => 'list',
            ItemType::MAP => 'map',
            ItemType::STRUCT => 'struct',
            ItemType::FLOAT => 'float',
            ItemType::DOUBLE => 'double',
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