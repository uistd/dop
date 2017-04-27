<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\FileBuf;
use ffan\dop\build\PluginCoderBase;
use ffan\dop\build\StrBuf;
use ffan\dop\Exception;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;
use ffan\php\utils\Str as FFanStr;

/**
 * Class PhpMockCode
 * @package ffan\dop\plugin\mock
 */
class PhpMockCoder extends PluginCoderBase
{
    /**
     * 生成插件 PHP 代码
     */
    public function buildCode()
    {
        $this->coder->XmlIterator(array($this, 'mockCode'));
    }

    /**
     * 按xml文件生成代码
     * @param string $file_name xml协议文件名
     * @param array $struct_list 该文件下的协议列表
     */
    public function mockCode($file_name, $struct_list)
    {
        $class_name = FFanStr::camelName('mock_' . str_replace('/', '_', $file_name));
        $dop_file = $this->coder->getFolder()->touch('dop_mock', $class_name);
        $main_buf = $dop_file->getMainBuf();
        $main_buf->push('<?php');
        $main_buf->emptyLine();
        $main_buf->push('namespace '. $this->coder->joinNameSpace('/mock'));
        $import_buf = new CodeBuf();
        $dop_file->addBuf(FileBuf::IMPORT_BUF, $import_buf);
        $import_buf->emptyLine();
        $import_buf->push('use ffan\dop\mock\DopMock;');
        $main_buf->push('class '. $class_name . ' extends DopMock');
        $main_buf->push('{');
        $main_buf->indentIncrease();
        $method_buf = new CodeBuf();
        $dop_file->addBuf(FileBuf::METHOD_BUF, $method_buf);
        $main_buf->indentDecrease()->push('}');
        $main_buf->emptyLine();
        
        /** @var Struct $struct */
        foreach ($struct_list as $struct) {
            $this->buildStructCode($struct, $dop_file);
        }
    }

    /**
     * 生成每个struct的代码
     * @param Struct $struct
     * @param FileBuf $file
     */
    private function buildStructCode($struct, $file)
    {
        $import_buf = $file->getBuf(FileBuf::IMPORT_BUF);
        $mock_buf = $file->getBuf(FileBuf::METHOD_BUF);
        $mock_buf->emptyLine();
        $class_name = $struct->getClassName();
        $mock_buf->push('/**');
        $mock_buf->push(' * 生成 ' . $class_name . ' mock数据');
        $mock_buf->push(' */');
        $mock_buf->push('public function mock' . $class_name . '()');
        $mock_buf->push('{');
        $mock_buf->indentIncrease();
        $import_buf->push('use ' . $this->coder->joinNameSpace($class_name) . ';');
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
            $this->buildItemCode($mock_buf, '$this->' . $name, $mock_rule, $item);
        }
        $mock_buf->indentDecrease()->push('}');
    }
    
    /**
     * 生成mock单项的代码
     * @param CodeBuf $mock_buf
     * @param string $mock_item
     * @param MockRule $mock_rule
     * @param Item $item
     * @param int $depth
     */
    private function buildItemCode($mock_buf, $mock_item, $mock_rule, $item, $depth = 0)
    {
        $plugin_name = $this->plugin->getName();
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
                $for_var_name = tmp_var_name($depth, 'i');
                $len_var_name = tmp_var_name($depth, 'len');
                $result_var_name = tmp_var_name($depth, 'mock_arr');
                self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, $item_type);
                $mock_buf->push('$' . $result_var_name . ' = array();');
                $mock_buf->push('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indentIncrease();
                $this->buildItemCode($mock_buf, '$' . $result_var_name, $sub_mock_rule, $sub_item, $depth + 1);
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
                $for_var_name = tmp_var_name($depth, 'i');
                $len_var_name = tmp_var_name($depth, 'len');
                $key_var_name = tmp_var_name($depth, 'tmp_key');
                $value_var_name = tmp_var_name($depth, 'tmp_value');
                $result_var_name = tmp_var_name($depth, 'mock_arr');
                self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, $item_type);
                $mock_buf->push('$' . $result_var_name . ' = array();');
                $mock_buf->push('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indentIncrease();
                $this->buildItemCode($mock_buf, '$' . $key_var_name, $key_mock_rule, $key_item, $depth + 1);
                $this->buildItemCode($mock_buf, '$' . $value_var_name, $value_mock_rule, $value_item, $depth + 1);
                $mock_buf->push('$' . $result_var_name . '[' . $key_var_name . '] = $' . $value_var_name);
                $mock_buf->indentDecrease()->push('}');
                $mock_buf->push($mock_item .' = $'. $result_var_name .';');
                break;
        }
    }

    /**
     * mock值生成
     * @param CodeBuf $mock_buf
     * @param string $mock_item
     * @param MockRule $mock_rule
     * @param int $item_type
     * @throws Exception
     */
    private function mockValue($mock_buf, $mock_item, $mock_rule, $item_type)
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
                $tmp_line = new StrBuf();
                $mock_buf->insertBuf($tmp_line);
                $tmp_line->push($arr_name . ' = array(');
                $tmp_line->push(join(', ', $mock_rule->enum_set));
                $tmp_line->push(');');
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
                $build_func = $mock_rule->build_in_type .'TypeMock';
                $mock_buf->push($mock_item . ' = self::'. $build_func .'();');
                break;
            default:
                throw new Exception('Unknown mock type . ', $mock_rule->mock_type);
        }
    }
}
