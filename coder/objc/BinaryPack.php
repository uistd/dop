<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\Exception;
use ffan\dop\protocol\IntItem;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;

/**
 * Class BinaryPack
 * @package ffan\dop\coder\objc
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
        return array('struct', 'dictionary');
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
        $code_buf->pushStr(' */');
        $this->pushImportCode('#import "FFANDOPUtils.h"');
        //如果是子 struct
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('- (void)binaryPack:(FFANDOPEncode *) result {');
            $code_buf->indent();
        } else {
            $code_buf->pushStr('- (NSData *)doPack:(BOOL)pid is_sign:(BOOL)sign mask_key:(NSString *)mask_key {');
            $code_buf->indent();
            $code_buf->pushStr('FFANDOPEncode *result = [FFANDOPEncode new];');
            $pid = $struct->getNamespace() . $struct->getClassName();
            $code_buf->pushStr('if (pid) {');
            $code_buf->pushIndent('[result writePid:@"' . $pid . '"];');
            $code_buf->pushStr('}');
            $code_buf->pushStr('if (sign) {');
            $code_buf->pushIndent('[result sign];');
            $code_buf->pushStr('}');
            $code_buf->pushStr('if (nil != mask_key && [mask_key length] > 0) {');
            $code_buf->pushIndent('[result mask:mask_key];');
            $code_buf->pushStr('}');
            //打包进去协议
            $class_name = $this->coder->makeClassName($struct);
            $code_buf->pushStr('[result writeData:[' . $class_name . ' binaryStruct] with_length:YES];');
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
                $code_buf->pushStr('if (nil == self.' . $name . '){');
                $code_buf->pushIndent('[result writeChar:0];');
                $code_buf->pushStr('} else {')->indent();
            }
            if (ItemType::STRUCT === $item_type) {
                $code_buf->pushStr('[result writeUnsignedChar:0xff];');
            }
            $this->packItemValue($code_buf, 'self.' . $name, 'result', $item, $tmp_index);
            if ($is_null_check) {
                $code_buf->backIndent()->pushStr('}');
            }
        }
        if (!$struct->isSubStruct()) {
            $code_buf->pushStr('return [result pack];');
            $code_buf->backIndent()->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (NSData *)binaryEncode {');
            $code_buf->pushIndent('return [self doPack:NO is_sign:NO mask_key:nil];');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (NSData *)binaryEncode:(BOOL)pid {');
            $code_buf->pushIndent('return [self doPack:pid is_sign:NO mask_key:nil];');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (NSData *)binaryEncode:(BOOL)pid is_sign:(BOOL)is_sign {');
            $code_buf->pushIndent('return [self doPack:pid is_sign:is_sign mask_key:nil];');
            $code_buf->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (NSData *)binaryEncode:(BOOL)pid mask_key:(NSString *)mask_key {');
            $code_buf->pushIndent('return [self doPack:pid is_sign:YES mask_key:mask_key];');
            $code_buf->pushStr('}');
        } else {
            $code_buf->backIndent()->pushStr('}');
        }

    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 二进制解包');
        $code_buf->pushStr(' */');
        if (!$struct->isSubStruct()) {
            $this->pushImportCode('#import "FFANDOPDecode.h"');
            $code_buf->pushStr('- (int)binaryDecode:(NSData *)data {')->indent();
            $code_buf->pushStr('FFANDOPDecode *decoder = [[FFANDOPDecode alloc] initWithData:data];');
            $code_buf->pushStr('NSDictionary *dop_struct = [decoder unpack];');
            $code_buf->pushStr('if ([decoder getErrorCode] > 0) {');
            $code_buf->pushIndent('return [decoder getErrorCode];');
            $code_buf->pushStr('}');
            $code_buf->pushStr('[self dictionaryDecode:dop_struct];');
            $code_buf->pushStr('return 0;');
            $code_buf->backIndent()->pushStr('}');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (int)binaryDecode:(NSData *)data mask_key:(NSString*)mask_key {')->indent();
            $code_buf->pushStr('FFANDOPDecode *decoder = [[FFANDOPDecode alloc] initWithData:data];');
            $code_buf->pushStr('NSDictionary *dop_struct = [decoder unpack:mask_key];');
            $code_buf->pushStr('if ([decoder getErrorCode] > 0) {');
            $code_buf->pushIndent('return [decoder getErrorCode];');
            $code_buf->pushStr('}');
            $code_buf->pushStr('[self dictionaryDecode:dop_struct];');
            $code_buf->pushStr('return 0;');
            $code_buf->backIndent()->pushStr('}');
        }
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $var_name 变量名
     * @param string $result_name 结果数组
     * @param Item $item 节点对象
     * @param int $tmp_index
     */
    private function packItemValue($code_buf, $var_name, $result_name, $item, &$tmp_index = 0)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::BINARY:
                $code_buf->pushStr('[' . $result_name . ' writeData:' . $var_name . ' with_length:YES];');
                break;
            case ItemType::STRING:
                $code_buf->pushStr('[' . $result_name . ' writeString:' . $var_name . '];');
                break;
            case ItemType::FLOAT:
                $code_buf->pushStr('[' . $result_name . ' writeFloat:' . $var_name . '];');
                break;
            case ItemType::DOUBLE:
                $code_buf->pushStr('[' . $result_name . ' writeDouble:' . $var_name . '];');
                break;
            case ItemType::INT:
                /** @var IntItem $item */
                $func_name = self::getIntWriteFuncName($item);
                $code_buf->pushStr('[' . $result_name . ' ' . $func_name . ':' . $var_name . '];');
                break;
            case ItemType::BOOL:
                $code_buf->pushStr('[' . $result_name . ' writeChar: (' . $var_name . ' ? 1 : 0)];');
                break;
            case ItemType::STRUCT:
                $code_buf->pushStr('[' . $var_name . ' binaryPack:' . $result_name . '];');
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
                $code_buf->pushStr('int ' . $len_var_name . ' = 0;');
                //写入list的类型
                $code_buf->pushStr('FFANDOPEncode *' . $buffer_name . ' = [FFANDOPEncode new];');
                $code_buf->pushStr('for (id ' . $for_var_name . ' in ' . $var_name . ') {');
                $code_buf->indent();
                $class_name = $this->coder->nsClassName($sub_item);
                $code_buf->pushStr('if (![' . $for_var_name . ' isKindOfClass:[' . $class_name . ' class]]){');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                $for_var_name = self::objectChangeToBasic($sub_item, '(' . $class_name . ' *)' . $for_var_name);
                self::packItemValue($code_buf, $for_var_name, $buffer_name, $sub_item, $tmp_index);
                $code_buf->pushStr('++' . $len_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr('[' . $result_name . ' writeLength:' . $len_var_name . '];');
                $code_buf->pushStr('[' . $result_name . ' writeData:[' . $buffer_name . ' getData] with_length:NO];');
                break;
            case ItemType::MAP:
                //临时buffer
                $buffer_name = self::varName($tmp_index++, 'map_buf');
                //长度变量
                $len_var_name = self::varName($tmp_index++, 'len');
                //Enumerator
                $enumerator_name = self::varName($tmp_index++, 'enumerator');
                //循环键名
                $key_var_name = self::varName($tmp_index++, 'key');
                $value_var_name = self::varName($tmp_index++, 'value');
                $code_buf->pushStr('int ' . $len_var_name . ' = 0;');
                $code_buf->pushStr('NSEnumerator *' . $enumerator_name . ' = ['. $var_name .' keyEnumerator];');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->pushStr('FFANDOPEncode *' . $buffer_name . ' = [FFANDOPEncode new];');
                $code_buf->pushStr('for (id ' . $key_var_name . ' in ' . $enumerator_name . '){');
                $code_buf->indent();
                $code_buf->pushStr('id '. $value_var_name .' = ['. $var_name .' objectForKey:'. $key_var_name .'];');
                $value_class_type = $this->coder->nsClassName($value_item);
                $code_buf->pushStr('if ([' . $value_var_name . ' isKindOfClass:[' . $value_var_name . ' class]]) {')->indent();
                $key_ns_type = DictionaryPack::nsTypeName($key_item->getType());
                $key_var_name = self::objectChangeToBasic($key_item, '[FFANDOPUtils idTo'. $key_ns_type .':'.$key_var_name.']');
                $value_var_name = self::objectChangeToBasic($value_item, '(' . $value_class_type . ' *)' . $value_var_name);
                self::packItemValue($code_buf, $key_var_name, $buffer_name, $key_item, $tmp_index);
                self::packItemValue($code_buf, $value_var_name, $buffer_name, $value_item, $tmp_index);
                $code_buf->pushStr('++' . $len_var_name . ';');
                $code_buf->backIndent()->pushStr('}');
                $code_buf->backIndent()->pushStr('}');
                $code_buf->pushStr('[' . $result_name . ' writeLength:' . $len_var_name . '];');
                $code_buf->pushStr('[' . $result_name . ' writeData:[' . $buffer_name . ' getData] with_length:NO];');
                break;
        }
    }

    /**
     * 将对象转成基础类型
     * @param Item $item
     * @param string $code
     * @return string
     */
    private static function objectChangeToBasic($item, $code)
    {
        $item_type = $item->getType();
        $change_arr = array(
            ItemType::BOOL => true,
            ItemType::INT => true,
            ItemType::FLOAT => true,
            ItemType::DOUBLE => true
        );
        if (!isset($change_arr[$item_type])) {
            return $code;
        }
        $number_code = DictionaryPack::nsNumberCode($item);
        return '[' . $code . ' ' . $number_code . ']';
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
            0x92 => 'UnsignedChar',
            0x22 => 'Int16',
            0xa2 => 'UInt16',
            0x42 => 'Int32',
            0xc2 => 'UInt32',
            0x82 => 'Int64',
        );
        if (!isset($func_arr[$bin_type])) {
            throw new Exception('Error int type:' . $bin_type);
        }
        return 'write' . $func_arr[$bin_type];
    }
}