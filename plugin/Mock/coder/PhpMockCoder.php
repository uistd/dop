<?php

namespace UiStd\Dop\Plugin\Mock;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\FileBuf;
use UiStd\Dop\Build\PackerBase;
use UiStd\Dop\Build\PluginCoderBase;
use UiStd\Dop\Build\PluginRule;
use UiStd\Dop\Build\StrBuf;
use UiStd\Dop\Exception;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\ListItem;
use UiStd\Dop\Protocol\MapItem;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Protocol\StructItem;
use UiStd\Common\Str as UisStr;

/**
 * Class PhpMockCode
 * @package UiStd\Dop\Plugin\Mock
 */
class PhpMockCoder extends PluginCoderBase
{
    /**
     * @var string 当前正在使用的协议文件
     */
    private $current_name_space;

    /**
     * @var Struct[] 使用到的struct
     */
    private $struct_list;

    /**
     * 生成插件 PHP 代码
     */
    public function buildCode()
    {
        $this->coder->structIterator(array($this, 'getAllStruct'));
        //按xml文件生成代码
        $this->coder->xmlFileIterator(array($this, 'mockCode'));
    }

    /**
     * 保存使用到的struct
     * @param Struct $struct
     */
    public function getAllStruct(Struct $struct)
    {
        $key = $struct->getFullName();
        $this->struct_list[$key] = true;
    }

    /**
     * 根据文件名生成类名
     * @param string $file_name
     * @return string
     */
    private function fileNameToClassName($file_name)
    {
        return UisStr::camelName('mock_' . str_replace('/', '_', $file_name));
    }

    /**
     * 按xml文件生成代码
     * @param string $file_name xml协议文件名
     * @param array $struct_list 该文件下的协议列表
     */
    public function mockCode($file_name, $struct_list)
    {
        $this->current_name_space = '/' . $file_name;
        $build_path = $this->plugin->getBuildPath();
        //如果 带 子目录
        if (false !== strpos($file_name, '/')) {
            $path_name = '/' . UisStr::camelName(dirname($file_name));
            $build_path .= $path_name;
        }
        $class_name = $this->fileNameToClassName($file_name);
        $main_buf = $this->coder->getFolder()->touch($build_path, $class_name . '.php');
        $main_buf->pushStr('<?php');
        $main_buf->emptyLine();
        $class_ns = $this->makeClassNs($file_name);
        $main_buf->pushStr('namespace ' . $class_ns . ';');
        $import_buf = $main_buf->touchBuf(FileBuf::IMPORT_BUF);
        $import_buf->emptyLine();
        $main_buf->emptyLine();
        $main_buf->pushStr('class ' . $class_name . ' extends \UiStd\DopLib\DopMock');
        $main_buf->pushStr('{');
        $main_buf->indent();
        $main_buf->touchBuf(FileBuf::METHOD_BUF);
        $main_buf->backIndent()->pushStr('}');
        $main_buf->emptyLine();
        /** @var Struct $struct */
        foreach ($struct_list as $struct) {
            $full_name = $struct->getFullName();
            if (!isset($this->struct_list[$full_name])) {
                continue;
            }
            $this->buildStructCode($struct, $main_buf);
        }
    }

    /**
     * 生成namespace
     * @param string $file_name
     * @return string
     */
    private function makeClassNs($file_name)
    {
        $ns = $this->plugin->getNameSpace();
        $pos = strpos($file_name, '/');
        if (false !== $pos) {
            $tmp_arr = UisStr::split($file_name, '/');
            array_pop($tmp_arr);
            foreach ($tmp_arr as &$tmp) {
                $tmp = UisStr::camelName($tmp);
            }
            $ns .= '\\' . join('\\', $tmp_arr);
        }
        return $ns;
    }

