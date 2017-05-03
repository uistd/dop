<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\FileBuf;
use ffan\dop\build\PluginCoderBase;
use ffan\dop\build\StrBuf;
use ffan\dop\coder\php\Coder;
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
     * 获取命名空间
     */
    private function mockNameSpace()
    {
        return $this->coder->joinNameSpace('plugin/mock');
    }

    /**
     * 生成插件 PHP 代码
     */
    public function buildCode()
    {
        $autoload_buf = $this->coder->getBuf('', Coder::MAIN_FILE, 'autoload');
        $name_space = $this->mockNameSpace();
        if ($autoload_buf) {
            $autoload_buf->pushStr("'" . $name_space . "' => 'dop_mock',");
        }
        $this->coder->xmlFileIterator(array($this, 'mockCode'));
        $folder = $this->plugin->getFolder();
        $base_class_file = $folder->touch('dop_mock', 'DopMock.php');
        $tpl_data = array(
            'namespace' => $name_space
        );
        $this->plugin->loadTpl($base_class_file, 'tpl/DopMock.tpl', $tpl_data);
    }

    /**
     * 按xml文件生成代码
     * @param string $file_name xml协议文件名
     * @param array $struct_list 该文件下的协议列表
     */
    public function mockCode($file_name, $struct_list)
    {
        $class_name = FFanStr::camelName('mock_' . str_replace('/', '_', $file_name));
        $main_buf = $this->coder->getFolder()->touch('dop_mock', $class_name . '.php');
        $main_buf->pushStr('<?php');
        $main_buf->emptyLine();
        $main_buf->pushStr('namespace ' . $this->mockNameSpace() . ';');
        $import_buf = $main_buf->touchBuf(FileBuf::IMPORT_BUF);
        $import_buf->emptyLine();
        $main_buf->emptyLine();
        $main_buf->pushStr('class ' . $class_name . ' extends DopMock');
        $main_buf->pushStr('{');
        $main_buf->indentIncrease();
        $main_buf->touchBuf(FileBuf::METHOD_BUF);
        $main_buf->indentDecrease()->pushStr('}');
        $main_buf->emptyLine();
        /** @var Struct $struct */
        foreach ($struct_list as $struct) {
            $this->buildStructCode($struct, $main_buf);
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
        $mock_buf->pushStr('/**');
        $mock_buf->pushStr(' * 生成 ' . $class_name . ' mock数据');
        $mock_buf->pushStr(' * @return ' . $class_name);
        $mock_buf->pushStr(' */');
        $mock_buf->pushStr('public static function mock' . $class_name . '()');
        $mock_buf->pushStr('{');
        $mock_buf->indentIncrease();
        $use_ns = $this->coder->joinNameSpace($struct->getNamespace());
        $import_buf->pushStr('use ' . $use_ns . '\\' . $class_name . ';');
        $mock_buf->pushStr('$data = new ' . $class_name . '();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            /** @var MockRule $mock_rule */
            $mock_rule = $item->getPluginData('mock');
            Exception::setAppendMsg('Mock ' . $class_name . '->' . $name);
            $this->buildItemCode($mock_buf, '$data->' . $name, $mock_rule, $item);
        }
        $mock_buf->pushStr('return $data;');
        $mock_buf->indentDecrease()->pushStr('}');
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
        $plugin_name = $this->plugin->getPluginName();
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
                self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, ItemType::INT);
                $mock_buf->pushStr('$' . $result_var_name . ' = array();');
                $mock_buf->pushStr('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indentIncrease();
                $this->buildItemCode($mock_buf, '$' . $result_var_name, $sub_mock_rule, $sub_item, $depth + 1);
                $mock_buf->indentDecrease()->pushStr('}');
                $mock_buf->pushStr($mock_item . ' = $' . $result_var_name . ';');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $func_name = 'mock' . $item->getStructName();
                $mock_buf->pushStr($mock_item . ' = self::' . $func_name . '();');
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
                self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, ItemType::INT);
                $mock_buf->pushStr('$' . $result_var_name . ' = array();');
                $mock_buf->pushStr('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indentIncrease();
                $this->buildItemCode($mock_buf, '$' . $key_var_name, $key_mock_rule, $key_item, $depth + 1);
                $this->buildItemCode($mock_buf, '$' . $value_var_name, $value_mock_rule, $value_item, $depth + 1);
                $mock_buf->pushStr('$' . $result_var_name . '[$' . $key_var_name . '] = $' . $value_var_name . ';');
                $mock_buf->indentDecrease()->pushStr('}');
                $mock_buf->pushStr($mock_item . ' = $' . $result_var_name . ';');
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
        if (null === $mock_rule) {
            switch ($item_type) {
                case ItemType::INT:
                    $mock_buf->pushStr($mock_item . ' = mt_rand(0, 100);');
                    break;
                case ItemType::STRING:
                    $mock_buf->pushStr($mock_item . ' = self::strRangeMock(5, 20);');
                    break;
                case ItemType::FLOAT:
                case ItemType::DOUBLE:
                    $mock_buf->pushStr($mock_item . ' = self::floatRangeMock(0, 100);');
                    break;
            }
        } else {
            switch ($mock_rule->mock_type) {
                //固定值
                case MockRule::MOCK_FIXED:
                    $mock_buf->pushStr($mock_item . ' = ' . $mock_rule->fixed_value . ';');
                    break;
                //指定几个值随机
                case MockRule::MOCK_ENUM:
                    $arr_name = '$tmp_arr_' . $tmp_arr_index;
                    $tmp_arr_index++;
                    $tmp_line = new StrBuf();
                    $mock_buf->insertBuf($tmp_line);
                    $tmp_line->pushStr($arr_name . ' = array(');
                    $tmp_line->pushStr(join(', ', $mock_rule->enum_set));
                    $tmp_line->pushStr(');');
                    $mock_buf->pushStr($mock_item . ' = ' . $arr_name . '[array_rand(' . $arr_name . ')];');
                    break;
                //指定范围随机
                case MockRule::MOCK_RANGE:
                    //ARR和MAP 表示是长度
                    if (ItemType::INT === $item_type || ItemType::ARR === $item_type || ItemType::MAP === $item_type) {
                        $mock_buf->pushStr($mock_item . ' = mt_rand(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                    } elseif (ItemType::FLOAT === $item_type || ItemType::DOUBLE === $item_type) {
                        $mock_buf->pushStr($mock_item . ' = self::floatRangeMock(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                    } elseif (ItemType::STRING === $item_type) {
                        $mock_buf->pushStr($mock_item . ' = self::strRangeMock(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                    }
                    break;
                //固定值mock
                case MockRule::MOCK_BUILD_IN_TYPE:
                    $build_func = $mock_rule->build_in_type . 'TypeMock';
                    $mock_buf->pushStr($mock_item . ' = self::' . $build_func . '();');
                    break;
                default:
                    throw new Exception('Unknown mock type . ', $mock_rule->mock_type);
            }
        }
    }
}
