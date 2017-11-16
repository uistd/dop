<?php

namespace FFan\Dop\Coder\Js;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\FileBuf;
use FFan\Dop\Build\PackerBase;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\ListItem;
use FFan\Dop\Protocol\MapItem;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\StructItem;

/**
 * Class StructPack
 * @package FFan\Dop\Coder\Js
 */
class StructPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;
    
    /**
     * 生成通用代码（加载时）
     */
    public function onLoad()
    {
        $folder = $this->coder->getFolder();
        $dop_encode = $folder->touch('', 'DopEncode.js');
        $dop_decode = $folder->touch('', 'DopDecode.js');
        $this->coder->loadTpl($dop_encode, 'tpl/DopEncode.js');
        $this->coder->loadTpl($dop_decode, 'tpl/DopDecode.js');
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
        $code_buf->pushStr(' * @return Uint8Array');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('binaryStruct: function(){');
        $code_buf->indent();
        $code_buf->pushStr('var byte_array = new DopEncode();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $code_buf->pushStr('byte_array.writeString(\'' . $name . '\');');
            $this->writeItemType($code_buf, $item);
        }
        $code_buf->pushStr('return byte_array.dumpUint8Array();');
        $code_buf->backIndent()->pushStr('},');
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
     * 将use FFan\Dop\BinaryBuffer写入
     * @param Struct $struct
     */
    private function buildUseCode($struct)
    {
        $class_file = $this->coder->getClassFileBuf($struct);
        $use_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        if ($use_buf) {
            $use_buf->pushUniqueStr('var DopEncode = require("' . $this->coder->relativePath('/', $struct->getNamespace()) . 'DopEncode");');
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
        $code_buf->pushStr('byte_array.writeChar(0x' . dechex($bin_type) . ');');
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
                $code_buf->pushStr('byte_array.writeUint8Array('.$class_name.'.prototype.binaryStruct(), true);');
                break;
        }
    }
}
