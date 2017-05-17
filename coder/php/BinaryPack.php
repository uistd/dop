<?php

namespace ffan\dop\coder\php;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\Exception;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;

/**
 * Class BinaryPack
 * @package ffan\dop\coder\php
 */
class BinaryPack extends PackerBase
{
    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 二进制打包');
        $code_buf->pushStr(' * @param bool $pid 是否打包协议ID');
        $code_buf->pushStr(' * @param bool $mask 是否加密');
        $code_buf->pushStr(' * @param bool $sign 是否签名');
        $code_buf->pushStr(' * @return string');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public function binaryPack($pid = true, $mask = true, $sign = false)');
        $code_buf->pushStr('{');
        $code_buf->indentIncrease();
        $code_buf->pushStr('$result = new BinaryBuffer;');
        $code_buf->pushStr('if ($pid) {');
        $pid = $struct->getNamespace() . $struct->getClassName();
        $code_buf->pushIndent('$result->writeString(\'' . $pid . '\');');
        $code_buf->pushStr('}');
        //打包进去协议
        $code_buf->pushStr('self::binaryStruct($result);');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            //null值判断
            $code_buf->pushStr('if (null === $this->'.$name.') {');
            $code_buf->pushIndent('$result->writeChar(0);');
            $code_buf->pushStr('} else {')->indentIncrease();
            $code_buf->pushStr('$result->writeChar('.$item->getType().');');
            self::packItemValue($code_buf, 'this->' . $name, $item, 0);
            $code_buf->indentDecrease()->pushStr('}');
        }
        $code_buf->pushStr('return $result->dump();');
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
        // TODO: Implement buildUnpackMethod() method.
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $var_name 变量名
     * @param Item $item 节点对象
     * @param int $depth 深度
     * @throws Exception
     */
    private static function packItemValue($code_buf, $var_name, $item, $depth = 0)
    {
        $item_type = $item->getType();
        switch($item_type) {
            
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
        $code_buf->pushStr('$result->writeUnsignedChar(0x' . dechex($bin_type) . ');');
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
                $code_buf->pushStr('$result->writeBinary(' . $class_name . '::binaryStruct());');
                break;
        }
    }
}