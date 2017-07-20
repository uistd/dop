<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\build\StrBuf;
use ffan\dop\Exception;
use ffan\dop\protocol\IntItem;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;

/**
 * Class json json代码生成
 * @package ffan\dop\coder\objc
 */
class JsonPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;

    /**
     * @var bool 是否需要null
     */
    private $is_null_require;

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
        $code_buf->pushStr(' * 转成JSON字符串');
        $code_buf->pushStr(' */');
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('- (NSMutableDictionary*) jsonEncode {');
            $code_buf->indent();
            $code_buf->pushStr('NSMutableDictionary *result = [NSMutableDictionary new];');
        } else {
            $code_buf->pushStr('- (NSString *)jsonEncode {');
            $code_buf->indent();
            $code_buf->pushStr('NSMutableDictionary *result = [NSMutableDictionary new];');
        }
        $this->null_obj_buf = new StrBuf();
        $code_buf->push($this->null_obj_buf);
        $this->writePropertyLoop($code_buf, $struct);
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('return result;');
        } else {
            $code_buf->pushStr('NSError *error = nil;');
            $code_buf->pushStr('NSData *json_data = [NSJSONSerialization dataWithJSONObject:result options:0 error:&error];');
            $code_buf->pushStr('if (nil != error || nil == json_data) {');
            $code_buf->pushIndent('return @"";');
            $code_buf->pushStr('}');
            $code_buf->pushStr('NSString *json_str = [[NSString alloc] initWithData:json_data encoding:NSUTF8StringEncoding];');
            $code_buf->pushStr('return json_str;');
        }
        $code_buf->backIndent()->pushStr('}');
        if ($this->is_null_require) {
            $this->null_obj_buf->pushStr('NSNull *nil_object = [NSNull new];');
        }
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
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $type = $item->getType();
            $null_check = isset($null_check_list[$type]);
            if ($null_check) {
                //如果忽略null值
                if ($this->coder->isJsonIgnoreNull()) {
                    $code_buf->pushStr('if (nil != self.' . $name . ') {')->indent();
                } else {
                    $this->is_null_require = true;
                    $code_buf->pushStr('if (nil == self.' . $name . ') {');
                    $code_buf->pushIndent('result[@"' . $name . '"] = nil_object;');
                    $code_buf->pushStr('} else {')->indent();
                }
                $this->packItemValue($code_buf, 'self.' . $name, '@"' . $name . '"', $item);
                $code_buf->backIndent()->pushStr('}');
            } else {
                $this->packItemValue($code_buf, 'self.' . $name, '@"' . $name . '"', $item);
            }
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
        $code_buf->pushStr(' * JSON 解析');
        $code_buf->pushStr(' */');
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('- (void)jsonDecode:(NSDictionary*) json_dict');
            $code_buf->pushStr('{')->indent();
        } else {
            $code_buf->pushStr('- (BOOL)jsonDecode:(NSString*)json_str');
            $code_buf->pushStr('{')->indent();
            $code_buf->pushStr('NSData* json_data = [json_str dataUsingEncoding:NSUTF8StringEncoding];');
            $code_buf->pushStr('NSError *error;');
            $code_buf->pushStr('NSDictionary *json_dict = [NSJSONSerialization JSONObjectWithData:json_data options:NSJSONReadingAllowFragments error:&error];');
            $code_buf->pushStr('if (nil == json_dict) {');
            $code_buf->pushIndent('return NO;');
            $code_buf->pushStr('}');
        }
        $this->readPropertyLoop($code_buf, $struct);
        if (!$struct->isSubStruct()) {
            $code_buf->pushStr('return YES;');
        }
        $code_buf->backIndent()->pushStr('}');
    }

    /**
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    private function readPropertyLoop($code_buf, $struct)
    {
        $all_item = $struct->getAllExtendItem();
        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $item_type = $item->getType();
            $ns_type = self::nsTypeName($item_type);
            $value = '[FFANDOPUtils jsonRead' . $ns_type . ':[json_dict valueForKey:@"' . $name . '"]]';
            $this->unpackItemValue($code_buf, 'self.' . $name, $value, $item, $tmp_index);
        }
    }

    /**
     * 返回 objective-c 的类型
     * @param int $item_type
     * @return string
     */
    private static function nsTypeName($item_type)
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
                $type_name = 'NSNull';
        }
        return $type_name;
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $value_name 变量名
     * @param string $name 属性名
     * @param Item $item 节点对象
     * @throws Exception
     */
    private function packItemValue($code_buf, $value_name, $name, $item)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
                /** @var IntItem $item */
                $code = '@(' . $value_name . ')';
                break;
            case ItemType::STRING:
                $code = $value_name;
                break;
            case ItemType::BINARY:
                $code = '[' . $value_name . ' base64EncodedStringWithOptions:0]';
                break;
            case ItemType::BOOL:
                $code = '@(' . $value_name . ')';
                break;
            case ItemType::DOUBLE:
                $code = '@(' . $value_name . ')';
                break;
            case ItemType::FLOAT:
                $code = '@(' . $value_name . ')';
                break;
            case ItemType::STRUCT:
                $code = '[' . $value_name . ' jsonEncode]';
                break;
            case ItemType::ARR:
            case ItemType::MAP:
                $code = $value_name;
                break;
            default:
                throw new Exception('Unknown type');
        }
        $code_buf->push('result[' . $name . '] = ' . $code . ';');
    }

    /**
     * 生成int to nsNumber转换代码
     * @param IntItem $item
     * @return string
     */
    private static function nsNumberCode($item)
    {
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
                if (0 == $depth) {
                    /** @var IntItem $item */
                    $func_name = self::nsNumberCode($item);
                    $code_buf->pushStr($var_name . ' = [' . $value . ' ' . $func_name . '];');
                } else {
                    $code_buf->pushStr($var_name . ' = ' . $value . ';');
                }
                break;
            case ItemType::BOOL:
                $code_buf->pushStr($var_name . ' = [' . $value . ' boolValue];');
                break;
            case ItemType::FLOAT:
                $code_buf->pushStr($var_name . ' = [' . $value . ' floatValue];');
                break;
            case ItemType::DOUBLE:
                $code_buf->pushStr($var_name . ' = [' . $value . ' doubleValue];');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $sub_struct = $item->getStruct();
                $class_name = $this->coder->makeClassName($sub_struct);
                $code_buf->pushStr($var_name . ' = [' . $class_name . ' new];');
                $code_buf->pushStr('[' . $var_name . ' jsonDecode:' . $value . '];');
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
                $this->unpackItemValue($code_buf, $for_value, '[FFANDOPUtils jsonRead' . $ns_type . ':' . $for_var . ']', $sub_item, $tmp_index, $depth + 1);
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
                $code_buf->pushStr($key_type . ' ' . $for_key_name . ' = [FFANDOPUtils jsonRead' . $key_ns_type . ':' . $for_key_var . '];');
                $code_buf->pushStr($value_type . ' ' . $for_value_name . ';');
                $this->unpackItemValue($code_buf, $for_value_name, '[FFANDOPUtils jsonRead' . $value_ns_type . ':' . $for_value_var . ']', $value_item, $tmp_index, $depth + 1);
                $code_buf->pushStr($var_name . '['. $for_key_name .'] = ' . $for_value_name . ';');
                $code_buf->backIndent()->pushStr('}];');
                break;
        }
    }
}
