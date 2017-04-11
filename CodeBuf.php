<?php
namespace ffan\dop;

/**
 * Class CodeBuf
 * @package ffan\dop
 */
class CodeBuf
{
    /**
     * @var int 缩进级别
     */
    private $indent = 0;

    /**
     * @var string 内容缓存
     */
    private $str_buffer = '';

    /**
     * @var string 缩进
     */
    private $indent_space;

    /**
     * @var string 临时一行代码
     */
    private $tmp_line_str;

    /**
     * CodeBuf constructor.
     * @param bool $blank_indent 是否使用空格替代缩进
     */
    public function __construct($blank_indent = true)
    {
        if ($blank_indent) {
            $this->indent_space = '    ';
        } else {
            $this->indent_space = "\t";
        }
    }

    /**
     * 写入完整一行代码
     * @param string $str
     * @return $this
     */
    public function push($str)
    {
        $line_str = $this->indentSpace() . $str . PHP_EOL;
        $this->str_buffer .= $line_str;
        return $this;
    }

    /**
     * 空行
     * @return $this
     */
    public function emptyLine()
    {
        $this->str_buffer .= PHP_EOL;
        return $this;
    }

    /**
     * 结束一行
     * @return $this
     */
    public function lineFin()
    {
        $this->push($this->tmp_line_str);
        $this->tmp_line_str = '';
        return $this;
    }

    /**
     * 生成一行临时代码
     * @param string $str
     * @return $this
     */
    public function lineTmp($str)
    {
        $this->tmp_line_str .= $str;
        return $this;
    }

    /**
     * 增加缩进
     */
    public function indentIncrease()
    {
        $this->indent++;
    }

    /**
     * 减少缩进
     */
    public function indentDecrease()
    {
        if (--$this->indent) {
            $this->indent = 0;
        }
    }

    /**
     * 生成缩进
     */
    private function indentSpace()
    {
        return str_repeat($this->indent_space, $this->indent);
    }

    /**
     * 输出内容
     */
    public function dump()
    {
        return $this->str_buffer;
    }

    /**
     * 清空
     */
    public function clean()
    {
        $this->str_buffer = '';
        $this->tmp_line_str = '';
    }
}