    /**
     * 生成每个struct的代码
     * @param Struct $struct
     * @param FileBuf $file_buf
     */
    private function buildStructCode($struct, $file_buf)
    {
        $import_buf = $file_buf->getBuf(FileBuf::IMPORT_BUF);
        $mock_buf = $file_buf->getBuf(FileBuf::METHOD_BUF);
        $mock_buf->emptyLine();
        $class_name = $struct->getClassName();
        $mock_buf->pushStr('/**');
        $mock_buf->pushStr(' * 生成 ' . $class_name . ' mock数据');
        $mock_buf->pushStr(' * @return ' . $class_name);
        $mock_buf->pushStr(' */');
        $mock_buf->pushStr('public static function mock' . $class_name . '()');
        $mock_buf->pushStr('{');
        $mock_buf->indent();
        $use_ns = $this->coder->joinNameSpace($struct->getNamespace());
        $import_buf->pushUniqueStr('use ' . $use_ns . '\\' . $class_name . ';');
        $mock_buf->pushStr('$data = new ' . $class_name . '();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $name = $this->coder->fixPropertyName($name, $item);
            $this->makeImportCode($item, $import_buf);
            $mock_rule = $item->getPluginData($this->plugin->getPluginName());
            Exception::pushStack('Mock ' . $class_name . '->' . $name);
            $this->buildItemCode($mock_buf, '$data->' . $name, $mock_rule, $item);
        }
        $mock_buf->pushStr('return $data;');
        $mock_buf->backIndent()->pushStr('}');
    }

