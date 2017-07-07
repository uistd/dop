<?php

namespace ffan\dop\coder\java;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\FileBuf;
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
 * @package ffan\dop\coder\java
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
        return array('struct');
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
        //如果是子 struct
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('void binaryPack(DopEncode encoder)');
            $code_buf->pushStr('{');
            $code_buf->indent();
        } else {
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public byte[] binaryEncode() {');
            $code_buf->pushIndent('return this.doPack();');
            $code_buf->pushIndent('return this.doPack();');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public byte[] binaryEncode(boolean pid) {');
            $code_buf->pushIndent('return this.doPack(pid, false, null);');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public byte[] binaryEncode(boolean pid, bool sign) {');
            $code_buf->pushIndent('return this.doPack(pid, sign, null);');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public byte[] binaryEncode(boolean pid, bool sign, String mask) {');
            $code_buf->pushIndent('return this.doPack(pid, sign, mask);');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('byte[] doPack(pid = false, $sign = false, $mask_key = null) {');
            $code_buf->indent();
            $code_buf->pushStr('DopEncode result = new DopEncode();');
            $pid = $struct->getNamespace() . $struct->getClassName();
            $code_buf->pushStr('if (pid) {');
            $code_buf->pushIndent('result.writePid("' . $pid . '");');
            $code_buf->pushStr('}');
            $code_buf->pushStr('if (sign) {');
            $code_buf->pushIndent('result.sign();');
            $code_buf->pushStr('}');
            $code_buf->pushStr('if (null != mask_key && mask_key.length() > 0) {');
            $code_buf->pushIndent('result.mask(mask_key);');
            $code_buf->pushStr('}');
            //打包进去协议
            $code_buf->pushStr('result.writeBuffer(binaryStruct(), true);');
        }
        $all_item = $struct->getAllExtendItem();
        $null_check_arr = array(
            ItemType::MAP => true,
            ItemType::ARR => true,
            ItemType::STRING => true,
            ItemType::STRUCT => true,
            ItemType::BINARY => true,
        );
        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $item_type = $item->getType();
            $is_null_check = isset($null_check_arr[$item_type]);
            if ($is_null_check) {
                $code_buf->pushStr('if (null == this.'.$name.')){');
                $code_buf->pushIndent('result.writeByte(0);');
                $code_buf->pushStr('} else {')->indent();
            }
            if (ItemType::STRUCT === $item_type) {
                $code_buf->pushIndent('result.writeByte(0xff);');
            }
            $this->packItemValue($code_buf, 'this.'. $name, 'result', $item, 0, $tmp_index);
            if ($is_null_check) {
                $code_buf->backIndent()->pushStr('}');
            }
        }
        if (!$struct->isSubStruct()) {
            $code_buf->pushStr('return result.pack();');
        }
        $code_buf->backIndent()->pushStr('}');
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
        $use_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        if ($use_buf) {
            $use_buf->pushUniqueStr('use '. $this->coder->joinNameSpace('', 'DopDecode') .';');
        }
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 二进制解包');
        $code_buf->pushStr(' * @param DopDecode|string $data');
        $code_buf->pushStr(' * @param string|null $mask_key');
        $code_buf->pushStr(' * @return bool');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public function binaryDecode($data, $mask_key = null)');
        $code_buf->pushStr('{')->indent();
        $code_buf->pushStr('$decoder = $data instanceof DopDecode ? $data : new DopDecode($data);');
        $code_buf->pushStr('$data_arr = $decoder->unpack($mask_key);');
        $code_buf->pushStr('if ($decoder->getErrorCode()) {');
        $code_buf->pushIndent('return false;');
        $code_buf->pushStr('}');
        $code_buf->pushStr('$this->arrayUnpack($data_arr);');
        $code_buf->pushStr('return true;');
        $code_buf->backIndent()->pushStr('}');
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $var_name 变量名
     * @param string $result_name 结果数组
     * @param Item $item 节点对象
     * @param int $depth 深度
     * @param $tmp_index
     */
    private function packItemValue($code_buf, $var_name, $result_name, $item, $depth = 0, &$tmp_index)
    {
        $item_type = $item->getType();
        if ($depth > 0) {
            $code_buf->pushStr('if (null == '.$var_name.')){');
            $code_buf->pushIndent('continue;');
            $code_buf->pushStr('}');
        }
        switch ($item_type) {
            case ItemType::BINARY:
                $code_buf->pushStr($result_name.'.writeByteArray('. $var_name .', true);');
                break;
            case ItemType::STRING:
                $code_buf->pushStr($result_name.'.writeString('. $var_name .');');
                break;
            case ItemType::FLOAT:
                $code_buf->pushStr($result_name.'.writeFloat('. $var_name .');');
                break;
            case ItemType::DOUBLE:
                $code_buf->pushStr($result_name.'.writeDouble('. $var_name .');');
                break;
            case ItemType::INT:
                /** @var IntItem $item */
                $func_name = self::getIntWriteFuncName($item);
                $code_buf->pushStr($result_name.'.'. $func_name .'('. $var_name .');');
                break;
            case ItemType::BOOL:
                $code_buf->pushStr($result_name.'.writeByte('. $var_name .' ? 1 : 0);');
                break;
            case ItemType::STRUCT:
                $code_buf->pushStr($var_name . '.binaryPack(' . $result_name . ');');
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                //临时buffer
                $buffer_name = self::varName($tmp_index++, 'arr_buf');
                //长度变量
                $len_var_name = self::varName($tmp_index++, 'len');
                //循环变量
                $for_var_name = self::varName($tmp_index++, 'item');
                $for_type = Coder::varType($sub_item);
                $code_buf->pushStr('int ' . $len_var_name . ' = 0;');
                //写入list的类型
                $code_buf->pushStr($buffer_name . ' = new DopEncode();');
                $code_buf->pushStr('for (List<'.$for_type.'> ' . $for_var_name . ' : '.$var_name.') {');
                $code_buf->indent();
                self::packItemValue($code_buf, $for_var_name, $buffer_name, $sub_item, $depth + 1, $tmp_index);
                $code_buf->pushStr('++' . $len_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr($result_name . '.writeLength(' . $len_var_name . ');');
                $code_buf->pushStr($result_name . '.writeByteArray(' . $buffer_name . '.getBuffer());');
                break;
            case ItemType::MAP:
                //临时buffer
                $buffer_name = self::varName($depth, 'map_buf');
                //长度变量
                $len_var_name = self::varName($depth, 'len');
                //循环变量
                $for_var_name = self::varName($depth, 'item');
                //循环键名
                $key_var_name = self::varName($depth, 'key');
                $code_buf->pushStr($len_var_name . ' = 0;');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                //写入map key 和 value 的类型
                $code_buf->pushStr($buffer_name . ' = new DopEncode();');
                $for_type = $this->coder->getMapIteratorType($item);
                $code_buf->pushStr('for (' . $for_type . ' ' . $for_var_name . ' : ' . $var_name . '.entrySet()) {');
                $code_buf->indent();
                $code_buf->pushStr('if (null == '.$var_name.')){');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                $code_buf->backIndent();
                $code_buf->indent();
                self::packItemValue($code_buf, $key_var_name, $buffer_name, $key_item, $depth + 1);
                //这里的depth 变成 0，因为之前已经typeCheckCode了
                self::packItemValue($code_buf, $for_var_name, $buffer_name, $value_item, 0);
                $code_buf->pushStr('++$' . $len_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr('$' . $result_name . '->writeLength($' . $len_var_name . ');');
                $code_buf->pushStr('$' . $result_name . '->joinBuffer($' . $buffer_name . ');');
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
            case ItemType::ARR:
                $code_buf->pushStr('if (!is_array($' . $var_name . ')) {');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $code_buf->pushStr('if (!$' . $var_name . ' instanceof ' . $item->getStructName() . ') {');
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
            0x12 => 'Byte',
            0x92 => 'Byte',
            0x22 => 'Short',
            0xa2 => 'Short',
            0x42 => 'Int',
            0xc2 => 'Int',
            0x82 => 'Bigint',
        );
        if (!isset($func_arr[$bin_type])) {
            throw new Exception('Error int type:' . $bin_type);
        }
        return 'write' . $func_arr[$bin_type];
    }
}
