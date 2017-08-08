<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\FileBuf;
use ffan\dop\build\PackerBase;
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
     * @var string 当前正在使用的协议文件
     */
    private $current_file;

    /**
     * 生成插件 PHP 代码
     */
    public function buildCode()
    {
        $folder = $this->coder->getFolder();
        $include_file = $folder->touch($this->plugin->getBuildPath(), 'include.php');
        $this->plugin->loadTpl($include_file, 'tpl/include.tpl');
        $autoload_buf = $include_file->getBuf('autoload');
        if (!$autoload_buf) {
            throw new Exception('autoload buf mission');
        }
        $this->autoload_buf = $autoload_buf;

        //按xml文件生成代码
        $this->coder->xmlFileIterator(array($this, 'mockCode'));
    }

    /**
     * 根据文件名生成类名
     * @param string $file_name
     * @return string
     */
    private function fileNameToClassName($file_name)
    {
        return FFanStr::camelName('mock_' . str_replace('/', '_', $file_name));
    }

    /**
     * 按xml文件生成代码
     * @param string $file_name xml协议文件名
     * @param array $struct_list 该文件下的协议列表
     */
    public function mockCode($file_name, $struct_list)
    {
        $this->current_file = $file_name;
        $build_path = $this->plugin->getBuildPath();
        $class_name = $this->fileNameToClassName($file_name);
        $main_buf = $this->coder->getFolder()->touch($build_path, $class_name . '.php');
        $main_buf->pushStr('<?php');
        $main_buf->emptyLine();
        $class_ns = $this->makeClassNs($file_name);
        $main_buf->pushStr('namespace ' . $class_ns . ';');
        $import_buf = $main_buf->touchBuf(FileBuf::IMPORT_BUF);
        $import_buf->emptyLine();
        $main_buf->emptyLine();
        $main_buf->pushStr('class ' . $class_name . ' extends \ffan\dop\DopMock');
        $main_buf->pushStr('{');
        $main_buf->indent();
        $main_buf->touchBuf(FileBuf::METHOD_BUF);
        $main_buf->backIndent()->pushStr('}');
        $main_buf->emptyLine();
        /** @var Struct $struct */
        foreach ($struct_list as $struct) {
            $this->buildStructCode($struct, $main_buf);
        }
        $this->autoload_buf->pushStr("'" . $class_ns . "' => \$mock_file_dir,");
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
            $file_name = substr($file_name, 0, $pos);
        }
        $ns .= '\\' . $file_name;
        return $ns;
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
        $mock_buf->indent();
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
            $mock_rule = $item->getPluginData($this->plugin->getPluginName());
            Exception::setAppendMsg('Mock ' . $class_name . '->' . $name);
            $this->buildItemCode($mock_buf, '$data->' . $name, $mock_rule, $item);
        }
        $mock_buf->pushStr('return $data;');
        $mock_buf->backIndent()->pushStr('}');
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
            case ItemType::BOOL:
                self::mockValue($mock_buf, $mock_item, $mock_rule, $item_type);
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                /** @var MockRule $sub_mock_rule */
                $sub_mock_rule = $sub_item->getPluginData($plugin_name);
                $for_var_name = PackerBase::varName($depth, 'i');
                $len_var_name = PackerBase::varName($depth, 'len');
                $result_var_name = PackerBase::varName($depth, 'mock_arr');
                self::mockValue($mock_buf, '$' . $len_var_name, $mock_rule, ItemType::INT);
                $mock_buf->pushStr('$' . $result_var_name . ' = array();');
                $mock_buf->pushStr('for ($' . $for_var_name . ' = 0; $' . $for_var_name . ' < $' . $len_var_name . '; ++$' . $for_var_name . ') {');
                $mock_buf->indent();
                $this->buildItemCode($mock_buf, '$' . $result_var_name.'[]', $sub_mock_rule, $sub_item, $depth + 1);
                $mock_buf->backIndent()->pushStr('}');
                $mock_buf->pushStr($mock_item . ' = $' . $result_var_name . ';');
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $func_name = 'mock' . $item->getStructName();
                $sub_struct = $item->getStruct();
                $sub_file = $sub_struct->getFile(false);
                if ($sub_file !== $this->current_file) {
                    $mock_class_name = $this->fileNameToClassName($sub_file);
                } else {
                    $mock_class_name = 'self';
                }
                $mock_buf->pushStr($mock_item . ' = ' . $mock_class_name . '::' . $func_name . '();');
                break;
            case ItemType::MAP:
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                /** @var MockRule $key_mock_rule */
                $key_mock_rule = $key_item->getPluginData($plugin_name);
                /** @var MockRule $value_mock_rule */
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
