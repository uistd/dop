<?php

namespace UiStd\Dop\Coder\Js;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\FileBuf;
use UiStd\Dop\Build\PackerBase;
use UiStd\Dop\Exception;
use UiStd\Dop\Protocol\IntItem;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\ListItem;
use UiStd\Dop\Protocol\MapItem;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Protocol\StructItem;

/**
 * Class BinaryPack
 * @package UiStd\Dop\Coder\Js
 */
class BinaryPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;

    /**
     * 获取依赖的packer
     * @return null|array
     */
    public function getRequirePacker()
    {
        //依赖 struct 打包 和 数组 解包方法 
        return array('struct', 'array');
    }

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
            $code_buf->pushStr(' * @param {DopEncode} result');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('binaryPack: function(result) {');
            $code_buf->indent();
        } else {
            $code_buf->pushStr(' * @param {boolean} pid 是否打包协议ID');
            $code_buf->pushStr(' * @param {boolean} sign 是否签名');
            $code_buf->pushStr(' * @param {null|string} mask_key 加密字符');
            $code_buf->pushStr(' * @return Uint8Array');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('binaryEncode: function(pid, sign, mask_key) {');
            $code_buf->indent();
            $pid = $struct->getNamespace() . $struct->getClassName();
            $code_buf->pushStr('var result = new DopEncode();');
            $code_buf->pushStr('if (pid) {');
            $code_buf->pushIndent('result.writePid(\'' . $pid . '\');');
            $code_buf->pushStr('}');
            $code_buf->pushStr('if (sign) {');
            $code_buf->pushIndent('result.sign();');
            $code_buf->pushStr('}');
            $code_buf->pushStr('if ("string" === typeof mask_key && mask_key.length > 0) {');
            $code_buf->pushIndent('result.mask(mask_key);');
            $code_buf->pushStr('}');
            //打包进去协议
            $code_buf->pushStr('result.writeUint8Array(this.binaryStruct(), true);');
        }
        $all_item = $struct->getAllExtendItem();
        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $item_type = $item->getType();
            //如果是以下几种类型，特殊判断
            if (ItemType::ARR === $item_type || ItemType::STRUCT === $item_type || ItemType::MAP === $item_type) {
                //struct
                if (ItemType::STRUCT === $item_type) {
                    /** @var $item StructItem */
                    $sub_struct = $item->getStruct();
                    $code_buf->pushStr('if (!(this.' . $name . ' instanceof ' . $sub_struct->getClassName() . ')) {');
                    //写入 0 表示空struct
                    $code_buf->pushIndent('result.writeChar(0);');
                } else {
                    $func_name = ItemType::MAP === $item_type ? 'isObject' : 'isArray';
                    $code_buf->pushStr('if (!DopBase.' . $func_name . '(this.' . $name . ')){');
                    //写入 0，表示数组长度 0
                    $code_buf->pushIndent('result.writeChar(0);');
                }
                $code_buf->pushStr('} else {')->indent();
                //struct 之前，要先写入一个0xff，表示非空 struct
                if (ItemType::STRUCT === $item_type) {
                    $code_buf->pushStr('result.writeChar(0xff);');
                }
                self::packItemValue($code_buf, 'this.' . $name, 'result', $item, 0, $tmp_index);
                $code_buf->backIndent()->pushStr('}');
            } else {
                self::packItemValue($code_buf, 'this.' . $name, 'result', $item, 0, $tmp_index);
            }
            $this->itemTrigger($code_buf, $item);
        }
        if (!$struct->isSubStruct()) {
            $code_buf->pushStr('return result.pack();');
        }
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
        //只有主协议 才会有这个方法
        if ($struct->isSubStruct()) {
            return;
        }
        $class_file = $this->coder->getClassFileBuf($struct);
        $import_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        if ($import_buf) {
            $import_buf->pushUniqueStr('var DopDecode = require("' . $this->coder->relativePath('/', $struct->getNamespace()) . 'DopDecode");');
        }
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 二进制解包');
        $code_buf->pushStr(' * @param {DopDecode|string|Uint8Array} data');
        $code_buf->pushStr(' * @param {string|null} mask_key');
        $code_buf->pushStr(' * @return {boolean}');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('binaryDecode: function(data, mask_key) {');
        $code_buf->indent();
        $code_buf->pushStr('mask_key = mask_key || null;');
        $code_buf->pushStr('var decoder = (data instanceof DopDecode) ? data : new DopDecode(data);');
        $code_buf->pushStr('var data_arr = decoder.unpack(mask_key);');
        $code_buf->pushStr('if (decoder.getErrorCode()) {');
        $code_buf->pushIndent('return false;');
        $code_buf->pushStr('}');
        $code_buf->pushStr('this.arrayUnpack(data_arr);');
        $code_buf->pushStr('return true;');
        $code_buf->backIndent()->pushStr('},');
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $var_name 变量名
     * @param string $result_name 结果数组
     * @param Item $item 节点对象
     * @param int $depth 深度
     * @param int $tmp_index
     */
    private static function packItemValue($code_buf, $var_name, $result_name, $item, $depth = 0, &$tmp_index = 0)
    {
        $item_type = $item->getType();
        if ($depth > 0) {
            self::typeCheckCode($code_buf, $var_name, $item);
        }
        switch ($item_type) {
            case ItemType::BINARY:
            case ItemType::STRING:
                self::packItemCode($code_buf, $var_name, $result_name, 'writeString');
                break;
            case ItemType::FLOAT:
                self::packItemCode($code_buf, $var_name, $result_name, 'writeFloat');
                break;
            case ItemType::BOOL:
                self::packItemCode($code_buf, $var_name, $result_name, 'writeChar');
                break;
            case ItemType::DOUBLE:
                self::packItemCode($code_buf, $var_name, $result_name, 'writeDouble');
                break;
            case ItemType::INT:
                /** @var IntItem $item */
                $func_name = self::getIntWriteFuncName($item);
                self::packItemCode($code_buf, $var_name, $result_name, $func_name);
                break;
            case ItemType::STRUCT:
                $code_buf->pushStr($var_name . '.binaryPack(' . $result_name . ');');
                break;
            case ItemType::ARR:
                //临时buffer
                $buffer_name = self::varName($tmp_index++, 'arr_buf');
                //长度变量
                $len_var_name = self::varName($tmp_index++, 'len');
                //循环变量
                $for_var_name = self::varName($tmp_index++, 'item');
                $for_index_name = self::varName($tmp_index++, 'i');
                $code_buf->pushStr('var ' . $len_var_name . ' = 0;');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                //写入list的类型
                $code_buf->pushStr('var ' . $buffer_name . ' = new DopEncode();');
                $code_buf->pushStr('for (var ' . $for_index_name . ' = 0; ' . $for_index_name . ' < ' . $var_name . '.length; ++' . $for_index_name . ') {');
                $code_buf->indent();
                $code_buf->pushStr('var ' . $for_var_name . ' = ' . $var_name . '[' . $for_index_name . '];');
                self::packItemValue($code_buf, $for_var_name, $buffer_name, $sub_item, $depth + 1, $tmp_index);
                $code_buf->pushStr('++' . $len_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr($result_name . '.writeLength(' . $len_var_name . ');');
                $code_buf->pushStr($result_name . '.joinBuffer(' . $buffer_name . ');');
                break;
            case ItemType::MAP:
                //临时buffer
                $buffer_name = self::varName($tmp_index++, 'map_buf');
                //长度变量
                $len_var_name = self::varName($tmp_index++, 'len');
                //循环变量
                $for_var_name = self::varName($tmp_index++, 'item');
                //循环键名
                $key_var_name = self::varName($tmp_index++, 'key');
                $code_buf->pushStr('var ' . $len_var_name . ' = 0;');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                //写入map key 和 value 的类型
                $code_buf->pushStr('var ' . $buffer_name . ' = new DopEncode();');
                $code_buf->pushStr('for( var ' . $key_var_name . ' in ' . $var_name . ') {');
                $code_buf->indent();
                $code_buf->pushStr('var ' . $for_var_name . ' = ' . $var_name . '[' . $key_var_name . '];');
                self::typeCheckCode($code_buf, $for_var_name, $value_item);
                self::packItemValue($code_buf, $key_var_name, $buffer_name, $key_item, $depth + 1, $tmp_index);
                //这里的depth 变成 0，因为之前已经typeCheckCode了
                self::packItemValue($code_buf, $for_var_name, $buffer_name, $value_item, 0, $tmp_index);
                $code_buf->pushStr('++' . $len_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr($result_name . '.writeLength(' . $len_var_name . ');');
                $code_buf->pushStr($result_name . '.joinBuffer(' . $buffer_name . ');');
                break;
        }
    }

    /**
     * 类型检查代码
     * @param CodeBuf $code_buf
     * @param string $var_name
     * @param Item $item
     */
    private static function typeCheckCode($code_buf, $var_name, $item)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::MAP:
                $code_buf->pushStr('if (!DopBase.isObject(' . $var_name . ')) {');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                break;
            case ItemType::ARR:
                $code_buf->pushStr('if (!DopBase.isArray(' . $var_name . ')) {');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $code_buf->pushStr('if (!(' . $var_name . ' instanceof ' . $item->getStructName() . ')) {');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                break;
        }
    }

    /**
     * 获取int值的写方法名
     * @param IntItem $item
     * @return string
     * @throws Exception
     */
    private static function getIntWriteFuncName($item)
    {
        $bin_type = $item->getBinaryType();
        static $func_arr = array(
            0x12 => 'Char',
            0x92 => 'Char',
            0x22 => 'Short',
            0xa2 => 'Short',
            0x42 => 'Int',
            0xc2 => 'Int',
            0x82 => 'BigInt',
        );
        if (!isset($func_arr[$bin_type])) {
            throw new Exception('Error int type:' . $bin_type);
        }
        return 'write' . $func_arr[$bin_type];
    }

    /**
     * 生成打包一个节点的代码
     * @param CodeBuf $code_buf
     * @param string $var_name
     * @param string $result_name
     * @param string $func_name 方法名
     */
    private static function packItemCode($code_buf, $var_name, $result_name, $func_name)
    {
        $code_buf->pushStr($result_name . '.' . $func_name . '(' . $var_name . ');');
    }
}
