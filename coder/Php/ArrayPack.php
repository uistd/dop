<?php

namespace UiStd\Dop\Coder\Php;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\PackerBase;
use UiStd\Dop\Exception;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\ListItem;
use UiStd\Dop\Protocol\MapItem;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Protocol\StructItem;

/**
 * Class ArrayPack
 * @package UiStd\Dop\Coder\Php
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
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 转成数组');
        $code_buf->pushStr(' * @param bool $empty_convert 如果结果为空，是否转成stdClass');
        $code_buf->pushStr(' * @return array|object');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public function arrayPack($empty_convert = false)');
        $code_buf->pushStr('{');
        $code_buf->indent();
        $code_buf->pushStr('$result = array();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $property_name = $this->coder->fixPropertyName($name, $item);
            $value_name = $this->coder->fixOutputName($name, $item);
            self::packItemValue($code_buf, 'this->' . $property_name, "result['" . $value_name . "']", $item, 0);
            $this->itemTrigger($code_buf, $item);
        }
        $code_buf->pushStr('if ($empty_convert && empty($result)) {');
        $code_buf->pushIndent('return new \\stdClass();');
        $code_buf->pushStr('}');
        $code_buf->pushStr('return $result;');
        $code_buf->backIndent()->pushStr('}');
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
        $code_buf->pushStr(' * @param array $data');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public function arrayUnpack(array $data)');
        $code_buf->pushStr('{');
        $code_buf->indent();
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $property_name = $this->coder->fixPropertyName($name, $item);
            $value_name = $this->coder->fixOutputName($name, $item);
            self::unpackItemValue($code_buf, 'this->' . $property_name, 'data', $item, 0, $value_name);
            $this->itemTrigger($code_buf, $item);
        }
        $code_buf->backIndent()->pushStr('}');
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
            case ItemType::BOOL:
                self::packItemCode($code_buf, $result_var, $var_name, 'bool', $depth);
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
                $result_var_name = self::varName($depth, 'tmp_arr');
                self::packArrayCheckCode($code_buf, $var_name, $depth);
                $code_buf->pushStr('$' . $result_var_name . ' = array();');
                $for_var_name = self::varName($depth, 'item');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->pushStr('foreach ($' . $var_name . ' as $' . $for_var_name . ') {');
                $code_buf->indent();
                self::packItemValue($code_buf, $for_var_name, $result_var_name . '[]', $sub_item, $depth + 1);
                $code_buf->backIndent()->pushStr('}');
                $code_buf->pushStr('$' . $result_var . ' = $' . $result_var_name . ';');
                if (0 === $depth) {
                    $code_buf->backIndent()->pushStr('}');
                }
                break;
            case ItemType::MAP:
                $result_var_name = self::varName($depth, 'tmp_' . $item->getName());
                self::packArrayCheckCode($code_buf, $var_name, $depth);
                $code_buf->pushStr('$' . $result_var_name . ' = array();');
                $key_var_name = self::varName($depth, 'key');
                $for_var_name = self::varName($depth, 'item');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->pushStr('foreach ($' . $var_name . ' as $' . $key_var_name . ' => $' . $for_var_name . ') {');
                $code_buf->indent();
                self::packItemValue($code_buf, $for_var_name, $for_var_name, $value_item, $depth + 1);
                self::packItemValue($code_buf, $key_var_name, $key_var_name, $key_item, $depth + 1);
                $code_buf->pushStr('$' . $result_var_name . '[$' . $key_var_name . '] = $' . $for_var_name . ';');
                $code_buf->backIndent()->pushStr('}');
                $code_buf->pushStr('$' . $result_var . ' = $' . $result_var_name . ';');
                if (0 === $depth) {
                    $code_buf->backIndent()->pushStr('}');
                }
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                if (0 === $depth) {
                    $code_buf->pushStr('if (isset($'.$var_name.') && $' . $var_name . ' instanceof ' . $item->getStructName() . ') {');
                    $code_buf->pushIndent('$' . $result_var . ' = $' . $var_name . '->arrayPack($empty_convert);');
                    $code_buf->pushStr('}');
                } else {
                    $code_buf->pushStr('if (!$' . $var_name . ' instanceof ' . $item->getStructName() . ') {');
                    $code_buf->pushIndent('continue;');
                    $code_buf->pushStr('}');
                    $code_buf->pushStr('$' . $result_var . ' = $' . $var_name . '->arrayPack($empty_convert);');
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
            $code_buf->pushStr('if (is_array($' . $var_name . ')) {');
            $code_buf->indent();
        } else {
            $code_buf->pushStr('if (!is_array($' . $var_name . ')) {');
            $code_buf->pushIndent('continue;');
            $code_buf->pushStr('}');
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
            $code_buf->pushStr('if (null !== $' . $var_name . ') {');
            $code_buf->pushIndent('$' . $result_var . ' = (' . $convert_type . ')$' . $var_name . ';');
            $code_buf->pushStr('}');
        } else {
            $code_buf->pushStr('if (null === $' . $var_name . ') {');
            $code_buf->pushIndent('continue;');
            $code_buf->pushStr('}');
            $code_buf->pushStr('$' . $result_var . ' = (' . $convert_type . ')$' . $var_name . ';');
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
            $code_buf->pushStr('if (isset($' . $data_value . ') && is_array($' . $data_value . ')) {');
            $code_buf->indent();
        } elseif ($isset_check) {
            $code_buf->pushStr('if (isset($' . $data_value . ')) {');
            $code_buf->indent();
        } //如果只用判断是否为数组，不为数组就continue
        elseif ($array_type_check) {
            $code_buf->pushStr('if (!is_array($' . $data_value . ')) {');
            $code_buf->pushIndent('continue;');
            $code_buf->pushStr('}');
        }
        switch ($item_type) {
            case ItemType::INT:
                $code_buf->pushStr('$' . $var_name . ' = (int)$' . $data_value . ';');
                break;
            case ItemType::BOOL:
                $code_buf->pushStr('$' . $var_name . ' = (bool)$' . $data_value . ';');
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $code_buf->pushStr('$' . $var_name . ' = (float)$' . $data_value . ';');
                break;
            case ItemType::STRING:
            case ItemType::BINARY:
                $code_buf->pushStr('$' . $var_name . ' = (string)$' . $data_value . ';');
                break;
            //对象
            case ItemType::STRUCT:
                $tmp_var_name = self::varName($key_name, 'struct');
                /** @var StructItem $item */
                $code_buf->pushStr('$' . $tmp_var_name . ' = new ' . $item->getStructName() . '();');
                $code_buf->pushStr('$' . $tmp_var_name . '->arrayUnpack($' . $data_value . ');');
                $code_buf->pushStr('$' . $var_name . ' = $' . $tmp_var_name . ';');
                break;
            //枚举数组
            case ItemType::ARR:
                //循环变量
                $for_var_name = self::varName($depth, 'item');
                //临时结果变量
                $result_var_name = self::varName($depth, 'result');
                $code_buf->pushStr('$' . $result_var_name . ' = array();');
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $code_buf->pushStr('foreach ($' . $data_value . ' as $' . $for_var_name . ') {');
                $code_buf->indent();
                self::unpackItemValue($code_buf, $for_var_name, $for_var_name, $sub_item, $depth + 1);
                $code_buf->pushStr('$' . $result_var_name . '[] = $' . $for_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr('$' . $var_name . ' = $' . $result_var_name . ';');
                break;
            //关联数组
            case ItemType::MAP:
                //循环键名
                $key_var_name = self::varName($depth, 'key');
                //循环变量
                $for_var_name = self::varName($depth, 'item');
                //临时结果变量
                $result_var_name = self::varName($depth, 'result');
                $code_buf->pushStr('$' . $result_var_name . ' = array();');
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $code_buf->pushStr('foreach ($' . $data_value . ' as $' . $key_var_name . ' => $' . $for_var_name . ') {');
                $code_buf->indent();
                self::unpackItemValue($code_buf, $key_var_name, $key_var_name, $key_item, $depth + 1);
                self::unpackItemValue($code_buf, $for_var_name, $for_var_name, $value_item, $depth + 1);
                $code_buf->pushStr('$' . $result_var_name . '[$' . $key_var_name . '] = $' . $for_var_name . ';');
                $code_buf->backIndent();
                $code_buf->pushStr('}');
                $code_buf->pushStr('$' . $var_name . ' = $' . $result_var_name . ';');
                break;
            default:
                throw new Exception('Unknown type:' . $item_type);
        }
        if ($isset_check) {
            $code_buf->backIndent();
            //if (0 === $depth) {
            //   $code_buf->pushStr('} else {');
            //    $code_buf->pushIndent('$' . $var_name . ' = ' . self::fixDefaultName($item) . ';');
            //}
            $code_buf->pushStr('}');
        }
    }
}