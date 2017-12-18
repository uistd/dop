<?php

namespace UiStd\Dop\Coder\Objc;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\PackerBase;
use UiStd\Dop\Build\StrBuf;
use UiStd\Dop\Exception;
use UiStd\Dop\Protocol\IntItem;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\Struct;

/**
 * Class json json代码生成
 * @package UiStd\Dop\Coder\Ojbc
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
     * 获取依赖的packer
     * @return null|array
     */
    public function getRequirePacker()
    {
        return array('dictionary');
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
        if (!$struct->isSubStruct()) {
            $code_buf->pushStr('- (BOOL)jsonDecode:(NSString*)json_str');
            $code_buf->pushStr('{')->indent();
            $code_buf->pushStr('NSData* json_data = [json_str dataUsingEncoding:NSUTF8StringEncoding];');
            $code_buf->pushStr('NSError *error;');
            $code_buf->pushStr('NSDictionary *json_dict = [NSJSONSerialization JSONObjectWithData:json_data options:NSJSONReadingAllowFragments error:&error];');
            $code_buf->pushStr('if (nil == json_dict) {');
            $code_buf->pushIndent('return NO;');
            $code_buf->pushStr('}');
            $code_buf->pushStr('[self dictionaryDecode:json_dict];');
            $code_buf->pushStr('return YES;');
            $code_buf->backIndent()->pushStr('}');
        }
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
}
