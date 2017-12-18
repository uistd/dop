<?php

namespace UiStd\Dop\Build;

use UiStd\Dop\Exception;
use UiStd\Common\Str as UisStr;

/**
 * Class TplLoader 模板加载器
 * @package UiStd\Dop\Build
 */
class TplLoader
{
    /**
     * 左、右标签
     */
    const LEFT_TAG = '{{';
    const RIGHT_TAG = '}}';

    /**
     * 模板编译结果 一行
     */
    const TPL_OP_NORMAL_LINE = 1;

    /**
     * 带tag的行
     */
    const TPL_OP_TAG_LINE = 2;

    /**
     * 模板编译结果 普通字符串
     */
    const TPL_OP_NORMAL_STRING = 3;

    /**
     * 模板编译结果 变量
     */
    const TPL_OP_VAR = 4;

    /**
     * 模板编译结果 CODE_BUF
     */
    const TPL_OP_CODE_BUF = 5;

    /**
     * 模板编译结果 STR_BUF
     */
    const TPL_OP_STR_BUF = 6;

    /**
     * 模板编译结果 前一项的参数
     */
    const TPL_OP_ARGS = 8;

    /**
     * 模板编译结果 换行
     */
    const TPL_OP_BR = 7;

    /**
     * @var string 模板名称
     */
    private $tpl_file;

    /**
     * @var int 行号
     */
    private $line_number = 0;

    /**
     * @var array 实例缓存
     */
    private static $tpl_instance;

    /**
     * @var array 模板编译结果
     */
    private $parse_result;

    /**
     * @var array 编译结果识别标志
     */
    private $result_code;

    /**
     * @var array 内置code buf 宏
     */
    private static $define_code_buf = array(
        'PROPERTY_CODE_BUF' => FileBuf::PROPERTY_BUF,
        'METHOD_CODE_BUF' => FileBuf::METHOD_BUF,
        'HEADER_CODE_BUF' => FileBuf::HEADER_BUF,
        'IMPORT_CODE_BUF' => FileBuf::IMPORT_BUF,
    );

    /**
     * TplLoader constructor.
     * @param string $tpl_file 模板文件
     * @throws Exception
     */
    public function __construct($tpl_file)
    {
        if (!is_file($tpl_file)) {
            throw new Exception('Can not found tpl file:' . $tpl_file);
        }
        $this->tpl_file = $tpl_file;
        $this->parseTpl();
    }

    /**
     * 编译模板
     */
    private function parseTpl()
    {
        $this->parse_result = array();
        $this->result_code = array();
        $file_handle = fopen($this->tpl_file, 'r');
        while ($line = fgets($file_handle)) {
            ++$this->line_number;
            if (false === strpos($line, self::LEFT_TAG)) {
                $this->pushTplCode(self::TPL_OP_NORMAL_LINE, rtrim($line, "\n"));
                continue;
            }
            $this->parseLine($line);
        }
    }

    /**
     * 消息格式化
     * @param string $msg
     * @return string
     */
    private function errorMsg($msg)
    {
        return $msg . ' File: ' . $this->tpl_file . ' Line: ' . $this->line_number;
    }

