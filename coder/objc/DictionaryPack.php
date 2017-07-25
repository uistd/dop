<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\protocol\IntItem;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;

/**
 * @package ffan\dop\coder\objc
 */
class DictionaryPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;

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
            $value = '[FFANDOPUtils idTo' . $ns_type . ':[dict_map valueForKey:@"' . $name . '"]]';
            $this->unpackItemValue($code_buf, 'self.' . $name, $value, $item, $tmp_index);
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
                $code_buf->pushStr($var_name . ' = [' . $class_name . ' new];');
                $code_buf->pushStr('[' . $var_name . ' dictionaryDecode:' . $value . '];');
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->pushStr($var_name . ' = [NSMutableArray new];');
                $for_var = self::varName($tmp_index++, 'id');
                $for_value = self::varName($tmp_index++, 'tmp');
                $code_buf->pushStr('for (id ' . $for_var . ' in ' . $value . ') {');
                $var_type = $this->coder->varType($sub_item, true, false);
                $code_buf->indent()->pushStr($var_type . ' ' . $for_value . ';');
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
                $code_buf->pushStr('[' . $value . ' enumerateKeysAndObjectsUsingBlock:^(id ' . $for_key_var . ', id ' . $for_value_var . ', BOOL *stop) {');
                $code_buf->indent();
                $key_type = $this->coder->varType($key_item, true, false);
                $value_type = $this->coder->varType($value_item, true, false);
                $key_ns_type = self::nsTypeName($key_item->getType());
                $value_ns_type = self::nsTypeName($value_item->getType());
                $code_buf->pushStr($key_type . ' ' . $for_key_name . ' = [FFANDOPUtils idTo' . $key_ns_type . ':' . $for_key_var . '];');
                $code_buf->pushStr($value_type . ' ' . $for_value_name . ';');
                $this->unpackItemValue($code_buf, $for_value_name, '[FFANDOPUtils idTo' . $value_ns_type . ':' . $for_value_var . ']', $value_item, $tmp_index, $depth + 1);
                $code_buf->pushStr($var_name . '['. $for_key_name .'] = ' . $for_value_name . ';');
                $code_buf->backIndent()->pushStr('}];');
                break;
        }
    }
}
