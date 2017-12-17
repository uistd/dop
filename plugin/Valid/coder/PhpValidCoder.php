<?php

namespace FFan\Dop\Plugin\Valid;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\FileBuf;
use FFan\Dop\Build\PackerBase;
use FFan\Dop\Build\PluginCoderBase;
use FFan\Dop\Build\StrBuf;
use FFan\Dop\Coder\Php\Coder;
use FFan\Dop\Protocol\IntItem;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\ListItem;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\StructItem;
use FFan\Std\Common\Str as FFanStr;

/**
 * Class PhpValidCoder
 * @package FFan\Dop\Plugin\Validator
 */
class PhpValidCoder extends PluginCoderBase
{
    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @var bool 是否要import基础类
     */
    private $import_flag;

    /**
     * @var Coder
     */
    protected $coder;

    /**
     * @var FileBuf
     */
    private $dop_file;

    /**
     * 生成插件代码
     */
    public function buildCode()
    {
        //方法生成到每个类中
        $this->coder->structIterator([$this, 'validateCode']);
    }

    /**
     * @param Struct $struct
     */
    public function validateCode(Struct $struct)
    {
        if (!$this->plugin->isBuildCode($struct)) {
            return;
        }
        $dop_file = $this->coder->getClassFileBuf($struct);
        if (!$dop_file) {
            return;
        }
        $this->dop_file = $dop_file;
        $method_buf = $dop_file->getBuf(FileBuf::METHOD_BUF);
        $property_buf = $dop_file->getBuf(FileBuf::PROPERTY_BUF);
        if (!$method_buf || !$property_buf) {
            return;
        }
        $property_buf->emptyLine();
        $property_buf->pushStr('/**');
        $property_buf->pushStr(' * @var string 数据有效性检查出错消息');
        $property_buf->pushStr(' */');
        $property_buf->pushStr('private $validate_error_msg;');

        $method_buf->emptyLine();
        $method_buf->pushStr('/**');
        $method_buf->pushStr(' * 验证数据有效性');
        $method_buf->pushStr(' * @return bool');
        $method_buf->pushStr(' */');
        $method_buf->pushStr('public function validateCheck()');
        $method_buf->pushStr('{')->indent();
        $all_items = $struct->getAllExtendItem();

        $this->import_flag = false;

        $valid_buf = $dop_file->getBuf(FileBuf::METHOD_BUF);

        $tmp_index = 0;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_items as $name => $item) {
            $name = $this->coder->fixPropertyName($name, $item);
            $this->validItem($valid_buf, 'this->' . $name, $item, $tmp_index);
        }
        $method_buf->push('return true;');
        $method_buf->backIndent()->push('}');
        $method_buf->emptyLine();
        $method_buf->pushStr('/**');
        $method_buf->pushStr(' * 获取出错的消息');
        $method_buf->pushStr(' * @return string|null');
        $method_buf->pushStr(' */');
        $method_buf->pushStr('public function getValidateErrorMsg()');
        $method_buf->pushStr('{');
        $method_buf->pushIndent('return $this->validate_error_msg;');
        $method_buf->pushStr('}');
        if ($this->import_flag) {
            $use_buf = $dop_file->getBuf(FileBuf::IMPORT_BUF);
            if ($use_buf) {
                $use_buf->pushUniqueStr('use FFan\Dop\DopValidator;');
            }
        }
    }

    /**
     * 生成检查代码
     * @param CodeBuf $valid_buf
     * @param string $var_name
     * @param Item $item
     * @param int $tmp_index
     */
    private function validItem($valid_buf, $var_name, $item, &$tmp_index)
    {
        /** @var ValidRule $rule */
        $rule = $item->getPluginData($this->plugin->getPluginName());
        $null_check = true;
        if (null !== $rule && $rule->is_require) {
            $this->requireCheck($valid_buf, $var_name, $rule, $item);
            $null_check = false;
        }
        $if_buf = new CodeBuf();
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $rule = $this->fixedRangeRule($rule, $item);
                if (!empty($rule->sets)) {
                    $this->setCheck($if_buf, $var_name, $rule);
                } elseif (null !== $rule && (null !== $rule->min_value || null !== $rule->max_value)) {
                    $this->rangeCheck($if_buf, $var_name, $rule);
                }
                break;
            case ItemType::STRING:
                if (null === $rule) {
                    $this->strSafeConvert($valid_buf, $var_name);
                } else {
                    if (!empty($rule->sets)) {
                        $this->setCheck($if_buf, $var_name, $rule);
                    } else {
                        //长度
                        if (null !== $rule->min_str_len || null !== $rule->max_str_len) {
                            $this->lengthCheck($if_buf, $var_name, $rule);
                            $this->import_flag = true;
                        }
                        if (null !== $rule->format_set) {
                            $this->formatCheck($if_buf, $var_name, $rule);
                        }
                    }
                }
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $sub_struct = $item->getStruct();
                $class_name = $sub_struct->getClassName();
                $if_buf->pushStr('if ($' . $var_name . ' instanceof ' . $class_name . ' && !$' . $var_name . '->validateCheck()) {');
                $if_buf->pushIndent('$this->validate_error_msg = $' . $var_name . '->getValidateErrorMsg();');
                $if_buf->pushIndent('return false;');
                $if_buf->pushStr('}');
                $null_check = false;
                break;
            case ItemType::ARR:
                $arr_check_code = new CodeBuf();
                if (null !== $rule && (null !== $rule->min_value || null !== $rule->max_value)) {
                    $len_name = PackerBase::varName($tmp_index++, 'len');
                    $arr_check_code->pushStr('$' . $len_name . ' = count($' . $var_name . ');');
                    $this->rangeCheck($arr_check_code, $len_name, $rule);
                }
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $for_var = PackerBase::varName($tmp_index++, 'item');
                $sub_item_valid_code = new CodeBuf();
                $this->validItem($sub_item_valid_code, $for_var, $sub_item, $tmp_index);
                //有代码输出，需要array验证
                if (!$arr_check_code->isEmpty() || !$sub_item_valid_code->isEmpty()) {
                    /** @var ValidRule $valid_rule */
                    $if_buf->pushStr('if (is_array($' . $var_name . ')) {')->indent();
                    if (!$arr_check_code->isEmpty()) {
                        $if_buf->push($arr_check_code);
                    }
                    if (!$sub_item_valid_code->isEmpty()) {
                        $if_buf->pushStr('foreach ($' . $var_name . ' as &$' . $for_var . ') {')->indent();
                        $if_buf->push($sub_item_valid_code);
                        $if_buf->backIndent()->pushStr('}');
                    }
                    $if_buf->backIndent()->pushStr('}');
                    $null_check = false;
                }
                break;
            case ItemType::MAP:
                //todo
                break;
        }
        //如果 if buf 不为空
        if (!$if_buf->isEmpty()) {
            //如果 rule 没不是必须传的
            if ($null_check) {
                //要判断是不是null， 字符串不能只判断null， 还要判断是不是空
                if (ItemType::STRING === $item_type) {
                    $valid_buf->pushStr('if (is_string($' . $var_name . ') && strlen($' . $var_name . ') > 0) {')->indent();
                } else {
                    $valid_buf->pushStr('if (null !== $' . $var_name . ') {')->indent();
                }
            }
            $valid_buf->push($if_buf);
            if ($null_check) {
                $valid_buf->backIndent()->pushStr('}');
            }
        }
    }

    /**
     * require检查
     * @param ValidRule $rule
     * @param Item $item
     * @return ValidRule
     */
    private function fixedRangeRule($rule, $item)
    {
        $item_type = $item->getType();
        if (ItemType::INT !== $item_type) {
            return $rule;
        }
        /** @var IntItem $item */
        $byte = $item->getByte();
        $key = $byte . '_';
        $key .= $item->isUnsigned() ? '1' : '0';
        $min_map = array(
            '1_0' => '-0x80',
            '1_1' => 0,
            '2_0' => '-0x8000',
            '2_1' => 0,
            '4_0' => '-0x80000000',
            '4_1' => 0,
            '8_0' => '-0x7fffffffffffffff'
        );
        $max_map = array(
            '1_0' => '0x7f',
            '1_1' => '0xff',
            '2_0' => '0x7fff',
            '2_1' => '0xffff',
            '4_0' => '0x7fffffff',
            '4_1' => '0xffffffff',
            '8_0' => '0x7fffffffffffffff'
        );
        if (!isset($max_map[$key])) {
            return $rule;
        }
        if (null === $rule) {
            $rule = new ValidRule();
        }
        $min_value = hexdec($min_map[$key]);
        $max_value = hexdec($max_map[$key]);
        if (null === $rule->min_value || $rule->min_value < $min_value) {
            $rule->min_value = $min_map[$key];
        }
        if (null === $rule->max_value || $rule->max_value > $max_value) {
            $rule->max_value = $max_map[$key];
        }
        if (null === $rule->range_msg) {
            $rule->range_msg = 'Invalid integer range of `'. $item->getName() .'`.';
        }
        return $rule;
    }


    /**
     * require检查
     * @param CodeBuf $valid_buf
     * @param string $var_name
     * @param ValidRule $rule
     * @param Item $item
     */
    private function requireCheck($valid_buf, $var_name, $rule, $item)
    {
        $type = $item->getType();
        if (ItemType::ARR === $type || ItemType::MAP === $type) {
            $this->conditionCode($valid_buf, 'empty($' . $var_name . ')', $rule, 'require');
        } elseif (ItemType::STRING === $type) {
            $this->conditionCode($valid_buf, '!is_string($' . $var_name . ') || 0 === strlen($' . $var_name . ')', $rule, 'require');
        } else {
            $this->conditionCode($valid_buf, 'null === $' . $var_name, $rule, 'require');
        }
    }

    /**
     * 范围检查
     * @param CodeBuf $valid_buf
     * @param string $var_name
     * @param ValidRule $rule
     */
    private function rangeCheck($valid_buf, $var_name, $rule)
    {
        $if_str = new StrBuf();
        $if_str->setJoinStr(' || ');
        if (null !== $rule->min_value) {
            $if_str->pushStr('$' . $var_name . ' < ' . $rule->min_value);
        }
        if (null !== $rule->max_value) {
            $if_str->pushStr('$' . $var_name . ' > ' . $rule->max_value);
        }
        $this->conditionCode($valid_buf, $if_str->dump(), $rule, 'range');
    }

    /**
     * 范围检查
     * @param CodeBuf $valid_buf
     * @param string $var_name
     * @param ValidRule $rule
     */
    private function lengthCheck($valid_buf, $var_name, $rule)
    {
        //字符串安全性处理
        if ($rule->is_trim || $rule->is_add_slashes || $rule->is_html_special_chars || $rule->is_strip_tags) {
            $this->strSafeConvert($valid_buf, $var_name, $rule->is_trim, $rule->is_add_slashes, $rule->is_html_special_chars, $rule->is_strip_tags);
        }
        $min_len = null === $rule->min_str_len ? 'null' : $rule->min_str_len;
        $max_len = null === $rule->max_str_len ? 'null' : $rule->max_str_len;
        $if_str = '!DopValidator::checkStrLength($' . $var_name . ', ' . $rule->str_len_type . ', ' . $min_len . ', ' . $max_len . ')';
        $this->conditionCode($valid_buf, $if_str, $rule, 'length');
    }

    /**
     * 字符串安全过滤
     * @param CodeBuf $valid_buf
     * @param string $var_name
     * @param bool $is_trim
     * @param bool $is_slash
     * @param bool $is_html
     * @param bool $is_strip
     */
    private function strSafeConvert($valid_buf, $var_name, $is_trim = true, $is_slash = false, $is_html = false, $is_strip = true)
    {
        $left_buf = new StrBuf();
        $right_buf = new StrBuf();
        if ($is_trim) {
            $left_buf->pushStr('trim(');
            $right_buf->pushStr(')');
        }
        if ($is_slash) {
            $left_buf->pushStr('addslashes(');
            $right_buf->pushStr(')');
        }
        if ($is_strip) {
            $left_buf->pushStr('strip_tags(');
            $right_buf->pushStr(')');
        } elseif ($is_html) {
            $left_buf->pushStr('htmlspecialchars(');
            $right_buf->pushStr(')');
        }
        $left_buf->pushStr('$' . $var_name);
        $left_buf->push($right_buf);
        $valid_buf->push('$' . $var_name . ' = ' . $left_buf->dump() . ';');
    }

    /**
     * 集合检查
     * @param CodeBuf $valid_buf
     * @param string $var_name
     * @param ValidRule $rule
     */
    private function setCheck($valid_buf, $var_name, $rule)
    {
        $if_str = '!in_array($' . $var_name . ', [' . join(', ', $rule->sets) . '])';
        $this->conditionCode($valid_buf, $if_str, $rule, 'format');
    }

    /**
     * 范围检查
     * @param CodeBuf $valid_buf
     * @param string $var_name
     * @param ValidRule $rule
     */
    private function formatCheck($valid_buf, $var_name, $rule)
    {
        //如果以 / 开始的字符串，表示为正则表达式
        if ('/' === $rule->format_set[0]) {
            $if_str = '!preg_match(\'' . $rule->format_set . '\', $' . $var_name . ')';
        } else {
            $this->dop_file->pushImport('use FFan\Dop\DopValidator;');
            $if_str = '!DopValidator::is' . FFanStr::camelName($rule->format_set) . '($' . $var_name . ')';
        }
        $this->conditionCode($valid_buf, $if_str, $rule, 'format');
    }

    /**
     * 生成if判断语句
     * @param CodeBuf $valid_buf
     * @param string $if_str
     * @param ValidRule $rule
     * @param string $err_msg_key ValidRule 中错误消息键名
     */
    private function conditionCode($valid_buf, $if_str, $rule, $err_msg_key)
    {
        $valid_buf->pushStr('if (' . $if_str . ') {');
        $valid_buf->indent();
        $err_msg_key .= '_msg';
        if (property_exists($rule, $err_msg_key) && null !== $rule->$err_msg_key) {
            $err_msg = $rule->$err_msg_key;
        } else {
            $err_msg = $rule->err_msg;
        }
        $valid_buf->pushStr('$this->validate_error_msg = "' . $err_msg . '";');
        $valid_buf->pushStr('return false;');
        $valid_buf->backIndent()->pushStr('}');
    }
}
