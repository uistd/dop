<?php

namespace ffan\dop\coder\js;

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
 * Class ArrayPack
 * @package ffan\dop\coder\js
 */
class ArrayPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;

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
        $code_buf->pushStr(' * 转成数组');
        $code_buf->pushStr(' * @return {Object}');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('arrayPack: function()');
        $code_buf->pushStr('{');
        $code_buf->indentIncrease();
        $code_buf->pushStr('var result = {};');
        $all_item = $struct->getAllExtendItem();
        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {

            self::packItemValue($code_buf, 'this.' . $name, "result['" . $name . "']", $item, $tmp_index);
        }
        $code_buf->pushStr('return result;');
        $code_buf->indentDecrease()->pushStr('},');
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
        $code_buf->pushStr(' * 对象初始化');
        $code_buf->pushStr(' * @param {Object} data');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('arrayUnpack: function(data)');
        $code_buf->pushStr('{');
        $code_buf->indentIncrease();
        $all_item = $struct->getAllExtendItem();
        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            self::unpackItemValue($code_buf, 'this.' . $name, 'data', $item, 0, $name, $tmp_index);
        }
        $code_buf->indentDecrease()->pushStr('},');
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $var_name 变量名
     * @param string $result_var 保存结果变量名
     * @param Item $item 节点对象
     * @param int $tmp_index 临时变量索引
     * @param int $depth 深度
     * @throws Exception
     */
    private static function packItemValue($code_buf, $var_name, $result_var, $item, &$tmp_index, $depth = 0)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
                $code_buf->pushStr($result_var . ' = dopBase.intVal(' . $var_name . ');');
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $code_buf->pushStr($result_var . ' = dopBase.floatVal(' . $var_name . ');');
                break;
            case ItemType::STRING:
            case ItemType::BINARY:
                $code_buf->pushStr($result_var . ' = dopBase.strVal(' . $var_name . ');');
                break;
            case ItemType::ARR:
                $result_var_name = tmp_var_name($tmp_index++, 'tmp_arr');
                $code_buf->pushStr('var ' . $result_var_name . ' = [];');
                self::packArrayCheckCode($code_buf, $var_name, $depth);
                $for_var_name = tmp_var_name($tmp_index++, 'item');
                $for_index_name = tmp_var_name($tmp_index++, 'i');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->pushStr('for (var ' . $for_index_name . ' = 0; ' . $for_index_name . ' < ' . $var_name . '.length; ' . $for_index_name . '++) {');
                $code_buf->indentIncrease();
                self::packItemValue($code_buf, $var_name . '[' . $for_index_name . ']', 'var ' . $for_var_name, $sub_item, $tmp_index, $depth + 1);
                $code_buf->pushStr($result_var_name . '.push(' . $for_var_name . ');');
                $code_buf->indentDecrease()->pushStr('}');
                if (0 === $depth) {
                    $code_buf->indentDecrease()->pushStr('}');
                }
                $code_buf->pushStr($result_var . ' = ' . $result_var_name . ';');
                break;
            case ItemType::MAP:
                $result_var_name = tmp_var_name($tmp_index++, 'tmp_' . $item->getName());
                $code_buf->pushStr($result_var_name . ' = {};');
                self::packArrayCheckCode($code_buf, $var_name, $depth);
                $key_var_name = tmp_var_name($tmp_index++, 'key');
                $for_var_name = tmp_var_name($tmp_index++, 'item');
                $for_index_name = tmp_var_name($tmp_index++, 'i');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->pushStr('for (var ' . $$for_index_name . ' in $' . $var_name . ') {');
                $code_buf->indentIncrease();
                self::packItemValue($code_buf, $for_var_name, $for_var_name, $value_item, $tmp_index, $depth + 1);
                self::packItemValue($code_buf, $key_var_name, $key_var_name, $key_item, $tmp_index, $depth + 1);
                $code_buf->pushStr($result_var_name . '[' . $key_var_name . '] = ' . $for_var_name . ';');
                $code_buf->indentDecrease()->pushStr('}');
                if (0 === $depth) {
                    $code_buf->indentDecrease()->pushStr('}');
                }
                $code_buf->pushStr($result_var . ' = ' . $result_var_name . ';');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                if (0 === $depth) {
                    $code_buf->pushStr('if ("function" === typeof ' . $var_name . '["arrayPack"]) {');
                    $code_buf->pushIndent($result_var . ' = ' . $var_name . '.arrayPack();');
                    $code_buf->pushStr('} else {');
                    $code_buf->pushIndent($result_var . ' = {};');
                    $code_buf->pushStr('}');
                } else {
                    $code_buf->pushStr('if ("function" !== typeof ' . $var_name . '["arrayPack"]) {');
                    $code_buf->pushIndent('continue;');
                    $code_buf->pushStr('}');
                    $code_buf->pushStr($result_var . ' = ' . $var_name . '.arrayPack();');
                }
                break;
            default:
                throw new Exception('Unknown type:' . $item_type);

        }
    }

    /**
     * 生成判断数组的代码
     * @param CodeBuf $code_buf
     * @param string $var_name
     * @param int $depth
     */
    private static function packArrayCheckCode($code_buf, $var_name, $depth)
    {
        if (0 === $depth) {
            $code_buf->pushStr('if (dopBase.isArray(' . $var_name . ')) {');
            $code_buf->indentIncrease();
        } else {
            $code_buf->pushStr('if (dopBase.isArray(' . $var_name . ')) {');
            $code_buf->pushIndent('continue;');
            $code_buf->pushStr('}');
        }
    }

    /**
     * 解出数据
     * @param CodeBuf $code_buf 生成代码缓存
     * @param string $var_name 值变量名
     * @param string $data_name 数据变量名
     * @param Item $item 节点对象
     * @param int $depth 深度
     * @param string $key_name 键名
     * @param int $tmp_index
     * @throws Exception
     */
    private static function unpackItemValue($code_buf, $var_name, $data_name, $item, $depth = 0, $key_name = null, &$tmp_index = 0)
    {
        $item_type = $item->getType();
        if ($key_name) {
            $isset_check = true;
            $data_value = $data_name . '[\'' . $key_name . '\']';
        } else {
            $isset_check = false;
            $key_name = $var_name;
            $data_value = $data_name;
        }
        //是否需要判断值是否是数组
        $array_type_check = (ItemType::ARR === $item_type || ItemType::MAP === $item_type || ItemType::STRUCT === $item_type);
        if ($isset_check && $array_type_check) {
            $code_buf->pushStr('if (dopBase.isset(' . $data_value . ') && dopBase.isArray(' . $data_value . ')) {');
            $code_buf->indentIncrease();
        } elseif ($isset_check) {
            $code_buf->pushStr('if (dopBase.isset(' . $data_value . ')) {');
            $code_buf->indentIncrease();
        } //如果只用判断是否为数组，不为数组就continue
        elseif ($array_type_check) {
            $code_buf->pushStr('if (!dopBase.isArray(' . $data_value . ')) {');
            $code_buf->pushIndent('continue;');
            $code_buf->pushStr('}');
        }
        switch ($item_type) {
            case ItemType::INT:
                $code_buf->pushStr($var_name . ' = dopBase.intVal(' . $data_value . ');');
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $code_buf->pushStr($var_name . ' = dopBase.floatVal(' . $data_value . ');');
                break;
            case ItemType::STRING:
            case ItemType::BINARY:
                $code_buf->pushStr($var_name . ' = dopBase.strVal(' . $data_value . ');');
                break;
            //对象
            case ItemType::STRUCT:
                $tmp_var_name = tmp_var_name($key_name, 'struct');
                /** @var StructItem $item */
                $code_buf->pushStr($tmp_var_name . ' = new ' . $item->getStructName() . '();');
                $code_buf->pushStr($tmp_var_name . '.arrayUnpack(' . $data_value . ');');
                $code_buf->pushStr($var_name . ' = ' . $tmp_var_name . ';');
                break;
            //枚举数组
            case ItemType::ARR:
                //循环变量
                $for_var_name = tmp_var_name($tmp_index++, 'item');
                //临时结果变量
                $result_var_name = tmp_var_name($tmp_index++, 'result');
                $for_index_name = tmp_var_name($tmp_index++, 'i');
                $code_buf->pushStr('var '. $result_var_name . ' = [];');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->pushStr('for (var ' . $for_index_name . ' = 0; ' . $for_index_name . ' < ' . $data_value . '.length; ++' . $for_index_name . ') {');
                $code_buf->pushStr('var ' . $for_var_name . ' = ' . $data_value . '[' . $for_index_name . ']');
                $code_buf->indentIncrease();
                self::unpackItemValue($code_buf, $for_var_name, $for_var_name, $sub_item, $depth + 1);
                $code_buf->pushStr($result_var_name . '.push(' . $for_var_name . ');');
                $code_buf->indentDecrease();
                $code_buf->pushStr('}');
                $code_buf->pushStr($var_name . ' = ' . $result_var_name . ';');
                break;
            //关联数组
            case ItemType::MAP:
                //循环键名
                $key_var_name = tmp_var_name($tmp_index++, 'key');
                //循环变量
                $for_var_name = tmp_var_name($tmp_index++, 'item');
                //循环下标
                $for_index_name = tmp_var_name($tmp_index++, 'i');
                //临时结果变量
                $result_var_name = tmp_var_name($tmp_index++, 'result');
                $code_buf->pushStr($result_var_name . ' = {};');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->pushStr('for (var ' . $for_index_name . ' in ' . $data_value . ') {');
                $code_buf->pushStr('var ' . $for_var_name . ' = ' . $data_value . '[' . $for_index_name . '];');
                $code_buf->indentIncrease();
                self::unpackItemValue($code_buf, $key_var_name, $key_var_name, $key_item, $depth + 1);
                self::unpackItemValue($code_buf, $for_var_name, $for_var_name, $value_item, $depth + 1);
                $code_buf->pushStr($result_var_name . '[' . $key_var_name . '] = ' . $for_var_name . ';');
                $code_buf->indentDecrease();
                $code_buf->pushStr('}');
                $code_buf->pushStr($var_name . ' = ' . $result_var_name . ';');
                break;
            default:
                throw new Exception('Unknown type:' . $item_type);
        }
        if ($isset_check) {
            $code_buf->indentDecrease();
            $code_buf->pushStr('}');
        }
    }
}