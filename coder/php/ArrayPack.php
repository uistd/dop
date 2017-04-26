<?php

namespace ffan\dop\coder\php;
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
 * @package ffan\dop\coder\php
 */
class ArrayPack extends PackerBase
{
    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->push('/**');
        $code_buf->push(' * 转成数组');
        $code_buf->push(' * @return array');
        $code_buf->push(' */');
        $code_buf->push('public function arrayPack()');
        $code_buf->push('{');
        $code_buf->indentIncrease();
        $code_buf->push('$result = array();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            self::packItemValue($code_buf, 'this->' . $name, "result['" . $name . "']", $item, 0);
        }
        $code_buf->push('return $result;');
        $code_buf->indentDecrease()->push('}');
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
        $code_buf->push('/**');
        $code_buf->push(' * 对象初始化');
        $code_buf->push(' * @param array $data');
        $code_buf->push(' */');
        $code_buf->push('public function arrayUnpack($data)');
        $code_buf->push('{');
        $code_buf->indentIncrease();
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            self::unpackItemValue($code_buf, 'this->' . $name, 'data', $item, 0, $name);
        }
        $code_buf->indentDecrease()->push('}');
    }

    /**
     * 打包一项数据
     * @param CodeBuf $code_buf
     * @param string $var_name 变量名
     * @param string $result_var 保存结果变量名
     * @param Item $item 节点对象
     * @param int $depth 深度
     * @throws Exception
     */
    private static function packItemValue($code_buf, $var_name, $result_var, $item, $depth = 0)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
                self::packItemCode($code_buf, $result_var, $var_name, 'int', $depth);
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                self::packItemCode($code_buf, $result_var, $var_name, 'float', $depth);
                break;
            case ItemType::STRING:
            case ItemType::BINARY:
                self::packItemCode($code_buf, $result_var, $var_name, 'string', $depth);
                break;
            case ItemType::ARR:
                $result_var_name = tmp_var_name($depth, 'tmp_arr');
                $code_buf->push('$' . $result_var_name . ' = array();');
                self::packArrayCheckCode($code_buf, $var_name, $depth);
                $for_var_name = tmp_var_name($depth, 'item');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->push('foreach ($' . $var_name . ' as $' . $for_var_name . ') {');
                $code_buf->indentIncrease();
                self::packItemValue($code_buf, $for_var_name, $result_var_name . '[]', $sub_item, $depth + 1);
                $code_buf->indentDecrease()->push('}');
                if (0 === $depth) {
                    $code_buf->indentDecrease()->push('}');
                }
                $code_buf->push('$' . $result_var . ' = $' . $result_var_name . ';');
                break;
            case ItemType::MAP:
                $result_var_name = tmp_var_name($depth, 'tmp_' . $item->getName());
                $code_buf->push('$' . $result_var_name . ' = array();');
                self::packArrayCheckCode($code_buf, $var_name, $depth);
                $key_var_name = tmp_var_name($depth, 'key');
                $for_var_name = tmp_var_name($depth, 'item');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->push('foreach ($' . $var_name . ' as $' . $key_var_name . ' => $' . $for_var_name . ') {');
                $code_buf->indentIncrease();
                self::packItemValue($code_buf, $for_var_name, $for_var_name, $value_item, $depth + 1);
                self::packItemValue($code_buf, $key_var_name, $key_var_name, $key_item, $depth + 1);
                $code_buf->push('$' . $result_var_name . '[$' . $key_var_name . '] = $' . $for_var_name . ';');
                $code_buf->indentDecrease()->push('}');
                if (0 === $depth) {
                    $code_buf->indentDecrease()->push('}');
                }
                $code_buf->push('$' . $result_var . ' = $' . $result_var_name . ';');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                if (0 === $depth) {
                    $code_buf->push('if ($' . $var_name . ' instanceof ' . $item->getStructName() . ') {');
                    $code_buf->pushIndent('$' . $result_var . ' = $' . $var_name . '->arrayPack();');
                    $code_buf->push('} else {');
                    $code_buf->pushIndent('$' . $result_var . ' = array();');
                    $code_buf->push('}');
                } else {
                    $code_buf->push('if (!$' . $var_name . ' instanceof ' . $item->getStructName() . ') {');
                    $code_buf->pushIndent('continue;');
                    $code_buf->push('}');
                    $code_buf->push('$' . $result_var . ' = $' . $var_name . '->arrayPack();');
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
            $code_buf->push('if (is_array($' . $var_name . ')) {');
            $code_buf->indentIncrease();
        } else {
            $code_buf->push('if (!is_array($' . $var_name . ')) {');
            $code_buf->pushIndent('continue;');
            $code_buf->push('}');
        }
    }

    /**
     * 生成打包一个节点的代码
     * @param CodeBuf $code_buf
     * @param string $result_var
     * @param string $var_name
     * @param string $convert_type 强转类型
     * @param int $depth 深度
     */
    private static function packItemCode($code_buf, $result_var, $var_name, $convert_type, $depth)
    {
        //如果是最外层，要判断值是不是null
        if (0 === $depth) {
            $code_buf->push('$' . $result_var . ' = null === $' . $var_name . ' ?: (' . $convert_type . ')$' . $var_name . ';');
        } else {
            $code_buf->push('$' . $result_var . ' = (' . $convert_type . ')$' . $var_name . ';');
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
     * @throws Exception
     */
    private static function unpackItemValue($code_buf, $var_name, $data_name, $item, $depth = 0, $key_name = null)
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
            $code_buf->push('if (isset($' . $data_value . ') && is_array($' . $data_value . ')) {');
            $code_buf->indentIncrease();
        } elseif ($isset_check) {
            $code_buf->push('if (isset($' . $data_value . ')) {');
            $code_buf->indentIncrease();
        } //如果只用判断是否为数组，不为数组就continue
        elseif ($array_type_check) {
            $code_buf->push('if (!is_array($' . $data_value . ')) {');
            $code_buf->pushIndent('continue;');
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
                $tmp_var_name = tmp_var_name($key_name, 'struct');
                /** @var StructItem $item */
                $code_buf->push('$' . $tmp_var_name . ' = new ' . $item->getStructName() . '();');
                $code_buf->push('$' . $tmp_var_name . '->arrayUnpack($' . $data_value . ');');
                $code_buf->push('$' . $var_name . ' = $' . $tmp_var_name . ';');
                break;
            //枚举数组
            case ItemType::ARR:
                //循环变量
                $for_var_name = tmp_var_name($depth, 'item');
                //临时结果变量
                $result_var_name = tmp_var_name($depth, 'result');
                $code_buf->push('$' . $result_var_name . ' = array();');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->push('foreach ($' . $data_value . ' as $' . $for_var_name . ') {');
                $code_buf->indentIncrease();
                self::unpackItemValue($code_buf, $for_var_name, $for_var_name, $sub_item, $depth + 1);
                $code_buf->push('$' . $result_var_name . '[] = $' . $for_var_name . ';');
                $code_buf->indentDecrease();
                $code_buf->push('}');
                $code_buf->push('$' . $var_name . ' = $' . $result_var_name . ';');
                break;
            //关联数组
            case ItemType::MAP:
                //循环键名
                $key_var_name = tmp_var_name($depth, 'key');
                //循环变量
                $for_var_name = tmp_var_name($depth, 'item');
                //临时结果变量
                $result_var_name = tmp_var_name($depth, 'result');
                $code_buf->push('$' . $result_var_name . ' = array();');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->push('foreach ($' . $data_value . ' as $' . $key_var_name . ' => $' . $for_var_name . ') {');
                $code_buf->indentIncrease();
                self::unpackItemValue($code_buf, $key_var_name, $key_var_name, $key_item, $depth + 1);
                self::unpackItemValue($code_buf, $for_var_name, $for_var_name, $value_item, $depth + 1);
                $code_buf->push('$' . $result_var_name . '[$' . $key_var_name . '] = $' . $for_var_name . ';');
                $code_buf->indentDecrease();
                $code_buf->push('}');
                $code_buf->push('$' . $var_name . ' = $' . $result_var_name . ';');
                break;
            default:
                throw new Exception('Unknown type:' . $item_type);
        }
        if ($isset_check) {
            $code_buf->indentDecrease();
            $code_buf->push('}');
        }
    }
}