    /**
     * 执行模板
     * @param FileBuf $file_buf
     * @param null $data
     */
    public function execute(FileBuf $file_buf, $data)
    {
        $tmp_line_result = array();
        $count = count($this->result_code);
        for ($index = 0; $index < $count; ++$index) {
            $code_type = $this->result_code[$index];
            $tmp_parse_result = $this->parse_result[$index];
            switch ($code_type) {
                //普通的一行内容
                case self::TPL_OP_NORMAL_LINE:
                    $file_buf->pushStr($tmp_parse_result);
                    break;
                //带标签的一行
                case self::TPL_OP_TAG_LINE:
                    $tmp_line_result = array($tmp_parse_result);
                    break;
                //普通字符串
                case self::TPL_OP_NORMAL_STRING:
                    $tmp_line_result[] = $tmp_parse_result;
                    break;
                //变量
                case self::TPL_OP_VAR:
                    //如果变量存在，替换变更
                    if (isset($data[$tmp_parse_result])) {
                        $tmp_line_result[] = $data[$tmp_parse_result];
                    } //如果变量不存在，转换为str_buf
                    else {
                        $buf = new StrBuf($tmp_parse_result);
                        $file_buf->addVariable($tmp_parse_result, $buf);
                        $tmp_line_result[] = $buf;
                    }
                    break;
                //buf
                case self::TPL_OP_CODE_BUF:
                case self::TPL_OP_STR_BUF:
                    if (self::TPL_OP_STR_BUF === $code_type) {
                        $buf = $file_buf->touchStrBuf($tmp_parse_result);
                    } else {
                        $buf = new CodeBuf($tmp_parse_result);
                    }
                    $tmp_line_result[] = $buf;
                    //如果下一个op code是参数，那就把下一步立即做掉
                    if (self::TPL_OP_ARGS === $this->result_code[$index + 1]) {
                        $this->bufArgSet($buf, $this->result_code[$index++]);
                    }
                    break;
                //带标签的行结束
                case self::TPL_OP_BR:
                    $this->lineExecuteResult($tmp_line_result, $file_buf);
                    break;
            }
        }
    }

    /**
     * 参数设置
     * @param BufInterface $buf
     * @param array $buf_args
     */
    private function bufArgSet($buf, $buf_args)
    {
        //缩进参数
        if (isset($buf_args['indent'])) {
            $buf->setIndent((int)$buf_args['indent']);
        }
        //str buf 连接参数
        if (isset($buf_args['join_str']) && $buf instanceof StrBuf) {
            $buf->setJoinStr($buf_args['join_str']);
        }
    }

    /**
     * 整理一行执行结果
     * @param array $line_result
     * @param FileBuf $file_buf
     */
    private function lineExecuteResult($line_result, $file_buf)
    {
        $indent_str = array_shift($line_result);
        if (empty($line_result)) {
            return;
        }
        $count = count($line_result);
        //如果只有一项，并且这一项是 CodeBuf
        if (1 === $count && is_object($line_result[0]) && $line_result[0] instanceof CodeBuf) {
            /** @var CodeBuf $code_buf */
            $code_buf = $line_result[0];
            $code_buf->setIndent($this->strToIndent($indent_str));
            $file_buf->insertNameBuf($code_buf->getName(), $code_buf);
        } //全部当成str buf
        else {
            $str_buf = new StrBuf();
            $str_buf->pushStr($indent_str);
            foreach ($line_result as $item) {
                $str_buf->push($item);
            }
            $file_buf->insertBuf($str_buf);
        }
    }

    /**
     * 模板字符串转换为code
     * @param int $code_type
     * @param string $value
     */
    private function pushTplCode($code_type, $value)
    {
        $this->result_code[] = $code_type;
        $this->parse_result[] = $value;
    }

    /**
     * 将str转成缩进次数
     * @param string $str
     * @return int
     */
    private function strToIndent($str)
    {
        //统计4个空格  或者  tab 作为缩进的
        $blank_indent = substr_count($str, '    ');
        $tab_indent = substr_count($str, "\t");
        return $blank_indent + $tab_indent;
    }


