<?php

namespace ffan\dop\coder\java;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\Exception;
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
     * 变量类型
     */
    const JSON_OBJECT = 1;
    const JSON_ARRAY = 2;
    const JSON_NORMAL = 3;

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
        $code_buf->pushStr(' * 转成Json字符串');
        if ($struct->isSubStruct()) {
            $code_buf->pushStr(' * @param JsonWriter writer');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public void gsonPack(writer) {');
            $code_buf->indent();
            $this->writePropertyLoop($code_buf, $struct);
        } else {
            $code_buf->pushStr(' * @return String');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public String gsonPack() {');
            $code_buf->indent();
            $code_buf->pushStr('JsonWriter writer = new JsonWriter(new StringWriter());');
            $this->writePropertyLoop($code_buf, $struct);
            $code_buf->pushStr('writer.close();');
            $code_buf->pushStr('return writer.toString();');
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
                $code_buf->pushIndent('} else {')->indent();
            }
            self::packItemValue($code_buf, 'this.' . $name, $name, $item, $tmp_index);
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
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * json串反序列化');
        if ($struct->isSubStruct()) {
            $code_buf->pushStr(' * @param JsonReader reader');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public void gsonRead(reader) {');
            $code_buf->indent();
            $this->readPropertyLoop($code_buf, $struct);
        } else {
            $code_buf->pushStr(' * @param String json_str');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('public void gsonRead(json_str) {');
            $code_buf->indent();
            $code_buf->pushStr('JsonReader reader = new JsonReader(new StringReader(json_str));');
            $code_buf->pushStr('reader.beginObject();');
            $this->readPropertyLoop($code_buf, $struct);
            $code_buf->pushStr('return writer.toString();');
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
            $code_buf->pushStr('case "' . $name . '":')->indent();
            self::unpackItemValue($code_buf, 'this.' . $name, $item, $tmp_index);
            $code_buf->pushStr('break;')->backIndent();
        }
        $code_buf->pushStr('default:')->indent();
        $code_buf->pushStr('reader.skipValue();');
        $code_buf->pushStr('break;')->backIndent();
        $code_buf->backIndent()->pushStr('}')->pushStr('reader.endObject();');
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
    private static function packItemValue($code_buf, $value_name, $name, $item, &$tmp_index = 0)
    {
        $item_type = $item->getType();
        if (!empty($name)) {
            $code_buf->pushStr('writer.name("' . $name . '")');
        }
        if (ItemType::ARR === $item_type) {
            /** @var ListItem $item */
            $sub_item = $item->getItem();
            $code_buf->pushStr('writer.beginArray();');
            $for_type = Coder::varType($sub_item);
            $for_var_name = self::varName($tmp_index++, 'item');
            $code_buf->pushStr('for (' . $for_type . ' ' . $for_var_name . ' : ' . $value_name . ') {');
            $code_buf->indent();
            self::packItemValue($code_buf, $for_var_name, '', $sub_item, $tmp_index);
            $code_buf->backIndent()->pushStr('}');
            $code_buf->pushStr('writer.endArray();');
        } elseif (ItemType::MAP === $item_type) {
            /** @var MapItem $item */
            $key_item = $item->getKeyItem();
            $value_item = $item->getValueItem();
            $for_type = Coder::varType($item);
            $for_var_name = self::varName($tmp_index++, 'item');
            $code_buf->pushStr('writer.beginObject();');
            $code_buf->pushStr('for (Map.Entry' . $for_type . ' ' . $for_var_name . ' : ' . $value_name . '.entrySet()) {');
            $code_buf->indent();
            $key_var_name = self::varName($tmp_index++, 'key');
            $value_var_name = self::varName($tmp_index++, 'value');
            if (ItemType::STRING === $key_item->getType()) {
                $code_buf->pushStr(Coder::varType($key_item) . ' ' . $key_var_name . ' = ' . $for_var_name . '.getKey();');
            } else {
                $code_buf->pushStr(Coder::varType($key_item) . ' ' . $key_var_name . ' = new String(' . $for_var_name . '.getKey());');
            }
            $code_buf->pushStr(Coder::varType($value_item) . ' ' . $value_var_name . ' = ' . $for_var_name . '.getValue();');
            self::packItemValue($code_buf, $value_var_name, $key_var_name, $value_item, $tmp_index);
            $code_buf->backIndent()->pushStr('}');
            $code_buf->pushStr('writer.endObject();');
        } elseif (ItemType::STRUCT === $item_type) {
            $code_buf->pushStr($value_name . '.gsonPack(writer);');
        } elseif (ItemType::BINARY === $item_type) {
            $code_buf->pushStr('writer.value(Base64.getEncoder().encodeToString(' . $value_name . '));');
        } else {
            $code_buf->pushStr('writer.value(' . $value_name . ')');
        }
    }

    /**
     * 解出数据
     * @param CodeBuf $code_buf 生成代码缓存
     * @param string $var_name 值变量名
     * @param Item $item 节点对象
     * @param int $tmp_index
     * @throws Exception
     */
    private static function unpackItemValue($code_buf, $var_name, $item, &$tmp_index)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
                $code_buf->pushStr($var_name . ' = reader.nextInt();');
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $code_buf->pushStr($var_name . ' = reader.nextDouble();');
                break;
            case ItemType::STRING:
                $code_buf->pushStr($var_name . ' = reader.nextString();');
                break;
            case ItemType::BINARY:
                $code_buf->pushStr($var_name . ' = Base64.getDecoder().decode(reader.nextString());');
                break;
            //对象
            case ItemType::STRUCT:

                break;
            //枚举数组
            case ItemType::ARR:

                break;
            //关联数组
            case ItemType::MAP:

                break;
            default:
                throw new Exception('Unknown type:' . $item_type);
        }
    }
}