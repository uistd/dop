<?php

namespace ffan\dop\plugin\valid;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\FileBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\build\PluginCoderBase;
use ffan\dop\build\StrBuf;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;
use ffan\php\utils\Str as FFanStr;

/**
 * Class PhpValidCoder
 * @package ffan\dop\plugin\validator
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
        $folder = $this->coder->getFolder();
        $dop_file = $folder->getFile($struct->getNamespace(), $struct->getClassName() . '.php');
        if (!$dop_file) {
            return;
        }
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
                $use_buf->pushUniqueStr('use ffan\dop\DopValidator;');
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
        if (null !== $rule && $rule->is_require) {
            $this->requireCheck($valid_buf, $var_name, $rule, $item);
        }
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                if (null !== $rule && (null !== $rule->min_value || null !== $rule->max_value)) {
                    $this->rangeCheck($valid_buf, $var_name, $rule);
                }
                break;
            case ItemType::STRING:
                if (null === $rule) {
                    break;
                }
                //长度
                if (null !== $rule->min_str_len || null !== $rule->max_str_len) {
                    $this->lengthCheck($valid_buf, $var_name, $rule);
                    $this->import_flag = true;
                }
                if (null !== $rule->format_set) {
                    $this->formatCheck($valid_buf, $var_name, $rule, $use_code_flag);
                }
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $sub_struct = $item->getStruct();
                $class_name = $sub_struct->getClassName();
                $valid_buf->pushStr('if ($' . $var_name . ' instanceof ' . $class_name . ' && !$' . $var_name . '->validateCheck()) {');
                $valid_buf->pushIndent('$this->validate_error_msg = $' . $var_name . '->getValidateErrorMsg();');
                $valid_buf->pushIndent('return false;');
                $valid_buf->pushStr('}');
                break;
            case ItemType::ARR:
                $arr_check_code = new CodeBuf();
                if (null !== $rule && (null !== $rule->min_value || null !== $rule->max_value)) {
                    //$count_chk_buf->pushStr('if (is_array($'. $var_name .')) {')->indent();
                    $len_name = PackerBase::varName($tmp_index++, 'len');
                    $arr_check_code->pushStr('$' . $len_name . ' = count($' . $var_name . ');');
                    $this->rangeCheck($arr_check_code, $len_name, $rule);
                    //$count_chk_buf->backIndent()->pushStr('}');
                }
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $for_var = PackerBase::varName($tmp_index++, 'item');
                $sub_item_valid_code = new CodeBuf();
                $this->validItem($sub_item_valid_code, $for_var, $sub_item, $tmp_index);
                //有代码输出，需要array验证
                if (!$arr_check_code->isEmpty() || !$sub_item_valid_code->isEmpty()) {
                    /** @var ValidRule $valid_rule */
                    $valid_buf->pushStr('if (is_array($' . $var_name . ')) {')->indent();
                    if (!$arr_check_code->isEmpty()) {
                        $valid_buf->push($arr_check_code);
                    }
                    if (!$sub_item_valid_code->isEmpty()) {
                        $valid_buf->pushStr('foreach ($' . $var_name . ' as $' . $for_var . ') {')->indent();
                        $valid_buf->push($sub_item_valid_code);
                        $valid_buf->backIndent()->pushStr('}');
                    }
                    $valid_buf->backIndent()->pushStr('}');
                }
                break;
            case ItemType::MAP:
                //todo
                break;
        }
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
        $valid_buf->pushStr('if (null !== $' . $var_name . ') {');
        $valid_buf->indent();
        //字符串安全性处理
        if ($rule->is_trim || $rule->is_add_slashes || $rule->is_html_special_chars || $rule->is_strip_tags) {
            $left_buf = new StrBuf();
            $right_buf = new StrBuf();
            if ($rule->is_trim) {
                $left_buf->pushStr('trim(');
                $right_buf->pushStr(')');
            }
            if ($rule->is_add_slashes) {
                $left_buf->pushStr('addslashes(');
                $right_buf->pushStr(')');
            }
            if ($rule->is_strip_tags) {
                $left_buf->pushStr('strip_tags(');
                $right_buf->pushStr(')');
            } elseif ($rule->is_html_special_chars) {
                $left_buf->pushStr('htmlspecialchars(');
                $right_buf->pushStr(')');
            }
            $left_buf->pushStr('$' . $var_name);
            $left_buf->push($right_buf);
            $valid_buf->push('$' . $var_name . ' = ' . $left_buf->dump() . ';');
        }
        $min_len = null === $rule->min_str_len ? 'null' : $rule->min_str_len;
        $max_len = null === $rule->max_str_len ? 'null' : $rule->max_str_len;
        $if_str = 'DopValidator::checkStrLength($' . $var_name . ', ' . $rule->str_len_type . ', ' . $min_len . ', ' . $max_len . ')';
        $this->conditionCode($valid_buf, $if_str, $rule, 'length');
        $valid_buf->backIndent()->pushStr('}');
    }

    /**
     * 范围检查
     * @param CodeBuf $valid_buf
     * @param string $var_name
     * @param ValidRule $rule
     * @param bool $use_code_flag
     */
    private function formatCheck($valid_buf, $var_name, $rule, &$use_code_flag)
    {
        //如果以 / 开始的字符串，表示为正则表达式
        if ('/' === $rule->format_set[0]) {
            $if_str = '!preg_match(\'' . $rule->format_set . '\', $' . $var_name . ')';
        } else {
            $if_str = 'DopValidator::isValid' . FFanStr::camelName($rule->format_set) . '($' . $var_name . ')';
            $use_code_flag = true;
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
