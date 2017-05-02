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
     * @var array 模板变量
     */
    private $tpl_data;

    /**
     * @var string 模板名称
     */
    private $tpl_file;

    /**
     * @var FileBuf
     */
    private $result_buf;

    /**
     * @var int 行号
     */
    private $line_number = 0;

    /**
     * TplLoader constructor.
     * @param FileBuf $file_buf
     * @param string $tpl_file
     * @param null $data
     * @throws Exception
     */
    public function __construct(FileBuf $file_buf, $tpl_file, $data)
    {
        if (!is_file($tpl_file)) {
            throw new Exception('Can not found tpl file:' . $tpl_file);
        }
        $this->tpl_file = $tpl_file;
        $this->tpl_data = $data;
        $this->result_buf = $file_buf;
        $this->parseTpl();
    }

    /**
     * 解析模板
     */
    private function parseTpl()
    {
        $file_handle = fopen($this->tpl_file, 'r');
        while ($line = fgets($file_handle)) {
            ++$this->line_number;
            if (false === strpos($line, self::LEFT_TAG)) {
                $this->result_buf->pushStr(rtrim($line, "\n"));
                continue;
            }
            $this->parseLine($line);
        }
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
        $indent_str = '';
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
        $result = array();
        while (false !== $beg_pos) {
            if ($beg_pos > 0) {
                $normal_str = substr($content, $tmp_end_pos + $tag_len, $beg_pos - $tmp_end_pos - $tag_len);
                $result[] = $normal_str;
            }
            $tmp_end_pos = strpos($content, self::RIGHT_TAG, $beg_pos);
            //没有找到闭合的标签，就当成普通字符串
            if (false === $tmp_end_pos) {
                $result[] = substr($content, $beg_pos);
                break;
            }
            $tag_content = substr($content, $beg_pos + $tag_len, $tmp_end_pos - $beg_pos - $tag_len);
            $result[] = $this->parseTag($tag_content);
            $beg_pos = strpos($content, self::LEFT_TAG, $tmp_end_pos);
        }
        if ($tmp_end_pos + $tag_len < strlen($content)) {
            $normal_str = substr($content, $tmp_end_pos + $tag_len);
            $result[] = $normal_str;
        }
        $first_item = $result[0];
        //缩进处理
        if (is_string($first_item)) {
            $result[0] = $indent_str . $first_item;
        } else {
            /** @var $first_item BufInterface */
            $first_item->setIndent($indent_value);
        }
        //如果这一行被分成多段，那就将第一段放入str_buf中
        if (count($result) > 0) {
            $str_buf = new StrBuf();
            foreach ($result as $item) {
                $str_buf->push($item);
            }
            $this->result_buf->insertBuf($str_buf);
        }
        //只一个值
        else {
            $this->result_buf->push($result[0]);
        }
    }

    /**
     * 解析标签里边的内容
     * @param string $tag_content
     * @return BufInterface|string
     * @throws Exception
     */
    private function parseTag($tag_content)
    {
        $tag_content = trim($tag_content);
        if (empty($tag_content)) {
            return '';
        }
        //变量直接替换
        if ('$' === $tag_content[0]) {
            $var_name = substr($tag_content, 1);
            if (isset($this->tpl_data[$var_name])) {
                return $this->tpl_data[$var_name];
            } else {
                return $tag_content;
            }
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
                        throw new Exception('Unknown tpl tag:' . self::LEFT_TAG . $tag_content . self::RIGHT_TAG);
                    }
                    $buf_name = substr($tag_content, strlen($flag));
                    break;
            }
            return $this->result_buf->touchBuf($buf_name);
        }
    }
}