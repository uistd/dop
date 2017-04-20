<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\BuildOption;
use ffan\dop\CodeBuf;
use ffan\dop\DOPException;
use ffan\dop\DOPGenerator;
use ffan\dop\Item;
use ffan\dop\ItemType;
use ffan\dop\ListItem;
use ffan\dop\MapItem;
use ffan\dop\pack\php\Coder;
use ffan\dop\Plugin;
use ffan\dop\PluginCoder;
use ffan\dop\Struct;
use ffan\dop\StructItem;

/**
 * Class PhpMockCode
 * @package ffan\dop\plugin\mock
 */
class PhpMockCode extends PluginCoder
{
    /**
     * PHP 相关插件代码
     * @param Plugin $plugin
     * @param BuildOption $build_opt
     * @param CodeBuf $code_buf
     * @param Struct $struct
     * @return void
     */
    public function pluginCode(Plugin $plugin, BuildOption $build_opt, CodeBuf $code_buf, Struct $struct)
    {
    }

    /**
     * 生成mock单项的代码
     * @param CodeBuf $mock_buf
     * @param string $mock_item
     * @param MockRule $mock_rule
     * @param Item $item
     * @param int $depth
     */
    private static function mockItem($mock_buf, $mock_item, $mock_rule, $item, $depth = 0)
    {
        $plugin_name = MockPlugin::getName();
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
            case ItemType::STRING:
                self::mockValue($mock_buf, $mock_item, $mock_rule, $item_type);
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $sub_mock_rule = $sub_item->getPluginData($plugin_name);
                $for_var_name = DOPGenerator::tmpVarName($depth, 'i');
                $len_var_name = DOPGenerator::tmpVarName($depth, 'len');
                $result_var_name = DOPGenerator::tmpVarName($depth, 'mock_arr');
                self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, $item_type);
                $mock_buf->push('$' . $result_var_name . ' = array();');
                $mock_buf->push('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indentIncrease();
                self::mockItem($mock_buf, '$' . $result_var_name, $sub_mock_rule, $sub_item, $depth + 1);
                $mock_buf->indentDecrease()->push('}');
                $mock_buf->push($mock_item . ' = $' . $result_var_name . ';');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $func_name = 'mock' . $item->getStructName();
                $mock_buf->push($mock_item . ' = self::' . $func_name . '();');
                break;
            case ItemType::MAP:
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $key_mock_rule = $key_item->getPluginData($plugin_name);
                $value_mock_rule = $value_item->getPluginData($plugin_name);
                $for_var_name = DOPGenerator::tmpVarName($depth, 'i');
                $len_var_name = DOPGenerator::tmpVarName($depth, 'len');
                $key_var_name = DOPGenerator::tmpVarName($depth, 'tmp_key');
                $value_var_name = DOPGenerator::tmpVarName($depth, 'tmp_value');
                $result_var_name = DOPGenerator::tmpVarName($depth, 'mock_arr');
                self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, $item_type);
                $mock_buf->push('$' . $result_var_name . ' = array();');
                $mock_buf->push('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indentIncrease();
                self::mockItem($mock_buf, '$' . $key_var_name, $key_mock_rule, $key_item, $depth + 1);
                self::mockItem($mock_buf, '$' . $value_var_name, $value_mock_rule, $value_item, $depth + 1);
                $mock_buf->push('$' . $result_var_name . '[' . $key_var_name . '] = $' . $value_var_name);
                $mock_buf->indentDecrease()->push('}');
                $mock_buf->push($mock_item . ' = $' . $result_var_name . ';');
                break;
        }
    }

    /**
     * mock值生成
     * @param CodeBuf $mock_buf
     * @param string $mock_item
     * @param MockRule $mock_rule
     * @param int $item_type
     * @throws DOPException
     */
    private static function mockValue($mock_buf, $mock_item, $mock_rule, $item_type)
    {
        static $tmp_arr_index = 0;
        switch ($mock_rule->mock_type) {
            //固定值
            case MockRule::MOCK_FIXED:
                $mock_buf->push($mock_item . ' = ' . $mock_rule->fixed_value . ';');
                break;
            //指定几个值随机
            case MockRule::MOCK_ENUM:
                $arr_name = '$tmp_arr_' . $tmp_arr_index;
                $tmp_arr_index++;
                $mock_buf->lineTmp($arr_name . ' = array(');
                $mock_buf->lineTmp(join(', ', $mock_rule->enum_set));
                $mock_buf->lineTmp(');');
                $mock_buf->lineFin();
                $mock_buf->push($mock_item . ' = ' . $arr_name . '[array_rand(' . $arr_name . ')];');
                break;
            //指定范围随机
            case MockRule::MOCK_RANGE:
                //ARR和MAP 表示是长度
                if (ItemType::INT === $item_type || ItemType::ARR === $item_type || ItemType::MAP === $item_type) {
                    $mock_buf->push($mock_item . ' = mt_rand(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                } elseif (ItemType::FLOAT === $item_type || ItemType::DOUBLE === $item_type) {
                    $mock_buf->push($mock_item . ' = self::floatRangeMock(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                } elseif (ItemType::STRING === $item_type) {
                    $mock_buf->push($mock_item . ' = self::strRangeMock(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                }
                break;
            //固定值mock
            case MockRule::MOCK_TYPE:
                $build_func = $mock_rule->build_in_type . 'TypeMock';
                $mock_buf->push($mock_item . ' = self::' . $build_func . '();');
                break;
            default:
                throw new DOPException('Unknown mock type . ', $mock_rule->mock_type);
        }
    }

    /**
     * 生成一个mock函数
     * @param BuildOption $build_opt
     * @param Struct $struct
     * @return string
     */
    private function mockFuncCode($build_opt, $struct)
    {
        $mock_buf = new CodeBuf();
        $mock_buf->emptyLine();
        $class_name = $struct->getClassName();
        $mock_buf->push('/**');
        $mock_buf->push(' * 生成 ' . $class_name . ' mock数据');
        $mock_buf->push(' */');
        $mock_buf->push('public function mock' . $class_name . '()');
        $mock_buf->push('{');
        $mock_buf->indentIncrease();
        $mock_buf->push('use ' . Coder::phpNameSpace($build_opt, $class_name) . ';');
        $mock_buf->push('$data = new ' . $class_name . '();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            /** @var MockRule $mock_rule */
            $mock_rule = $item->getPluginData('mock');
            if (null === $mock_rule) {
                continue;
            }
            self::mockItem($mock_buf, '$this->' . $name, $mock_rule, $item);
        }
        $mock_buf->indentDecrease()->push('}');
        return $mock_buf->dump();
    }
}