    /**
     * 生成引用相关的代码
     * @param Item $item
     * @param CodeBuf $use_buf
     */
    private function makeImportCode($item, $use_buf)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $struct = $item->getStruct();
            $ns = $struct->getNamespace();
            if ($ns === $this->current_name_space) {
                return;
            }
            if (dirname($this->current_name_space) === dirname($ns)) {
                return;
            }
            $import_ns = $this->makeClassNs($ns);
            $import_class = $this->fileNameToClassName($ns);
            if ('\\' !== $import_ns{(strlen($import_ns) - 1)}) {
                $import_ns .= '\\';
            }
            $use_buf->pushUniqueStr('use ' . $import_ns . $import_class . ';');
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->makeImportCode($item->getItem(), $use_buf);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->makeImportCode($item->getValueItem(), $use_buf);
        }
    }

    /**
     * 生成mock单项的代码
     * @param CodeBuf $mock_buf
     * @param string $mock_item
     * @param PluginRule $mock_rule
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
            case ItemType::BOOL:
                self::mockValue($mock_buf, $mock_item, $mock_rule, $item_type);
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                /** @var PluginRule $sub_mock_rule */
                $sub_mock_rule = $sub_item->getPluginData($plugin_name);
                $for_var_name = PackerBase::varName($depth, 'i');
                $len_var_name = PackerBase::varName($depth, 'len');
                $result_var_name = PackerBase::varName($depth, 'mock_arr');
                if (null !== $mock_rule) {
                    self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, ItemType::INT);
                } else {
                    $mock_buf->pushStr('$' . $len_var_name . ' = mt_rand(1, 3);');
                }
                $mock_buf->pushStr('$' . $result_var_name . ' = array();');
                $mock_buf->pushStr('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indent();
                $this->buildItemCode($mock_buf, '$' . $result_var_name . '[]', $sub_mock_rule, $sub_item, $depth + 1);
                $mock_buf->backIndent()->pushStr('}');
                $mock_buf->pushStr($mock_item . ' = $' . $result_var_name . ';');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $func_name = 'mock' . $item->getStructName();
                $sub_struct = $item->getStruct();
                $sub_file = $sub_struct->getFile(false);
                if ($this->current_name_space === $sub_struct->getNamespace()) {
                    $mock_class_name = 'self';
                } else {
                    $mock_class_name = $this->fileNameToClassName($sub_file);
                }
                $mock_buf->pushStr($mock_item . ' = ' . $mock_class_name . '::' . $func_name . '();');
                break;
            case ItemType::MAP:
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                /** @var PluginRule $key_mock_rule */
                $key_mock_rule = $key_item->getPluginData($plugin_name);
                /** @var PluginRule $value_mock_rule */
                $value_mock_rule = $value_item->getPluginData($plugin_name);
                $for_var_name = PackerBase::varName($depth, 'i');
                $len_var_name = PackerBase::varName($depth, 'len');
                $key_var_name = PackerBase::varName($depth, 'tmp_key');
                $value_var_name = PackerBase::varName($depth, 'tmp_value');
                $result_var_name = PackerBase::varName($depth, 'mock_arr');
                self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, ItemType::INT);
                $mock_buf->pushStr('$' . $result_var_name . ' = array();');
                $mock_buf->pushStr('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indent();
                $this->buildItemCode($mock_buf, '$' . $key_var_name, $key_mock_rule, $key_item, $depth + 1);
                $this->buildItemCode($mock_buf, '$' . $value_var_name, $value_mock_rule, $value_item, $depth + 1);
                $mock_buf->pushStr('$' . $result_var_name . '[$' . $key_var_name . '] = $' . $value_var_name . ';');
                $mock_buf->backIndent()->pushStr('}');
                $mock_buf->pushStr($mock_item . ' = $' . $result_var_name . ';');
                break;
        }
    }

    /**
     * mock值生成
     * @param CodeBuf $mock_buf
     * @param string $mock_item
     * @param PluginRule $mock_rule
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
                case ItemType::BOOL:
                    $mock_buf->pushStr($mock_item . ' = (bool)mt_rand(0, 1);');
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
            $mock_type = $mock_rule->getType();
            switch ($mock_type) {
                //固定值
                case MockType::MOCK_FIXED:
                    /** @var RuleFixed $mock_rule */
                    $mock_buf->pushStr($mock_item . ' = ' . $mock_rule->fixed_value . ';');
                    break;
                //指定几个值随机
                case MockType::MOCK_ENUM:
                    /** @var RuleEnum $mock_rule */
                    $arr_name = PackerBase::varName($tmp_arr_index++, '$tmp_arr');
                    $tmp_line = new StrBuf();
                    $mock_buf->insertBuf($tmp_line);
                    $tmp_line->pushStr($arr_name . ' = array(');
                    $tmp_line->pushStr(join(', ', $mock_rule->enum_set));
                    $tmp_line->pushStr(');');
                    $mock_buf->pushStr($mock_item . ' = ' . $arr_name . '[array_rand(' . $arr_name . ')];');
                    break;
                //指定范围随机
                case MockType::MOCK_RANGE:
                    /** @var RuleRange $mock_rule */
                    //ARR和MAP 表示是长度
                    if (ItemType::INT === $item_type || ItemType::ARR === $item_type || ItemType::MAP === $item_type || ItemType::BOOL === $item_type) {
                        $mock_buf->pushStr($mock_item . ' = mt_rand(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                    } elseif (ItemType::FLOAT === $item_type || ItemType::DOUBLE === $item_type) {
                        $mock_buf->pushStr($mock_item . ' = self::floatRangeMock(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                    } elseif (ItemType::STRING === $item_type) {
                        $mock_buf->pushStr($mock_item . ' = self::strRangeMock(' . $mock_rule->range_min . ', ' . $mock_rule->range_max . ');');
                    }
                    break;
                //固定值mock
                case MockType::MOCK_BUILD_IN_TYPE:
                    /** @var RuleType $mock_rule */
                    $build_func = $mock_rule->build_in_type . 'TypeMock';
                    $mock_buf->pushStr($mock_item . ' = self::' . $build_func . '();');
                    break;
                //自增长
                case MockType::MOCK_INCREASE:
                    /** @var RuleIncrease $mock_rule */
                    $tmp_name = PackerBase::varName($tmp_arr_index++, '$tmp_inc');
                    $mock_buf->pushStr('static ' . $tmp_name . ' = ' . $mock_rule->begin . ';');
                    $mock_buf->pushStr($mock_item . ' = ' . $tmp_name . ';');
                    $mock_buf->pushStr($tmp_name . ' += ' . $mock_rule->step . ';');
                    break;
                //数据配对
                case MockType::MOCK_PAIR:
                    /** @var RulePair $mock_rule */
                    $arr_name = PackerBase::varName($tmp_arr_index++, '$tmp_map');
                    $tmp_line = new StrBuf();
                    $mock_buf->insertBuf($tmp_line);
                    $tmp_line->pushStr($arr_name . ' = array(');
                    $tmp_line->pushStr(UisStr::dualJoin($mock_rule->value_set, ',', ' => '));
                    $tmp_line->pushStr(');');
                    $tmp_code = $arr_name . '[$data->' . $mock_rule->key_field . ']';
                    $mock_buf->pushStr($mock_item . ' = isset(' . $tmp_code . ') ? ' . $tmp_code . ' : null;');
                    break;
                default:
                    throw new Exception('Unknown mock type . ', $mock_type);
            }
        }
    }
}
