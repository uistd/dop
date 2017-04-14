<?php

namespace ffan\dop\php;

use ffan\dop\CodeBuf;
use ffan\dop\DOPException;
use ffan\dop\DOPGenerator;
use ffan\dop\Item;
use ffan\dop\ItemType;
use ffan\dop\ListItem;
use ffan\dop\MapItem;
use ffan\dop\PackInterface;
use ffan\dop\Struct;
use ffan\dop\StructItem;

/**
 * Class ArrayPack 数组打包解包
 * @package ffan\dop\php
 */
class ArrayPack implements PackInterface
{

    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public static function buildPackMethod($struct, $code_buf)
    {

    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public static function buildUnPackMethod($struct, $code_buf)
    {
        $method_name = 'arrayUnpack';
        if (!$code_buf->addMethod($method_name)) {
            return;
        }
        $code_buf->emptyLine();
        $code_buf->push('/**');
        $code_buf->push(' * 对象初始化');
        $code_buf->push(' * @param array $data');
        $code_buf->push(' */');
        $code_buf->push('public function ' . $method_name . '($data)');
        $code_buf->push('{');
        $code_buf->indentIncrease();
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            self::unpackItemValue($code_buf, '$this->' . $name, 'data', $item, 0, $name);
        }
        $code_buf->indentDecrease();
        $code_buf->push('}');
    }

    /**
     * 解出数据
     * @param CodeBuf $code_buf 生成代码缓存
     * @param string $var_name 值变量名
     * @param string $data_name 数据变量名
     * @param Item $item 节点对象
     * @param int $depth 深度
     * @param string $key_name 键名
     * @throws DOPException
     */
    private static function unpackItemValue($code_buf, $var_name, $data_name, $item, $depth = 0, $key_name = null)
    {
        $item_type = $item->getType();
        if ($key_name) {
            $isset_check = true;
            $data_value = $data_name . '[' . $key_name . ']';
        } else {
            $isset_check = false;
            $key_name = $var_name;
            $data_value = $data_name;
        }
        //是否需要判断值是否是数组
        $array_type_check = (ItemType::ARR === $item_type || ItemType::MAP === $item_type || ItemType::STRUCT === $item_type);
        //判断值是否存在
        if ($isset_check) {
            $code_buf->push('if (!isset($' . $data_value . ')) {');
            $code_buf->indentIncrease();
            $code_buf->push('continue;');
            $code_buf->indentDecrease();
            $code_buf->push('}');
        }
        //判断是否是数组
        if ($isset_check && $array_type_check) {
            $code_buf->push('if (!is_array($' . $data_value . ') {');
            $code_buf->indentIncrease();
            $code_buf->indentIncrease();
            $code_buf->push('continue;');
            $code_buf->indentDecrease();
            $code_buf->push('}');
        }
        switch ($item_type) {
            case ItemType::INT:
                $code_buf->push('$' . $var_name . ' = (int)$' . $data_value . ';');
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $code_buf->push('$' . $var_name . ' = (float)$' . $data_value . ';');
                break;
            case ItemType::STRING:
            case ItemType::BINARY:
                $code_buf->push('$' . $var_name . ' = (string)$' . $data_value . ';');
                break;
            //对象
            case ItemType::STRUCT:
                $tmp_var_name = DOPGenerator::tmpVarName($key_name, 'struct');
                /** @var StructItem $item */
                $code_buf->push('$' . $tmp_var_name . ' = new ' . $item->getStructName() . '();');
                $code_buf->push('$' . $tmp_var_name . '->arrayUnpack($' . $data_value . ');');
                $code_buf->push('$' . $var_name . ' = $' . $tmp_var_name . ';');
                break;
            //枚举数组
            case ItemType::ARR:
                //循环变量
                $for_var_name = DOPGenerator::tmpVarName($depth, 'item');
                //临时结果变量
                $result_var_name = DOPGenerator::tmpVarName($depth, 'result');
                $code_buf->push('$' . $result_var_name . ' = array();');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->push('foreach ($' . $data_value . ' as $' . $for_var_name . ') {');
                $code_buf->indentIncrease();
                self::unpackItemValue($code_buf, $for_var_name, $for_var_name, $sub_item, ++$depth);
                $code_buf->push('$' . $result_var_name . '[] = $' . $for_var_name . ';');
                $code_buf->indentDecrease();
                $code_buf->push('}');
                $code_buf->push('$' . $var_name . ' = $' . $result_var_name . ';');
                break;
            //关联数组
            case ItemType::MAP:
                //循环键名
                $key_var_name = DOPGenerator::tmpVarName($depth, 'key');
                //循环变量
                $for_var_name = DOPGenerator::tmpVarName($depth, 'item');
                //临时结果变量
                $result_var_name = DOPGenerator::tmpVarName($depth, 'result');
                $code_buf->push('$' . $result_var_name . ' = array();');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->push('foreach ($' . $data_value . ' as $' . $key_var_name . ' => $' . $for_var_name . ') {');
                $code_buf->indentIncrease();
                self::unpackItemValue($code_buf, $key_var_name, $key_var_name, $key_item, ++$depth);
                self::unpackItemValue($code_buf, $for_var_name, $for_var_name, $value_item, $depth);
                $code_buf->push('$' . $result_var_name . '[$' . $key_var_name . '] = $' . $for_var_name . ';');
                $code_buf->indentDecrease();
                $code_buf->push('}');
                $code_buf->push('$' . $var_name . ' = $' . $result_var_name . ';');
                break;
            default:
                throw new DOPException('Unknown type:' . $item_type);
        }
    }
}