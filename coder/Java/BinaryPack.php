<?php

namespace UiStd\Dop\Coder\Java;

use UiStd\Dop\Build\CodeBuf;
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
 * @package UiStd\Dop\Coder\Java
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
            $code_buf->pushStr('public void binaryPack(DopEncode result)');
            $code_buf->pushStr('{');
            $code_buf->indent();
        } else {
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public byte[] binaryEncode() {');
            $code_buf->pushIndent('return this.doPack(false, false, null);');
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
            $code_buf->pushStr('public byte[] binaryEncode(boolean pid, boolean sign) {');
            $code_buf->pushIndent('return this.doPack(pid, sign, null);');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public byte[] binaryEncode(boolean pid, String mask) {');
            $code_buf->pushIndent('return this.doPack(pid, true, mask);');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制打包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('private byte[] doPack(boolean pid, boolean sign, String mask_key) {');
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
            $code_buf->pushStr('result.writeByteArray(binaryStruct(), true);');
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
                $code_buf->pushStr('if (null == this.' . $name . '){');
                $code_buf->pushIndent('result.writeByte((byte) 0);');
                $code_buf->pushStr('} else {')->indent();
            }
            if (ItemType::STRUCT === $item_type) {
                $code_buf->pushIndent('result.writeByte((byte) 0xff);');
            }
            $this->packItemValue($code_buf, 'this.' . $name, 'result', $item, $tmp_index);
            if ($is_null_check) {
                $code_buf->backIndent()->pushStr('}');
            }
            $this->itemTrigger($code_buf, $item);
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
        $this->pushImportCode('import ' . $this->coder->joinNameSpace('', 'DopStruct'));
        $this->pushImportCode('import ' . $this->coder->joinNameSpace('', 'Item'));
        if (!$struct->isSubStruct()) {
            $import_str = $this->coder->joinNameSpace('', 'DopDecode');
            $this->pushImportCode('import ' . $import_str);
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制解包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public int binaryDecode(byte[] data)');
            $code_buf->pushStr('{')->indent();
            $code_buf->pushStr('DopDecode decoder = new DopDecode(data);');
            $code_buf->pushStr('DopStruct dop_struct = decoder.unpack();');
            $code_buf->pushStr('if (null == dop_struct || decoder.getErrorCode() > 0) {');
            $code_buf->pushIndent('return decoder.getErrorCode();');
            $code_buf->pushStr('}');
            $code_buf->pushStr('this.readDopStruct(dop_struct);');
            $code_buf->pushStr('return 0;');
            $code_buf->backIndent()->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制解包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public int binaryDecode(byte[] data, String mask_key)');
            $code_buf->pushStr('{')->indent();
            $code_buf->pushStr('DopDecode decoder = new DopDecode(data);');
            $code_buf->pushStr('DopStruct dop_struct = decoder.unpack(mask_key);');
            $code_buf->pushStr('if (null == dop_struct || decoder.getErrorCode() > 0) {');
            $code_buf->pushIndent('return decoder.getErrorCode();');
            $code_buf->pushStr('}');
            $code_buf->pushStr('this.readDopStruct(dop_struct);');
            $code_buf->pushStr('return 0;');
            $code_buf->backIndent()->pushStr('}');
        }
        $all_item = $struct->getAllExtendItem();
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 二进制解包');
        $code_buf->pushStr(' */');
        $access_type = $struct->isSubStruct() ? 'public' : 'private';
        $code_buf->pushStr($access_type . ' void readDopStruct(DopStruct dop_struct)');
        $code_buf->pushStr('{')->indent();
        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $code_buf->pushStr('Item ' . $name . ' = dop_struct.get("' . $name . '");');
            $code_buf->pushStr('if (null != ' . $name . ') {')->indent();
            $this->unpackItemValue($code_buf, 'this.' . $name, $name, $item, $tmp_index, true);
            $code_buf->backIndent()->pushStr('}');
        }
        $code_buf->backIndent()->pushStr('}');
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $var_name 变量名
     * @param string $result_name 结果数组
     * @param Item $item 节点对象
     * @param $tmp_index
     */
    private function packItemValue($code_buf, $var_name, $result_name, $item, &$tmp_index = 0)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::BINARY:
                $code_buf->pushStr($result_name . '.writeByteArray(' . $var_name . ', true);');
                break;
            case ItemType::STRING:
                $code_buf->pushStr($result_name . '.writeString(' . $var_name . ');');
                break;
            case ItemType::FLOAT:
                $code_buf->pushStr($result_name . '.writeFloat(' . $var_name . ');');
                break;
            case ItemType::DOUBLE:
                $code_buf->pushStr($result_name . '.writeDouble(' . $var_name . ');');
                break;
            case ItemType::INT:
                /** @var IntItem $item */
                $func_name = self::getIntWriteFuncName($item);
                $code_buf->pushStr($result_name . '.' . $func_name . '(' . $var_name . ');');
                break;
            case ItemType::BOOL:
                $code_buf->pushStr($result_name . '.writeByte((byte) (' . $var_name . ' ? 1 : 0));');
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
                $code_buf->pushStr('DopEncode ' . $buffer_name . ' = new DopEncode();');
                $code_buf->pushStr('for (' . $for_type . ' ' . $for_var_name . ' : ' . $var_name . ') {');
                $code_buf->indent();
                $sub_type = $sub_item->getType();
                if (in_array($sub_type, array(ItemType::STRING, ItemType::MAP, ItemType::STRUCT, ItemType::ARR))) {
                    $code_buf->pushStr('if (null == ' . $for_var_name . '){');
                    $code_buf->pushIndent('continue;');
                    $code_buf->pushStr('}');
                }
                self::packItemValue($code_buf, $for_var_name, $buffer_name, $sub_item, $tmp_index);
                $code_buf->pushStr('++' . $len_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr($result_name . '.writeLength(' . $len_var_name . ');');
                $code_buf->pushStr($result_name . '.writeByteArray(' . $buffer_name . '.getBuffer());');
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
                $value_var_name = self::varName($tmp_index++, 'value');
                $code_buf->pushStr('int ' . $len_var_name . ' = 0;');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $key_type = Coder::varType($key_item, 0, false, true);
                $value_type = Coder::varType($value_item);
                //写入map key 和 value 的类型
                $code_buf->pushStr('DopEncode ' . $buffer_name . ' = new DopEncode();');
                $for_type = $this->coder->getMapIteratorType($item);
                $code_buf->pushStr('for (' . $for_type . ' ' . $for_var_name . ' : ' . $var_name . '.entrySet()) {');
                $code_buf->indent();
                $code_buf->pushStr($key_type . ' ' . $key_var_name . ' = ' . $for_var_name . '.getKey();');
                $code_buf->pushStr($value_type . ' ' . $value_var_name . ' = ' . $for_var_name . '.getValue();');
                $code_buf->pushStr('if (null == ' . $key_var_name . ' || null == ' . $value_var_name . '){');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                //这里的depth 变成 0，因为之前已经typeCheckCode了
                self::packItemValue($code_buf, $key_var_name, $buffer_name, $key_item, $tmp_index);
                self::packItemValue($code_buf, $value_var_name, $buffer_name, $value_item, $tmp_index);
                $code_buf->pushStr('++' . $len_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr($result_name . '.writeLength(' . $len_var_name . ');');
                $code_buf->pushStr($result_name . '.writeByteArray(' . $buffer_name . '.getBuffer());');
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
            0x82 => 'BigInt',
        );
        if (!isset($func_arr[$bin_type])) {
            throw new Exception('Error int type:' . $bin_type);
        }
        return 'write' . $func_arr[$bin_type];
    }


    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $var_name 变量名
     * @param string $dop_name 结果数组
     * @param Item $item 节点对象
     * @param int $tmp_index
     * @param bool $is_property 是否是属性
     */
    private function unpackItemValue($code_buf, $var_name, $dop_name, $item, &$tmp_index = 0, $is_property = false)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::BINARY:
                $code_buf->pushStr($var_name . ' = ' . $dop_name . '.getValueByte();');
                break;
            case ItemType::STRING:
                $code_buf->pushStr($var_name . ' = ' . $dop_name . '.getValueString();');
                break;
            case ItemType::FLOAT:
                $code_buf->pushStr($var_name . ' = ' . $dop_name . '.getValueFloat();');
                break;
            case ItemType::DOUBLE:
                $code_buf->pushStr($var_name . ' = ' . $dop_name . '.getValueDouble();');
                break;
            case ItemType::INT:
                $var_type = Coder::varType($item);
                $code_str = $dop_name . '.getValueInt();';
                if ('long' !== $var_type) {
                    $code_str = '(' . $var_type . ') ' . $code_str;
                }
                $code_buf->pushStr($var_name . ' = ' . $code_str);
                break;
            case ItemType::BOOL:
                $code_buf->pushStr($var_name . ' = ' . $dop_name . '.getValueBool();');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $sub_item = $item->getStruct();
                $tmp_var = self::varName($tmp_index++, 'tmp_struct');
                $code_buf->pushStr('DopStruct ' . $tmp_var . ' = ' . $dop_name . '.getValueStruct();');
                //如果是属性
                if ($is_property) {
                    $code_buf->pushStr('if(null != ' . $tmp_var . ') {')->indent();
                } else {
                    $code_buf->pushStr('if(null == ' . $tmp_var . ') {');
                    $code_buf->pushIndent('continue;');
                    $code_buf->pushStr('}');
                }
                $code_buf->pushStr($var_name . ' = new ' . $sub_item->getClassName() . '();');
                $code_buf->pushStr($var_name . '.readDopStruct(' . $tmp_var . ');');
                if ($is_property) {
                    $code_buf->backIndent()->pushStr('}');
                }
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                //临时list
                $tmp_list = self::varName($tmp_index++, 'tmp_list');
                //循环变量
                $for_var_name = self::varName($tmp_index++, 'item');
                $list_type = Coder::varType($item, 0, false);
                $code_buf->pushStr('List<Item> ' . $tmp_list . ' = ' . $dop_name . '.getValueArray();');
                $code_buf->pushStr($var_name . ' = new ' . $list_type . '(' . $tmp_list . '.size());');
                $sub_item_type = Coder::varType($sub_item, 0, false);
                $for_item_value = self::varName($tmp_index++, 'item_value');
                $code_buf->pushStr($sub_item_type . ' ' . $for_item_value . ';');
                $code_buf->pushStr('for (Item ' . $for_var_name . ' : ' . $tmp_list . ') {');
                $code_buf->indent();
                self::unpackItemValue($code_buf, $for_item_value, $for_var_name, $sub_item, $tmp_index);
                $code_buf->pushStr($var_name . '.add(' . $for_item_value . ');');
                $code_buf->backIndent()->pushStr('}');
                break;
            case ItemType::MAP:
                //临时map
                $tmp_map = self::varName($tmp_index++, 'map');
                //循环变量
                $for_var_name = self::varName($tmp_index++, 'item');
                //循环键名
                $key_var_name = self::varName($tmp_index++, 'key');
                $value_var_name = self::varName($tmp_index++, 'value');
                $map_type = Coder::varType($item, 0, false);
                $code_buf->pushStr('Map<Item, Item> ' . $tmp_map . ' = ' . $dop_name . '.getValueMap();');
                $code_buf->pushStr($var_name . ' = new ' . $map_type . '(' . $tmp_map . '.size());');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $key_type = Coder::varType($key_item, 0, false, true);
                $value_type = Coder::varType($value_item);
                $code_buf->pushStr($key_type . ' ' . $key_var_name . ';');
                $code_buf->pushStr($value_type . ' ' . $value_var_name . ';');
                $code_buf->pushStr('for (Map.Entry<Item, Item>' . $for_var_name . ' : ' . $tmp_map . '.entrySet()) {');
                $code_buf->indent();
                //这里的depth 变成 0，因为之前已经typeCheckCode了
                self::unpackItemValue($code_buf, $key_var_name, $for_var_name . '.getKey()', $key_item, $tmp_index);
                self::unpackItemValue($code_buf, $value_var_name, $for_var_name . '.getValue()', $value_item, $tmp_index);
                $code_buf->pushStr($var_name . '.put(' . $key_var_name . ', ' . $value_var_name . ');');
                $code_buf->backIndent()->pushStr('}');
                break;
        }
    }
}