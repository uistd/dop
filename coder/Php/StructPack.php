<?php

namespace UiStd\Dop\Coder\Php;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\PackerBase;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\ListItem;
use UiStd\Dop\Protocol\MapItem;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Protocol\StructItem;

/**
 * Class StructPack
 * @package UiStd\Dop\Coder\Php
 */
class StructPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;

    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        $this->pushImportCode('use UiStd\\DopLib\\DopEncode;');
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 生成二进制协议头');
        $code_buf->pushStr(' * @return String');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public static function binaryStruct()');
        $code_buf->pushStr('{');
        $code_buf->indent();
        $code_buf->pushStr('$byte_array = new DopEncode();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $name = $this->coder->fixOutputName($name, $item);
            $code_buf->pushStr('$byte_array->writeString(\'' . $name . '\');');
            $this->writeItemType($code_buf, $item);
        }
        $code_buf->pushStr('return $byte_array->dump();');
        $code_buf->backIndent()->pushStr('}');
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
        $code_buf->pushStr('$byte_array->writeChar(0x' . dechex($bin_type) . ');');
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
                $code_buf->pushStr('$byte_array->writeString('.$class_name.'::binaryStruct());');
                break;
        }
    }
}
