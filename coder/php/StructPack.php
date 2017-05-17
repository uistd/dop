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
        $buffer_file = $folder->touch('', 'BinaryBuffer.php');
        $namespace = $this->coder->joinNameSpace('');
        $this->coder->loadTpl($buffer_file, 'tpl/BinaryBuffer.tpl', array('namespace' => $namespace));
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
        $code_buf->pushStr(' * @param BinaryBuffer $buffer');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public static function binaryStruct(BinaryBuffer $buffer)');
        $code_buf->pushStr('{');
        $code_buf->indentIncrease();
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $code_buf->pushStr('$buffer->writeString(\'' . $name . '\');');
            $this->writeItemType($code_buf, $item);
        }
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
        $code_buf->pushStr('$buffer->writeUnsignedChar(0x' . dechex($bin_type) . ');');
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
                $code_buf->pushStr($class_name.'::binaryStruct($buffer);');
                break;
        }
    }
}