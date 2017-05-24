<?php

namespace ffan\dop\coder\php;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\Exception;
use ffan\dop\protocol\IntItem;
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
        //如果是子 struct
        if ($struct->isSubStruct()) {
            $code_buf->pushStr(' * @param BinaryBuffer $result');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public function binaryPack($result)');
            $code_buf->pushStr('{');
            $code_buf->indentIncrease();
        } else {
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
        }
        
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
            $code_buf->pushStr('$result->writeChar('.$item->getBinaryType().');');
            self::packItemValue($code_buf, 'this->' . $name, $item, 0);
            $code_buf->indentDecrease()->pushStr('}');
        }
        if (!$struct->isSubStruct()) {
            $code_buf->pushStr('return $result->dump();');
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
            case ItemType::STRING:
                self::packItemCode($code_buf, $var_name, 'writeString');
                break;
            case ItemType::INT:
                /** @var IntItem $item */
                $func_name = self::getIntFuncName($item, 'write');
                self::packItemCode($code_buf, $var_name, $func_name);
                break;
            case ItemType::STRUCT:
                $code_buf->pushStr('$'. $var_name'->');
                break;
        }
    }

    /**
     * 获取int值的方法名
     * @param IntItem $item
     * @param string $type
     * @return string
     * @throws Exception
     */
    private static function getIntFuncName($item, $type = 'read')
    {
        $bin_type = $item->getBinaryType();
        $func_arr = array(
            0x12 => 'Char',
            0x92 => 'UnsignedChar',
            0x22 => 'Short',
            0xa2 => 'UnsignedShort',
            0x42 => 'int',
            0xc2 => 'UnsignedInt',
            0x82 => 'bigint',
            0xf2 => 'UnsignedBigint',
        );
        if (!isset($func_arr[$bin_type])) {
            throw new Exception('Error int type:'. $bin_type);
        }
        return $type . $func_arr[$bin_type];
    }
    
    /**
     * 生成打包一个节点的代码
     * @param CodeBuf $code_buf
     * @param string $var_name
     * @param string $func_name 方法名
     */
    private static function packItemCode($code_buf, $var_name, $func_name)
    {
        $code_buf->pushStr('$result->'. $func_name .'($' . $var_name . ');');
    }
}