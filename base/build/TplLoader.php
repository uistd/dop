<?php

namespace ffan\dop\build;

use ffan\dop\Exception;

/**
 * Class TplLoader 模板加载器
 * @package ffan\dop\build
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
     * 模板编译结果 换行
     */
    const TPL_OP_BR = 6;

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
    private function logMsg($msg)
    {
        return $msg . ' @ ' . $this->tpl_file . ' line ' . $this->line_number;
    }

    /**
     * 执行模板
     * @param FileBuf $file_buf
     * @param null $data
     */
    public function execute(FileBuf $file_buf, $data)
    {
        $tmp_line_result = array();
        foreach ($this->result_code as $index => $code_type) {
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
                    $tmp_line_result[] = isset($data[$tmp_parse_result]) ? $data[$tmp_parse_result] : '$' . $data[$tmp_parse_result];
                    break;
                //code buf
                case self::TPL_OP_CODE_BUF:
                    $tmp_line_result[] = new CodeBuf($tmp_parse_result);
                    break;
                //带标签的行结束
                case self::TPL_OP_BR:
                    $this->lineExecuteResult($tmp_line_result, $file_buf);
                    break;
            }
        }
    }

    /**
     * 整理一行执行结果
     * @param array $line_result
     * @param FileBuf $file_buf
     */
    private function lineExecuteResult($line_result, $file_buf)
    {
        $indent = array_shift($line_result);
        if (empty($line_result)) {
            return;
        }
        $count = count($line_result);
        //如果只有一项，并且这一项是 CodeBuf
        if (1 === $count && is_object($line_result[0]) && $line_result[0] instanceof CodeBuf) {
            /** @var CodeBuf $code_buf */
            $code_buf = $line_result[0];
            $code_buf->setIndent($indent);
            $file_buf->insertNameBuf($code_buf->getName(), $code_buf);
        }
        //全部当成str buf
        else {
            $str_buf = new StrBuf();
            foreach ($line_result as $item) {
                $str_buf->push($item);
            }
            $str_buf->setIndent($indent);
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
        $indent_value = 0;
        if ($indent_len > 0) {
            //得到缩进的内容
            $indent_str = substr($line_content, 0, $indent_len);
            //统计4个空格  或者  tab 作为缩进的
            $blank_indent = substr_count($indent_str, '    ');
            $tab_indent = substr_count($indent_str, "\t");
            $indent_value = $blank_indent + $tab_indent;
        }
        $content = rtrim($content);
        $tag_len = strlen(self::LEFT_TAG);
        $tmp_end_pos = $tag_len * -1;
        $beg_pos = strpos($content, self::LEFT_TAG);
        $this->pushTplCode(self::TPL_OP_TAG_LINE, $indent_value);

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
            $this->pushTplCode(self::TPL_OP_VAR, substr($tag_content, 1));
        } //code buf
        else {
            switch ($tag_content) {
                case 'PROPERTY_CODE_BUF':
                    $buf_name = FileBuf::PROPERTY_BUF;
                    break;
                case 'METHOD_CODE_BUF':
                    $buf_name = FileBuf::METHOD_BUF;
                    break;
                case 'HEADER_CODE_BUF':
                    $buf_name = FileBuf::HEADER_BUF;
                    break;
                case 'IMPORT_CODE_BUF':
                    $buf_name = FileBuf::IMPORT_BUF;
                    break;
                default:
                    $flag = 'buf::';
                    $pos = strpos($tag_content, $flag);
                    if (0 !== $pos) {
                        $msg = $this->logMsg('Unknown tpl tag:' . self::LEFT_TAG . $tag_content . self::RIGHT_TAG);
                        throw new Exception($msg);
                    }
                    $buf_name = substr($tag_content, strlen($flag));
                    break;
            }
            $this->pushTplCode(self::TPL_OP_CODE_BUF, $buf_name);
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
