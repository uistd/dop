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
 * Class GsonPack java gson库解析json代码生成
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
            $code_buf->pushStr('- (NSMutableDictionary*) jsonEncode');
            $code_buf->pushStr('{')->indent();
            $this->pushImportCode('NSMutableDictionary *result = [NSMutableDictionary new];');
        } else {
            $code_buf->pushStr('- (NSString *)jsonEncode');
            $code_buf->pushStr('{')->indent();
            $this->pushImportCode('NSMutableDictionary *result = [NSMutableDictionary new];');
        }
        $this->null_obj_buf = new StrBuf();
        $code_buf->push($this->null_obj_buf);
        $this->writePropertyLoop($code_buf, $struct);
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('return result;');
        } else {
            $code_buf->pushStr('NSData *json_data = [NSJSONSerialization dataWithJSONObject:result options:kNilOptions error:nil];');
            $code_buf->pushStr('NSString *json_str = [[NSString alloc] initWithData:json_data encoding:NSUTF8StringEncoding];');
            $code_buf->pushStr('return json_str;');
        }
        $code_buf->backIndent()->pushStr('}');
    }

    /**
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    private function writePropertyLoop($code_buf, $struct)
    {
        $tmp_index = 0;
        $all_item = $struct->getAllExtendItem();
        $code_buf->pushStr('writer.beginObject();');
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
                $this->is_null_require = true;
                $code_buf->pushStr('if (nil == self.' . $name . ') {');
                $code_buf->pushIndent('[result setObject:nil_object forKey: @"' . $name . '"];');
                $code_buf->pushStr('} else {')->indent();
            }
            $this->packItemValue($code_buf, 'self.' . $name, '"' . $name . '"', $item, $tmp_index);
            if ($null_check) {
                $code_buf->backIndent()->pushStr('}');
            }
        }
        $code_buf->pushStr('writer.endObject();');
    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        $this->pushImportCode('import java.io.IOException;');
        $this->pushImportCode('import com.google.gson.stream.JsonReader;');
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * JSON字符串解析');
        if ($struct->isSubStruct()) {
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public void gsonRead(JsonReader reader) throws IOException {');
            $code_buf->indent();
            $this->readPropertyLoop($code_buf, $struct);
        } else {
            $this->pushImportCode('import java.io.StringReader;');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public Boolean gsonRead(String json_str) {');
            $code_buf->indent();
            $code_buf->pushStr('try {')->indent();
            $code_buf->pushStr('JsonReader reader = new JsonReader(new StringReader(json_str));');
            $this->readPropertyLoop($code_buf, $struct);
            $code_buf->pushStr('reader.close();');
            $code_buf->pushStr('return true;');
            $code_buf->backIndent()->pushStr('} catch(IOException e) {');
            $code_buf->pushIndent('return false;');
            $code_buf->pushStr('}');
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
        $code_buf->pushStr('reader.beginObject();');
        $code_buf->pushStr('while (reader.hasNext()) {')->indent();
        $code_buf->pushStr('String s = reader.nextName(); ');
        $code_buf->pushStr('switch (s) {')->indent();
        $tmp_index = 0;
        static $null_check_list = array(
            ItemType::BINARY => true,
            ItemType::MAP => true,
            ItemType::ARR => true,
            ItemType::STRUCT => true,
        );
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $type = $item->getType();
            $code_buf->pushStr('case "' . $name . '":')->indent();
            //List 和 Map 初始化值
            if (ItemType::ARR === $type || ItemType::MAP === $type) {
                $code_buf->pushStr('this.' . $name . ' = new ' . Coder::varType($item, 0, false) . '();');
            }
            //判断null值
            $null_check = isset($null_check_list[$type]);
            if ($null_check) {
                $this->importClass('JsonToken');
                $code_buf->pushStr('if (JsonToken.NULL == reader.peek()) {')->indent();
                $code_buf->pushStr('reader.skipValue();');
                if (ItemType::BINARY === $type) {
                    $code_buf->pushStr('this.' . $name . ' = new byte[0];');
                }
                $code_buf->backIndent()->pushStr('} else {')->indent();
            }
            $this->unpackItemValue($code_buf, 'this.' . $name, $item, $tmp_index);
            if ($null_check) {
                $code_buf->backIndent()->pushStr('}');
            }
            $code_buf->pushStr('break;')->backIndent();
        }
        $code_buf->pushStr('default:')->indent();
        $code_buf->pushStr('reader.skipValue();');
        $code_buf->pushStr('break;')->backIndent();
        $code_buf->backIndent()->pushStr('}');
        $code_buf->backIndent()->pushStr('}');
        $code_buf->pushStr('reader.endObject();');
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $value_name 变量名
     * @param string $name 属性名
     * @param Item $item 节点对象
     * @param int $tmp_index 临时变量
     * @throws Exception
     */
    private function packItemValue($code_buf, $value_name, $name, $item, &$tmp_index = 0)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
                $code = self::nsNumberCode($item);
                break;
            case ItemType::STRING:
                $code = $value_name;
                break;
            case ItemType::BINARY:
                $code = '['.$value_name . ' base64EncodedStringWithOptions:0]';
                break;
            case ItemType::BOOL:
                $code = '[NSNumber numberWithBool:'. $value_name .']';
                break;
            case ItemType::DOUBLE:
                $code = '[NSNumber numberWithDouble:'. $value_name .']';
                break;
            case ItemType::FLOAT:
                $code = '[NSNumber numberWithFloat:'. $value_name .']';
                break;
            case ItemType::STRUCT:
                $code = '['.$value_name.' jsonEncode]';
                break;
        }
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
            $code = 'Char';
        } elseif (2 === $byte) {
            $code = 'Short';
        } elseif (4 === $byte) {
            $code = 'Int';
        } else {
            $code = 'LongLong';
        }
        if ($item->isUnsigned()) {
            $code = 'Unsigned' . $code;
        }
        return '[NSNumber numberWith' . $code . ']';
    }

    /**
     * 解出数据
     * @param CodeBuf $code_buf 生成代码缓存
     * @param string $var_name 值变量名
     * @param Item $item 节点对象
     * @param int $tmp_index
     * @param int $depth 递归深度
     * @throws Exception
     */
    private function unpackItemValue($code_buf, $var_name, $item, &$tmp_index, $depth = 0)
    {

    }
}