    /**
     * 解析一行
     * @param string $line_content
     */
    private function parseLine($line_content)
    {
        $total_len = strlen($line_content);
        //得到移除两边的空格和 tab之后的内容
        $content = ltrim($line_content, " \t");
        $trim_len = strlen($content);
        //得到缩进内容的长度
        $indent_len = $total_len - $trim_len;
        $indent_str = '';
        if ($indent_len > 0) {
            //得到缩进的内容
            $indent_str = substr($line_content, 0, $indent_len);
        }
        $content = rtrim($content);
        $tag_len = strlen(self::LEFT_TAG);
        $tmp_end_pos = $tag_len * -1;
        $beg_pos = strpos($content, self::LEFT_TAG);
        $this->pushTplCode(self::TPL_OP_TAG_LINE, $indent_str);

        //↑↑↑以上代码比较复杂，目的就是将一行前的空白转换成缩进
        while (false !== $beg_pos) {
            if ($beg_pos > 0) {
                $normal_str = substr($content, $tmp_end_pos + $tag_len, $beg_pos - $tmp_end_pos - $tag_len);
                $this->pushTplCode(self::TPL_OP_NORMAL_STRING, $normal_str);
            }
            $tmp_end_pos = strpos($content, self::RIGHT_TAG, $beg_pos);
            //没有找到闭合的标签，就当成普通字符串
            if (false === $tmp_end_pos) {
                $this->pushTplCode(self::TPL_OP_NORMAL_STRING, substr($content, $beg_pos));
                break;
            }
            $tag_content = substr($content, $beg_pos + $tag_len, $tmp_end_pos - $beg_pos - $tag_len);
            $this->parseTag($tag_content);
            $beg_pos = strpos($content, self::LEFT_TAG, $tmp_end_pos);
        }
        if ($tmp_end_pos + $tag_len < strlen($content)) {
            $normal_str = substr($content, $tmp_end_pos + $tag_len);
            $this->pushTplCode(self::TPL_OP_NORMAL_STRING, $normal_str);
        }
        //一行结束
        $this->pushTplCode(self::TPL_OP_BR, '');
    }

    /**
     * 解析标签里边的内容
     * @param string $tag_content
     * @throws Exception
     */
    private function parseTag($tag_content)
    {
        $tag_content = trim($tag_content);
        if (empty($tag_content)) {
            return;
        }
        //变量直接替换
        if ('$' === $tag_content[0]) {
            $var_name = substr($tag_content, 1);
            if (!UisStr::isValidVarName($var_name)) {
                $err_msg = $this->errorMsg('Invalid name:' . $var_name);
                throw new Exception($err_msg);
            }
            $this->pushTplCode(self::TPL_OP_VAR, $var_name);
        } //内置宏
        elseif (isset(self::$define_code_buf[$tag_content])) {
            $this->pushTplCode(self::TPL_OP_CODE_BUF, self::$define_code_buf[$tag_content]);
        } //其它buf
        else {
            $flag = '::';
            $flag_pos = strpos($tag_content, $flag);
            $err_msg = $this->errorMsg('Unknown views tag:' . self::LEFT_TAG . $tag_content . self::RIGHT_TAG);
            if (false === $flag_pos) {
                throw new Exception($err_msg);
            }
            $buf_type = strtolower(substr($tag_content, 0, $flag_pos));
            $buf_arg_str = substr($tag_content, $flag_pos + strlen($flag));
            $arg_pos = strpos($buf_arg_str, ' ');
            $arg_arr = null;
            if (false === $arg_pos) {
                $buf_name = $buf_arg_str;
            } else {
                $buf_name = substr($buf_arg_str, 0, $arg_pos);
                $arg_arr = UisStr::dualSplit(substr($buf_arg_str, $arg_pos + 1), ' ', '=');
            }
            if (!UisStr::isValidVarName($buf_name)) {
                throw new Exception($err_msg);
            }
            switch ($buf_type) {
                case 'buf':
                case 'code_buf':
                    $this->pushTplCode(self::TPL_OP_CODE_BUF, $buf_name);
                    break;
                case 'str_buf':
                    $this->pushTplCode(self::TPL_OP_STR_BUF, $buf_name);
                    break;
                default:
                    throw new Exception($err_msg);
            }
            //如果有参数指定
            if ($arg_arr) {
                $this->pushTplCode(self::TPL_OP_ARGS, $arg_arr);
            }
        }
    }

    /**
     * 获取模板实例
     * @param string $tpl
     * @return TplLoader
     */
    public static function getInstance($tpl)
    {
        if (isset(self::$tpl_instance[$tpl])) {
            return self::$tpl_instance[$tpl];
        }
        return new self($tpl);
    }
}
