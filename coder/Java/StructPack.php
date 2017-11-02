<?php

namespace FFan\Dop\Coder\Java;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\PackerBase;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\ListItem;
use FFan\Dop\Protocol\MapItem;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\StructItem;

/**
 * Class StructPack
 * @package FFan\Dop\Coder\Java
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
        $import_str = $this->coder->joinNameSpace('', 'DopEncode');
        $this->pushImportCode('import ' . $import_str);
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 生成二进制协议头');
        $code_buf->pushStr(' */');
        $access_type = $struct->isSubStruct() ? 'public' : 'private';
        $code_buf->pushStr($access_type . ' static byte[] binaryStruct() {');
        $code_buf->indent();
        $fun_str = 'DopEncode protocol_encoder = new DopEncode();';
        $code_buf->pushStr($fun_str);
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $code_buf->pushStr('protocol_encoder.writeString("' . $name . '");');
            $this->writeItemType($code_buf, $item);
        }
        $code_buf->pushStr('return protocol_encoder.getBuffer();');
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
        $code_buf->pushStr('//' . $this->typeComment($bin_type));
        $code_buf->pushStr('protocol_encoder.writeByte((byte) 0x' . dechex($bin_type) . ');');
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
                $code_buf->pushStr('protocol_encoder.writeByteArray(' . $class_name . '.binaryStruct(), true);');
                break;
        }
    }
}
