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
 * Class GsonPack java gson库解析json代码生成
 * @package ffan\dop\coder\java
 */
class GsonPack extends PackerBase
{
    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        $this->pushImportCode('import java.io.IOException;');
        $this->pushImportCode('import com.google.gson.stream.JsonWriter;');
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 转成JSON字符串');
        if ($struct->isSubStruct()) {
            $code_buf->pushStr(' * @param JsonWriter writer');
            $code_buf->pushStr(' * @throws IOException');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public void gsonWrite(JsonWriter writer) throws IOException {');
            $code_buf->indent();
            $code_buf->pushStr('try {')->indent();
            $this->writePropertyLoop($code_buf, $struct);
            $code_buf->backIndent()->pushStr('} catch(IOException e) {');
            $code_buf->pushIndent('throw e;');
            $code_buf->pushStr('}');
        } else {
            $this->pushImportCode('import java.io.StringWriter;');
            $code_buf->pushStr(' * @return String');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public String gsonWrite() {');
            $code_buf->indent();
            $code_buf->pushStr('try {')->indent();
            $code_buf->pushStr('JsonWriter writer = new JsonWriter(new StringWriter());');
            $this->writePropertyLoop($code_buf, $struct);
            $code_buf->pushStr('writer.close();');
            $code_buf->pushStr('return writer.toString();');
            $code_buf->backIndent()->pushStr('} catch(IOException e) {');
            $code_buf->pushIndent('return "null";');
            $code_buf->pushStr('}');
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
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $type = $item->getType();
            $null_check = ItemType::INT !== $type && ItemType::FLOAT !== $type && ItemType::DOUBLE !== $type;
            if ($null_check) {
                $code_buf->pushStr('if (null == this.' . $name . ') {');
                $code_buf->pushIndent('writer.name("' . $name . '" ).nullValue();');
                $code_buf->pushStr('} else {')->indent();
            }
            $this->packItemValue($code_buf, 'this.' . $name, '"' . $name . '"', $item, $tmp_index);
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
            $code_buf->pushStr(' * @param reader');
            $code_buf->pushStr(' * @throws IOException');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public void gsonRead(JsonReader reader) throws IOException {');
            $code_buf->indent();
            $code_buf->pushStr('try {')->indent();
            $this->readPropertyLoop($code_buf, $struct);
            $code_buf->backIndent()->pushStr('} catch(IOException e) {');
            $code_buf->pushIndent('throw e;');
            $code_buf->pushStr('}');
        } else {
            $this->pushImportCode('import java.io.StringReader;');
            $code_buf->pushStr(' * @param json_str');
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
            $null_check = ItemType::INT !== $type && ItemType::FLOAT !== $type && ItemType::DOUBLE !== $type;
            if ($null_check) {
                $this->importClass('JsonToken');
                $code_buf->pushStr('if (JsonToken.NULL == reader.peek()) {')->indent();
                $code_buf->pushStr('reader.skipValue();');
                if (ItemType::STRING === $type) {
                    $code_buf->pushStr('this.' . $name . ' = "";');
                } elseif (ItemType::BINARY === $type) {
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
     *
     */
    private function makeInitValue()
    {

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
        if (!empty($name)) {
            $code_buf->pushStr('writer.name(' . $name . ');');
        }
        if (ItemType::ARR === $item_type) {
            /** @var ListItem $item */
            $sub_item = $item->getItem();
            $code_buf->pushStr('writer.beginArray();');
            $for_type = Coder::varType($sub_item);
            $for_var_name = self::varName($tmp_index++, 'item');
            $code_buf->pushStr('for (' . $for_type . ' ' . $for_var_name . ' : ' . $value_name . ') {');
            $code_buf->indent();
            $this->packItemValue($code_buf, $for_var_name, '', $sub_item, $tmp_index);
            $code_buf->backIndent()->pushStr('}');
            $code_buf->pushStr('writer.endArray();');
        } elseif (ItemType::MAP === $item_type) {
            /** @var MapItem $item */
            $key_item = $item->getKeyItem();
            $value_item = $item->getValueItem();
            //这里的Map<Int, Stint> 要替换为Entry<Int, String>
            $for_type = substr(Coder::varType($item), 3);
            $for_var_name = self::varName($tmp_index++, 'item');
            $code_buf->pushStr('writer.beginObject();');
            $code_buf->pushStr('for (Map.Entry' . $for_type . ' ' . $for_var_name . ' : ' . $value_name . '.entrySet()) {');
            $code_buf->indent();
            $key_var_name = self::varName($tmp_index++, 'key');
            $value_var_name = self::varName($tmp_index++, 'value');
            if (ItemType::STRING === $key_item->getType()) {
                $code_buf->pushStr('String ' . $key_var_name . ' = ' . $for_var_name . '.getKey();');
            } else {
                $code_buf->pushStr('String ' . $key_var_name . ' = ' . $for_var_name . '.getKey().toString();');
            }
            $code_buf->pushStr(Coder::varType($value_item) . ' ' . $value_var_name . ' = ' . $for_var_name . '.getValue();');
            $this->packItemValue($code_buf, $value_var_name, $key_var_name, $value_item, $tmp_index);
            $code_buf->backIndent()->pushStr('}');
            $code_buf->pushStr('writer.endObject();');
        } elseif (ItemType::STRUCT === $item_type) {
            $code_buf->pushStr($value_name . '.gsonWrite(writer);');
        } elseif (ItemType::BINARY === $item_type) {
            $this->importClass('Base64');
            $code_buf->pushStr('writer.value(Base64.getEncoder().encodeToString(' . $value_name . '));');
        } else {
            $code_buf->pushStr('writer.value(' . $value_name . ');');
        }
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
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
                /** @var IntItem $item */
                $bytes = $item->getByte();
                //因为java不支持unsigned 所以 升位 表示。 char 升 short, short升int int升long
                if ($item->isUnsigned()) {
                    $bytes <<= 1;
                }
                if (1 === $bytes) {
                    $code_buf->pushStr($var_name . ' = (byte)reader.nextInt();');
                } elseif (2 === $bytes) {
                    $code_buf->pushStr($var_name . ' = (short)reader.nextInt();');
                } elseif (8 === $bytes) {
                    $code_buf->pushStr($var_name . ' = reader.nextLong();');
                } else {
                    $code_buf->pushStr($var_name . ' = reader.nextInt();');
                }
                break;
            case ItemType::FLOAT:
                $code_buf->pushStr($var_name . ' = (float)reader.nextDouble();');
                break;
            case ItemType::DOUBLE:
                $code_buf->pushStr($var_name . ' = reader.nextDouble();');
                break;
            case ItemType::STRING:
                $code_buf->pushStr($var_name . ' = reader.nextString();');
                break;
            case ItemType::BINARY:
                $this->importClass('Base64');
                $code_buf->pushStr($var_name . ' = Base64.getDecoder().decode(reader.nextString());');
                break;
            //对象
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $sub_struct = $item->getStruct();
                $code_buf->pushStr($var_name . ' = new ' . $sub_struct->getClassName() . '();');
                $code_buf->pushStr($var_name . '.gsonRead(reader);');

                break;
            //枚举数组
            case ItemType::ARR:
                $this->importClass('ArrayList');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $list_type = Coder::varType($item, 0, false);
                $sub_item_type = Coder::varType($sub_item, 0, false);
                $tmp_sub_value = self::varName($tmp_index++, 'item');
                if ($depth > 0) {
                    $code_buf->pushStr($var_name . ' = new ' . $list_type . '();');
                }
                $code_buf->pushStr($sub_item_type . ' ' . $tmp_sub_value . ';');
                $code_buf->pushStr('reader.beginArray();');
                $code_buf->pushStr('while (reader.hasNext()) {')->indent();
                $this->importClass('JsonToken');
                $code_buf->pushStr('if (JsonToken.NULL == reader.peek()) {')->indent();
                $code_buf->pushStr('reader.skipValue();');
                $code_buf->pushStr('continue;');
                $code_buf->backIndent()->pushStr('}');
                $this->unpackItemValue($code_buf, $tmp_sub_value, $sub_item, $tmp_index, $depth + 1);
                $code_buf->pushStr($var_name . '.add(' . $tmp_sub_value . ');');
                $code_buf->backIndent()->pushStr('}');
                $code_buf->pushStr('reader.endArray();');
                break;
            //关联数组
            case ItemType::MAP:
                $this->importClass('HashMap');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $map_type = Coder::varType($item, 0, false);
                $code_buf->pushStr($var_name . ' = new ' . $map_type . '();');
                $key_item_type = Coder::varType($key_item, 0, false);
                $value_item_type = Coder::varType($value_item, 0, false);
                $tmp_key = self::varName($tmp_index++, 'key');
                $tmp_value = self::varName($tmp_index++, 'value');
                $code_buf->pushStr($key_item_type . ' ' . $tmp_key . ';');
                $code_buf->pushStr($value_item_type . ' ' . $tmp_value . ';');
                $code_buf->pushStr('reader.beginObject();');
                $code_buf->pushStr('while (reader.hasNext()) {')->indent();
                $this->importClass('JsonToken');
                $code_buf->pushStr('if (JsonToken.NULL == reader.peek()) {')->indent();
                $code_buf->pushStr('reader.skipValue();');
                $code_buf->pushStr('continue;');
                $code_buf->backIndent()->pushStr('}');
                $this->unpackItemValue($code_buf, $tmp_key, $key_item, $tmp_index, $depth + 1);
                $this->unpackItemValue($code_buf, $tmp_value, $value_item, $tmp_index, $depth + 1);
                $code_buf->pushStr($var_name . '.put(' . $tmp_key . ', ' . $tmp_value . ');');
                $code_buf->backIndent()->pushStr('}');
                $code_buf->pushStr('reader.endObject();');
                break;
            default:
                throw new Exception('Unknown type:' . $item_type);
        }
    }

    /**
     * 生成import代码
     * @param string $class_name
     */
    private function importClass($class_name)
    {
        $class_map = array(
            'JsonToken' => 'com.google.gson.stream.JsonToken',
            'Base64' => 'java.util.Base64',
            'HashMap' => 'java.util.HashMap',
            'ArrayList' => 'java.util.ArrayList'
        );
        if (!isset($class_map[$class_name])) {
            return;
        }
        $this->pushImportCode('import ' . $class_map[$class_name] . ';');
    }
}
