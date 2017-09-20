<?php

namespace FFan\Dop\Coder\Objc;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\PackerBase;
use FFan\Dop\Build\StrBuf;
use FFan\Dop\Exception;
use FFan\Dop\Protocol\IntItem;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\ListItem;
use FFan\Dop\Protocol\MapItem;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\StructItem;

/**
 * @package FFan\Dop\Coder\Ojbc
 */
class DictionaryPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;

    /**
     * @var StrBuf
     */
    private $null_obj_buf;

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
        $code_buf->pushStr(' * 输出NSDictionary');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('- (NSDictionary *)dictionaryEncode {');
        $code_buf->indent();
        $code_buf->pushStr('NSMutableDictionary *result = [NSMutableDictionary new];');
        $this->null_obj_buf = new StrBuf();
        $code_buf->push($this->null_obj_buf);
        $this->writePropertyLoop($code_buf, $struct);
        $code_buf->pushStr('return result;');
        $code_buf->backIndent()->pushStr('}');
    }

    /**
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    private function writePropertyLoop($code_buf, $struct)
    {
        $all_item = $struct->getAllExtendItem();
        static $null_check_list = array(
            ItemType::STRING => true,
            ItemType::ARR => true,
            ItemType::MAP => true,
            ItemType::BINARY => true,
            ItemType::STRUCT => true,
        );
        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $type = $item->getType();
            $null_check = isset($null_check_list[$type]);
            if ($null_check) {
                $code_buf->pushStr('if (nil != self.' . $name . ') {')->indent();
                $this->packItemValue($code_buf, 'self.' . $name, 'result[@"' . $name . '"]', $item, 0, $tmp_index);
                $code_buf->backIndent()->pushStr('}');
            } else {
                $this->packItemValue($code_buf, 'self.' . $name, 'result[@"' . $name . '"]', $item, 0, $tmp_index);
            }
            $this->itemTrigger($code_buf, $item);
        }
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $value_name 变量名
     * @param string $name 属性名
     * @param Item $item 节点对象
     * @param int $depth 递归深度
     * @param int $tmp_index
     * @throws Exception
     */
    private function packItemValue($code_buf, $value_name, $name, $item, $depth, &$tmp_index)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
                /** @var IntItem $item */
                $code = 0 === $depth ? ('@(' . $value_name . ')') : $value_name;
                $code_buf->pushStr($name . ' = ' . $code . ';');
                break;
            case ItemType::STRING:
                $code = $value_name;
                $code_buf->pushStr($name . ' = ' . $code . ';');
                break;
            case ItemType::BINARY:
                $code = '[' . $value_name . ' base64EncodedStringWithOptions:0]';
                $code_buf->pushStr($name . ' = ' . $code . ';');
                break;
            case ItemType::BOOL:
                $code = 0 === $depth ? ('@(' . $value_name . ')') : $value_name;
                $code_buf->pushStr($name . ' = ' . $code . ';');
                break;
            case ItemType::DOUBLE:
                $code = 0 === $depth ? ('@(' . $value_name . ')') : $value_name;
                $code_buf->pushStr($name . ' = ' . $code . ';');
                break;
            case ItemType::FLOAT:
                $code = 0 === $depth ? ('@(' . $value_name . ')') : $value_name;
                $code_buf->pushStr($name . ' = ' . $code . ';');
                break;
            case ItemType::STRUCT:
                $code = '[' . $value_name . ' dictionaryEncode]';
                $code_buf->pushStr($name . ' = ' . $code . ';');
                break;
            case ItemType::ARR:
                $tmp_var = self::varName($tmp_index++, 'tmp_arr');
                $for_var = self::varName($tmp_index, 'tmp_item');
                $for_id_var = self::varName($tmp_index++, 'tmp_id');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $var_type = 'NS' . self::nsTypeName($sub_item->getType());
                $code_buf->pushStr('NSMutableArray *' . $tmp_var . ' = [NSMutableArray new];');
                $code_buf->pushStr('for (id ' . $for_id_var . ' in ' . $value_name . ') {');
                $code_buf->indent();
                $class_name = $this->coder->nsClassName($sub_item);
                $code_buf->pushStr('if (![' . $for_id_var . ' isKindOfClass:[' . $class_name . ' class]]){');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                $code_buf->pushStr($var_type . ' *' . $for_var . ';');
                self::packItemValue($code_buf, '(' . $class_name . ' *)' . $for_id_var, $for_var, $sub_item, $depth + 1, $tmp_index);
                $code_buf->pushStr('[' . $tmp_var . ' addObject:' . $for_var . '];');
                $code_buf->backIndent()->push('}');
                $code_buf->pushStr($name . ' = ' . $tmp_var . ';');
                break;
            case ItemType::MAP:
                $tmp_var = self::varName($tmp_index++, 'tmp_map');
                //Enumerator
                $enumerator_name = self::varName($tmp_index++, 'enumerator');
                $code_buf->pushStr('NSMutableDictionary *' . $tmp_var . ' = [NSMutableDictionary new];');
                //循环键名
                $key_var_name = self::varName($tmp_index++, 'key');
                $value_var_name = self::varName($tmp_index++, 'value');
                $for_key_name = self::varName($tmp_index++, 'tmp_key');
                $for_value_name = self::varName($tmp_index++, 'tmp_value');
                $code_buf->pushStr('NSEnumerator *' . $enumerator_name . ' = [' . $value_name . ' keyEnumerator];');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->pushStr('for (id ' . $key_var_name . ' in ' . $enumerator_name . '){');
                $code_buf->indent();
                $class_name = $this->coder->nsClassName($value_item);
                $code_buf->pushStr('id ' . $value_var_name . ' = [' . $value_name . ' objectForKey:' . $key_var_name . '];');
                $value_class_type = $this->coder->nsClassName($value_item);
                $code_buf->pushStr('if (![' . $value_var_name . ' isKindOfClass:[' . $class_name . ' class]]) {');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('};');
                $key_ns_type = self::nsTypeName($key_item->getType());
                $key_var_name = '[FFANDOPUtils idTo' . $key_ns_type . ':' . $key_var_name . ']';
                $key_type = $this->coder->varType($key_item, true, false);
                $value_type = $this->coder->varType($value_item, true, false);
                $value_var_name = '(' . $value_class_type . ' *)' . $value_var_name;
                $code_buf->pushStr($key_type . ' ' . $for_key_name . ';');
                $code_buf->pushStr($value_type . ' ' . $for_value_name . ';');
                self::packItemValue($code_buf, $key_var_name, $for_key_name, $key_item, $depth + 1, $tmp_index);
                self::packItemValue($code_buf, $value_var_name, $for_value_name, $value_item, $depth + 1, $tmp_index);
                $code_buf->pushStr($tmp_var . '[' . $for_key_name . '] = ' . $for_value_name . ';');
                $code_buf->backIndent()->pushStr('}');
                $code_buf->pushStr($name . ' = ' . $tmp_var . ';');
                break;
            default:
                throw new Exception('Unknown type');
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
        $this->pushImportCode('#import "FFANDOPUtils.h"');
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * Dictionary 解析');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('- (void)dictionaryDecode:(NSDictionary*) dict_map {')->indent();
        $all_item = $struct->getAllExtendItem();
        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $item_type = $item->getType();
            $ns_type = self::nsTypeName($item_type);
            //空对象判断
            if (ItemType::STRUCT === $item_type) {
                $dic_var = PackerBase::varName($tmp_index++, 'tmp');
                $code_buf->pushStr('NSDictionary *' . $dic_var . ' = [FFANDOPUtils idTo' . $ns_type . ':[dict_map valueForKey:@"' . $name . '"]]');
                $code_buf->pushStr('if ([' . $dic_var . ' count] > 0) {')->indent();
                $this->unpackItemValue($code_buf, 'self.' . $name, $dic_var, $item, $tmp_index);
                $code_buf->backIndent()->pushStr('}');
            } else {
                $value = '[FFANDOPUtils idTo' . $ns_type . ':[dict_map valueForKey:@"' . $name . '"]]';
                $this->unpackItemValue($code_buf, 'self.' . $name, $value, $item, $tmp_index);
            }
            $this->itemTrigger($code_buf, $item);
        }
        $code_buf->backIndent()->pushStr('}');
    }

    /**
     * 返回 objective-c 的类型
     * @param int $item_type
     * @return string
     */
    public static function nsTypeName($item_type)
    {
        switch ($item_type) {
            case ItemType::INT:
            case ItemType::BOOL:
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $type_name = 'Number';
                break;
            case ItemType::BINARY:
                $type_name = 'Data';
                break;
            case ItemType::STRING:
                $type_name = 'String';
                break;
            case ItemType::STRUCT:
            case ItemType::MAP:
                $type_name = 'Dictionary';
                break;
            case ItemType::ARR:
                $type_name = 'Array';
                break;
            default:
                $type_name = 'Null';
        }
        return $type_name;
    }

    /**
     * 生成int to nsNumber转换代码
     * @param Item $item
     * @return string
     */
    public static function nsNumberCode($item)
    {
        $item_type = $item->getType();
        if (ItemType::INT === $item_type) {
            /** @var IntItem $item */
            $byte = $item->getByte();
            if (1 === $byte) {
                $code = 'char';
            } elseif (2 === $byte) {
                $code = 'short';
            } elseif (4 === $byte) {
                $code = 'int';
            } else {
                $code = 'longLong';
            }
            if ($item->isUnsigned()) {
                $code = 'unsigned' . ucfirst($code);
            }
        } elseif (ItemType::FLOAT === $item_type) {
            $code = 'float';
        } elseif (ItemType::DOUBLE === $item_type) {
            $code = 'double';
        } elseif (ItemType::BOOL) {
            $code = 'bool';
        } else {
            return null;
        }
        return $code . 'Value';
    }

    /**
     * 解出数据
     * @param CodeBuf $code_buf 生成代码缓存
     * @param string $var_name 值变量名
     * @param string $value 值
     * @param Item $item 节点对象
     * @param int $tmp_index 深度
     * @param int $depth
     */
    private function unpackItemValue($code_buf, $var_name, $value, $item, &$tmp_index, $depth = 0)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::STRING:
            case ItemType::BINARY:
                $code_buf->pushStr($var_name . " = " . $value . ';');
                break;
            case ItemType::INT:
            case ItemType::BOOL:
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                if (0 == $depth) {
                    $func_name = self::nsNumberCode($item);
                    $code_buf->pushStr($var_name . ' = [' . $value . ' ' . $func_name . '];');
                } else {
                    $code_buf->pushStr($var_name . ' = ' . $value . ';');
                }
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $sub_struct = $item->getStruct();
                $class_name = $this->coder->makeClassName($sub_struct);
                if ($depth > 0) {
                    $dic_var = self::varName($tmp_index++, 'tmp_dic');
                    $code_buf->pushStr('NSDictionary *' . $dic_var . ' = ' . $value . ';');
                    $code_buf->pushStr('if (0 == [' . $dic_var . ' count]) {');
                    $code_buf->pushIndent('continue;');
                    $code_buf->pushStr('}');
                    $value = $dic_var;
                }
                $code_buf->pushStr($var_name . ' = [' . $class_name . ' new];');
                $code_buf->pushStr('[' . $var_name . ' dictionaryDecode:' . $value . '];');
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->pushStr($var_name . ' = [NSMutableArray new];');
                $for_var = self::varName($tmp_index++, 'id');
                $for_value = self::varName($tmp_index++, 'tmp');
                $code_buf->pushStr('for (id ' . $for_var . ' in ' . $value . ') {')->indent();
                $code_buf->pushStr('if (nil == ' . $for_var . ') {');
                $code_buf->pushIndent('continue;');
                $code_buf->pushStr('}');
                $var_type = $this->coder->varType($sub_item, true, false);
                $code_buf->pushStr($var_type . ' ' . $for_value . ';');
                $ns_type = self::nsTypeName($sub_item->getType());
                $this->unpackItemValue($code_buf, $for_value, '[FFANDOPUtils idTo' . $ns_type . ':' . $for_var . ']', $sub_item, $tmp_index, $depth + 1);
                $code_buf->pushStr('[' . $var_name . ' addObject:' . $for_value . '];');
                $code_buf->backIndent()->pushStr('}');
                break;
            case ItemType::MAP:
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->pushStr($var_name . ' = [NSMutableDictionary new];');
                $for_key_var = self::varName($tmp_index++, 'key');
                $for_value_var = self::varName($tmp_index++, 'value');
                $for_key_name = self::varName($tmp_index++, 'tmp_key');
                $for_value_name = self::varName($tmp_index++, 'tmp_value');
                $code_buf->pushStr('[' . $value . ' enumerateKeysAndObjectsUsingBlock:^(id ' . $for_key_var . ', id ' . $for_value_var . ', BOOL * stop) {');
                $code_buf->indent();
                $key_type = $this->coder->varType($key_item, true, false);
                $value_type = $this->coder->varType($value_item, true, false);
                $key_ns_type = self::nsTypeName($key_item->getType());
                $value_ns_type = self::nsTypeName($value_item->getType());
                $code_buf->pushStr($key_type . ' ' . $for_key_name . ' = [FFANDOPUtils idTo' . $key_ns_type . ':' . $for_key_var . '];');
                $code_buf->pushStr($value_type . ' ' . $for_value_name . ';');
                $this->unpackItemValue($code_buf, $for_value_name, '[FFANDOPUtils idTo' . $value_ns_type . ':' . $for_value_var . ']', $value_item, $tmp_index, $depth + 1);
                $code_buf->pushStr($var_name . '[' . $for_key_name . '] = ' . $for_value_name . ';');
                $code_buf->backIndent()->pushStr('}];');
                break;
        }
    }
}
